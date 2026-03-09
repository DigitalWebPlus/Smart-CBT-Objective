<?php

declare(strict_types=1);

namespace App\Services\Exam;

use App\Models\ExamAttempt;
use App\Models\ExamQuestion;
use App\Models\ObjectiveExamAttempt;
use App\Models\ObjectiveQuestion;
use Illuminate\Support\Collection;

class ExamScoringService
{
    /**
     * @var array<int, mixed>
     */
    private array $questionableSubjects = [];

    public function __construct(private readonly GradeBandService $gradeBandService)
    {
    }

    public function recalculate(ExamAttempt $attempt): ExamAttempt
    {
        $attempt->loadMissing([
            'exam.subjects',
            'exam.questions',
            'objectiveResponses.examQuestion',
        ]);

        $this->hydrateQuestionables($attempt);

        $subjectScores = $this->initializeSubjectScores($attempt);
        $this->applyMaxMarks($attempt, $subjectScores);

        $auto = $this->applyObjectiveScores($attempt, $subjectScores);
        $manual = 0.0;

        $this->finalizeSubjectScores($subjectScores);

        $total = $auto + $manual;
        $totalMarks = (float) ($attempt->exam->total_marks ?: 0);
        $percentage = $totalMarks > 0 ? ($total / $totalMarks) * 100 : 0;
        $band = $this->gradeBandService->determine($percentage);

        $attributes = [
            'auto_score' => round($auto, 2),
            'manual_score' => round($manual, 2),
            'total_score' => round($total, 2),
            'percentage' => round($percentage, 2),
            'grade_letter' => $band['letter'],
            'grade_remark' => $band['remark'],
            'subject_scores' => array_values($subjectScores),
        ];

        $this->persistScores($attempt, $attributes);

        return $attempt->refresh();
    }

    /**
     * @param array<string, array<string, mixed>> $subjectScores
     */
    private function initializeSubjectScores(ExamAttempt $attempt): array
    {
        $scores = [];

        foreach ($attempt->exam->subjects as $subject) {
            $scores[(string) $subject->id] = $this->makeSubjectRecord(
                (int) $subject->id,
                $subject->code,
                $subject->name
            );
        }

        return $scores;
    }

    /**
     * @param array<string, array<string, mixed>> $subjectScores
     */
    private function applyMaxMarks(ExamAttempt $attempt, array &$subjectScores): void
    {
        foreach ($attempt->exam->questions as $examQuestion) {
            $subject = $this->resolveQuestionSubject($examQuestion);
            $subjectId = $subject?->getKey();
            $key = $subjectId ? (string) $subjectId : 'general';

            if (! isset($subjectScores[$key])) {
                $subjectScores[$key] = $this->makeSubjectRecord(
                    $subjectId ? (int) $subjectId : null,
                    $subject?->code ?? 'GEN',
                    $subject?->name ?? 'General'
                );
            }

            $subjectScores[$key]['max_marks'] += (float) ($examQuestion->marks ?? 0);
        }
    }

    /**
     * @param array<string, array<string, mixed>> $subjectScores
     */
    private function applyObjectiveScores(ExamAttempt $attempt, array &$subjectScores): float
    {
        $total = 0.0;

        foreach ($attempt->objectiveResponses as $response) {
            $marks = (float) ($response->awarded_marks ?? 0);
            $total += $marks;

            $subject = $this->resolveQuestionSubject($response->examQuestion);
            $subjectId = $subject?->getKey();
            $key = $subjectId ? (string) $subjectId : 'general';

            if (! isset($subjectScores[$key])) {
                $subjectScores[$key] = $this->makeSubjectRecord(
                    $subjectId ? (int) $subjectId : null,
                    $subject?->code ?? 'GEN',
                    $subject?->name ?? 'General'
                );
            }

            $subjectScores[$key]['auto_score'] += $marks;
        }

        return $total;
    }

    /**
     * @param array<string, array<string, mixed>> $subjectScores
     */
    /**
     * @param array<string, array<string, mixed>> $subjectScores
     */
    private function finalizeSubjectScores(array &$subjectScores): void
    {
        foreach ($subjectScores as &$record) {
            $record['auto_score'] = round($record['auto_score'], 2);
            $record['manual_score'] = round($record['manual_score'], 2);
            $record['max_marks'] = round($record['max_marks'], 2);
            $record['total_score'] = round($record['auto_score'] + $record['manual_score'], 2);

            if ($record['max_marks'] > 0) {
                $percentage = ($record['total_score'] / $record['max_marks']) * 100;
                $record['percentage'] = round($percentage, 2);
                $band = $this->gradeBandService->determine($percentage);
                $record['grade_letter'] = $band['letter'];
                $record['grade_remark'] = $band['remark'];
            } else {
                $record['percentage'] = 0.0;
                $record['grade_letter'] = null;
                $record['grade_remark'] = null;
            }
        }
        unset($record);
    }

    private function makeSubjectRecord(?int $subjectId, string $code, string $name): array
    {
        return [
            'subject_id' => $subjectId,
            'subject_code' => $code,
            'subject_name' => $name,
            'max_marks' => 0.0,
            'auto_score' => 0.0,
            'manual_score' => 0.0,
            'total_score' => 0.0,
            'percentage' => 0.0,
            'grade_letter' => null,
            'grade_remark' => null,
        ];
    }

    private function persistScores(ExamAttempt $attempt, array $attributes): void
    {
        $query = ObjectiveExamAttempt::query();

        $query->whereKey($attempt->getKey())->update($attributes);
    }

    private function hydrateQuestionables(ExamAttempt $attempt): void
    {
        $collections = collect();

        if ($attempt->exam?->relationLoaded('questions')) {
            $collections = $collections->merge($attempt->exam->questions);
        }

        $objectiveQuestions = $attempt->objectiveResponses
            ->filter(fn ($response) => $response->relationLoaded('examQuestion'))
            ->map(fn ($response) => $response->examQuestion);

        $collections = $collections->merge($objectiveQuestions);

        $this->hydrateExamQuestionCollection($collections);
    }

    /**
     * @param  Collection<int, ExamQuestion|null>  $questions
     */
    private function hydrateExamQuestionCollection(Collection $questions): void
    {
        $uniqueQuestions = $questions->filter(fn ($question) => $question instanceof ExamQuestion)
            ->unique(fn (ExamQuestion $question) => $question->getKey());

        if ($uniqueQuestions->isEmpty()) {
            return;
        }

        $objectiveIds = [];

        foreach ($uniqueQuestions as $question) {
            $objectiveIds[] = (int) $question->objective_question_id;
        }

        $objectiveMap = collect();

        if ($objectiveIds) {
            $objectiveMap = ObjectiveQuestion::query()
                ->with('subject')
                ->whereIn('id', array_unique($objectiveIds))
                ->get()
                ->keyBy('id');
        }

        foreach ($uniqueQuestions as $question) {
            $questionable = $objectiveMap->get((int) $question->objective_question_id);
            $question->setRelation('question', $questionable);
            $this->storeQuestionSubject($question);
        }
    }

    private function storeQuestionSubject(ExamQuestion $question): void
    {
        if (! $question->relationLoaded('question')) {
            return;
        }

        $questionable = $question->getRelation('question');

        if (! $questionable) {
            return;
        }

        $subject = $questionable->relationLoaded('subject')
            ? $questionable->getRelation('subject')
            : $questionable->subject;

        if ($subject) {
            $this->questionableSubjects[$question->getKey()] = $subject;
        }
    }

    private function resolveQuestionSubject(?ExamQuestion $question)
    {
        if (! $question || ! $question->getKey()) {
            return null;
        }

        $key = $question->getKey();

        if (array_key_exists($key, $this->questionableSubjects)) {
            return $this->questionableSubjects[$key];
        }

        if (! $question->relationLoaded('question')) {
            $questionable = $this->loadQuestionableManually($question);
            if (! $questionable) {
                return null;
            }

            $question->setRelation('question', $questionable);
        } else {
            $questionable = $question->getRelation('question');
        }

        if (! $questionable) {
            return null;
        }

        $subject = $questionable->relationLoaded('subject')
            ? $questionable->getRelation('subject')
            : $questionable->subject;

        if ($subject) {
            $this->questionableSubjects[$key] = $subject;
        }

        return $subject;
    }

    private function loadQuestionableManually(ExamQuestion $question): mixed
    {
        if ($question->objective_question_id) {
            return ObjectiveQuestion::query()->with('subject')->find($question->objective_question_id);
        }

        return null;
    }
}

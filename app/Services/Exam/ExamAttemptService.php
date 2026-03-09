<?php

declare(strict_types=1);

namespace App\Services\Exam;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamQuestion;
use App\Models\ObjectiveExamAttempt;
use App\Models\ObjectiveResponse;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use function collect;

class ExamAttemptService
{
    public function __construct(
        private readonly ObjectiveAutoGrader $autoGrader,
        private readonly ExamScoringService $scoringService,
        private readonly ExamSequenceService $sequenceService,
    ) {}

    public function start(Exam $exam, User $candidate, ?string $ipAddress = null): ExamAttempt
    {
        if ($exam->status !== Exam::STATUS_ACTIVE) {
            throw ValidationException::withMessages([
                'exam' => 'Exam is not available for participation.',
            ]);
        }

        if ($exam->start_at && now()->lt($exam->start_at)) {
            throw ValidationException::withMessages([
                'exam' => 'Exam has not started yet.',
            ]);
        }

        if ($exam->end_at && now()->greaterThan($exam->end_at)) {
            throw ValidationException::withMessages([
                'exam' => 'Exam has already ended.',
            ]);
        }

        return DB::transaction(function () use ($exam, $candidate, $ipAddress): ExamAttempt {
            $attemptModel = $this->resolveAttemptModel($exam);

            /** @var Model|null $existing */
            $existing = $attemptModel::query()
                ->where('exam_id', $exam->id)
            ->where('candidate_id', $candidate->id)
                ->first();

            if ($existing === null) {
                $payload = [
                    'id' => $this->sequenceService->next(ExamSequenceService::ATTEMPT_COUNTER),
                    'exam_id' => $exam->id,
                    'candidate_id' => $candidate->id,
                    'status' => ExamAttempt::STATUS_IN_PROGRESS,
                    'started_at' => now(),
                ];

                if ($ipAddress !== null && $ipAddress !== '') {
                    $payload['login_count'] = 1;
                    $payload['login_ips'] = [$ipAddress];
                }

                $existing = $attemptModel::query()->create($payload);
            }

            return ExamAttempt::query()->findOrFail($existing->getKey());
        });
    }

    /**
     * @param array<int, array<string, mixed>> $answers
     */
    public function submit(ExamAttempt $attempt, array $answers): ExamAttempt
    {
        if ($attempt->submitted_at) {
            throw ValidationException::withMessages([
                'attempt' => 'This attempt has already been submitted.',
            ]);
        }

        return DB::transaction(function () use ($attempt, $answers): ExamAttempt {
            $answersByQuestion = collect($answers)
                ->filter(fn ($row) => Arr::has($row, 'exam_question_id'))
                ->keyBy(fn ($row) => (int) Arr::get($row, 'exam_question_id'));
            $examQuestions = $attempt->exam->questions()->with('question.options')->get();
            foreach ($examQuestions as $examQuestion) {
                $answerPayload = $answersByQuestion->get($examQuestion->id, []);
                $this->storeResponse($attempt, $examQuestion, $answerPayload);
            }

            $concreteAttempt = $this->resolveConcreteAttempt($attempt);

            $concreteAttempt->forceFill([
                'status' => ExamAttempt::STATUS_GRADED,
                'submitted_at' => now(),
            ])->save();

            $this->scoringService->recalculate($attempt->refresh());

            return ExamAttempt::query()->findOrFail($attempt->id);
        });
    }

    /**
     * @param array<int, array<string, mixed>> $answers
     */
    public function saveDraft(ExamAttempt $attempt, array $answers): int
    {
        if ($attempt->status !== ExamAttempt::STATUS_IN_PROGRESS) {
            throw ValidationException::withMessages([
                'attempt' => 'Only in-progress attempts may be updated.',
            ]);
        }

        return DB::transaction(function () use ($attempt, $answers): int {
            $answersByQuestion = collect($answers)
                ->filter(fn ($row) => Arr::has($row, 'exam_question_id'))
                ->keyBy(fn ($row) => (int) Arr::get($row, 'exam_question_id'));

            if ($answersByQuestion->isEmpty()) {
                return 0;
            }

            $attempt->loadMissing('exam');

            $examQuestions = $attempt->exam
                ->questions()
                ->whereIn('exam_questions.id', $answersByQuestion->keys())
                ->with('question.options')
                ->get();

            foreach ($examQuestions as $examQuestion) {
                $answerPayload = $answersByQuestion->get($examQuestion->id, []);
                $this->storeResponse($attempt, $examQuestion, $answerPayload);
            }

            return $examQuestions->count();
        });
    }

    private function storeResponse(ExamAttempt $attempt, ExamQuestion $examQuestion, array $answerPayload): void
    {
        $this->storeObjectiveResponse($attempt, $examQuestion, $answerPayload);
    }

    private function storeObjectiveResponse(ExamAttempt $attempt, ExamQuestion $examQuestion, array $answerPayload): void
    {
        $selected = Arr::get($answerPayload, 'selected_option_ids', []);
        $selectedIds = \App\Models\ObjectiveResponse::normalizeSelectedOptionIds($selected);
        $result = $this->autoGrader->grade($examQuestion, $selectedIds);

        $objectiveQuestionId = $examQuestion->objective_question_id ?? $examQuestion->questionable_id;

        $selectedLabels = \App\Models\ObjectiveResponse::resolveSelectedOptionLabels(
            $examQuestion->question,
            $selectedIds
        );
        $metadata = [];
        if (! empty($selectedIds)) {
            $metadata['selected_option_ids'] = $selectedIds;
        }
        if (! empty($selectedLabels)) {
            $metadata['selected_option_labels'] = $selectedLabels;
        }

        ObjectiveResponse::query()->updateOrCreate(
            [
                'exam_attempt_id' => $attempt->id,
                'exam_question_id' => $examQuestion->id,
                'objective_question_id' => $objectiveQuestionId,
            ],
            [
                'selected_option_ids' => $selectedIds,
                'is_correct' => $result['is_correct'],
                'awarded_marks' => $result['awarded_marks'],
                'metadata' => $metadata ?: null,
            ]
        );
    }

    public function updateStatus(ExamAttempt $attempt, string $status): ExamAttempt
    {
        $concrete = $this->resolveConcreteAttempt($attempt);
        $concrete->forceFill(['status' => $status])->save();

        return ExamAttempt::query()->findOrFail($attempt->id);
    }

    private function resolveAttemptModel(Exam $exam): string
    {
        return ObjectiveExamAttempt::class;
    }

    private function resolveConcreteAttempt(ExamAttempt $attempt): Model
    {
        $attempt->loadMissing('exam');
        return ObjectiveExamAttempt::query()->findOrFail($attempt->id);
    }
}

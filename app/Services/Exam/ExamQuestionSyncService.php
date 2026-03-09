<?php

declare(strict_types=1);

namespace App\Services\Exam;

use App\Models\Exam;
use App\Models\ObjectiveExamQuestion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExamQuestionSyncService
{
    public function __construct(private ?ExamSequenceService $sequenceService = null)
    {
        $this->sequenceService ??= app(ExamSequenceService::class);
    }

    public function sync(Exam $exam, array $questions): void
    {
        DB::transaction(function () use ($exam, $questions): void {
            $this->deleteExistingQuestions($exam);

            foreach ($questions as $index => $questionPayload) {
                $this->createExamQuestion($exam, $questionPayload, $index + 1);
            }
        });
    }

    public function purge(Exam $exam): void
    {
        $this->deleteExistingQuestions($exam);
    }

    private function createExamQuestion(Exam $exam, array $questionPayload, int $position): Model
    {
        $questionableId = Arr::get($questionPayload, 'objective_question_id');

        if (empty($questionableId)) {
            throw ValidationException::withMessages([
                'questions' => 'Invalid question reference provided.',
            ]);
        }

        $questionId = $this->sequenceService->next(ExamSequenceService::QUESTION_COUNTER);
        $payload = [
            'id' => $questionId,
            'exam_id' => $exam->id,
            'marks' => Arr::get($questionPayload, 'marks', 0),
            'display_order' => Arr::get($questionPayload, 'display_order', $position),
            'settings' => Arr::get($questionPayload, 'settings'),
        ];

        $payload['objective_question_id'] = $questionableId;

        return ObjectiveExamQuestion::query()->create($payload);
    }

    private function deleteExistingQuestions(Exam $exam): void
    {
        ObjectiveExamQuestion::query()->where('exam_id', $exam->id)->delete();
    }
}

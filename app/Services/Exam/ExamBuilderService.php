<?php

declare(strict_types=1);

namespace App\Services\Exam;

use App\Models\Exam;
use App\Models\Admin;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExamBuilderService
{
    public function __construct(
        private readonly ExamQuestionSyncService $questionSyncService,
        private ?ExamSequenceService $sequenceService = null,
    ) {
        $this->sequenceService ??= app(ExamSequenceService::class);
    }

    public function createDraft(array $payload, ?Admin $admin): Exam
    {
        return DB::transaction(function () use ($payload, $admin) {
            $subjectIds = $this->normalizeSubjectIds($payload);
            $departmentIds = $this->normalizeDepartmentIds($payload);
            if (empty($subjectIds)) {
                throw ValidationException::withMessages([
                    'subject_ids' => 'Select at least one subject.',
                ]);
            }
            if (empty($departmentIds)) {
                throw ValidationException::withMessages([
                    'department_ids' => 'Select at least one department.',
                ]);
            }
            $examType = Arr::get($payload, 'exam_type', Exam::TYPE_OBJECTIVE);
            $examId = $this->sequenceService->next(ExamSequenceService::EXAM_COUNTER);

            $status = Arr::get($payload, 'status', Exam::STATUS_DRAFT);
            $publishedAt = Arr::get($payload, 'published_at');
            if ($status === Exam::STATUS_ACTIVE && $publishedAt === null) {
                $publishedAt = now();
            }

            $attributes = [
                'id' => $examId,
                'subject_id' => Arr::first($subjectIds),
                'admin_id' => $admin?->getKey(),
                'title' => Arr::get($payload, 'title'),
                'description' => Arr::get($payload, 'description'),
                'start_at' => Arr::get($payload, 'start_at'),
                'end_at' => Arr::get($payload, 'end_at'),
                'duration_minutes' => Arr::get($payload, 'duration_minutes', 0),
                'status' => $status,
                'settings' => Arr::get($payload, 'settings'),
                'published_at' => $publishedAt,
            ];

            $modelClass = Exam::concreteModelClassForType($examType);
            $modelClass::query()->create($attributes);

            $exam = Exam::query()->findOrFail($examId);

            if (! empty($subjectIds)) {
                $this->syncSubjects($exam, $subjectIds);
            }

            if (! empty($departmentIds)) {
                $this->syncDepartments($exam, $departmentIds);
            }

            if (! empty($payload['questions'])) {
                $this->questionSyncService->sync($exam, $payload['questions']);
            }

            return $exam->refreshTotalMarks();
        });
    }

    public function updateMetadata(Exam $exam, array $payload): Exam
    {
        return DB::transaction(function () use ($exam, $payload) {
            if (isset($payload['exam_type']) && $payload['exam_type'] !== $exam->exam_type) {
                throw ValidationException::withMessages([
                    'exam_type' => 'Exam type cannot be changed once created.',
                ]);
            }

            $metadata = Arr::except($payload, ['questions', 'subject_ids', 'exam_type']);
            if (! empty($metadata)) {
                $exam->persistToConcrete($metadata);
            }

            $subjectIds = $this->normalizeSubjectIds($payload);
            if (! empty($subjectIds)) {
                $this->syncSubjects($exam, $subjectIds);
            }

            $departmentIds = $this->normalizeDepartmentIds($payload);
            if (! empty($departmentIds)) {
                $this->syncDepartments($exam, $departmentIds);
            }

            if (! empty($payload['questions'])) {
                $this->questionSyncService->sync($exam, $payload['questions']);
            }

            return $exam->refreshTotalMarks();
        });
    }

    public function publish(Exam $exam): Exam
    {
        $exam->persistToConcrete([
            'status' => Exam::STATUS_ACTIVE,
            'published_at' => now(),
        ]);

        return $exam->fresh();
    }

    public function delete(Exam $exam): void
    {
        DB::transaction(function () use ($exam): void {
            $exam->concrete()->delete();
        });
    }

    public function forceDeleteByType(string $examType, int $examId): void
    {
        DB::transaction(function () use ($examType, $examId): void {
            $exam = new Exam();
            $exam->forceFill([
                'id' => $examId,
                'exam_type' => $examType,
            ]);

            $this->questionSyncService->purge($exam);

            $modelClass = Exam::concreteModelClassForType($examType);
            $modelClass::withTrashed()->whereKey($examId)->forceDelete();
        });
    }

    /**
     * @param  array<mixed>  $payload
     * @return array<int, int>
     */
    private function normalizeSubjectIds(array $payload): array
    {
        $subjectIds = Arr::get($payload, 'subject_ids', []);

        if (! is_array($subjectIds)) {
            $subjectIds = array_filter([(int) $subjectIds]);
        }

        return collect($subjectIds)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<mixed>  $payload
     * @return array<int, int>
     */
    private function normalizeDepartmentIds(array $payload): array
    {
        $departmentIds = Arr::get($payload, 'department_ids', []);

        if (! is_array($departmentIds)) {
            $departmentIds = array_filter([(int) $departmentIds]);
        }

        return collect($departmentIds)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, int>  $subjectIds
     */
    private function syncSubjects(Exam $exam, array $subjectIds): void
    {
        if (empty($subjectIds)) {
            return;
        }

        $exam->persistToConcrete([
            'subject_id' => Arr::first($subjectIds),
        ]);

        $exam->subjects()->sync($subjectIds);
    }

    /**
     * @param  array<int, int>  $departmentIds
     */
    private function syncDepartments(Exam $exam, array $departmentIds): void
    {
        if (empty($departmentIds)) {
            return;
        }

        $exam->departments()->sync($departmentIds);
    }
}

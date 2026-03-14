<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ExamRequest;
use App\Models\Department;
use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\ObjectiveQuestion;
use App\Models\Subject;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ExamController extends Controller
{
    public function bin(): View
    {
        $supportsSoftDeletes = in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive(Exam::class), true);

        if (! $supportsSoftDeletes) {
            return view('admin.exams.bin', [
                'exams' => collect(),
            ]);
        }

        $exams = Exam::query()
            ->onlyTrashed()
            ->with('subjects')
            ->latest('deleted_at')
            ->get()
            ->map(function (Exam $exam): array {
                return [
                    'id' => (int) $exam->id,
                    'exam_type' => (string) ($exam->exam_type ?? Exam::TYPE_OBJECTIVE),
                    'title' => (string) $exam->title,
                    'description' => (string) ($exam->description ?? ''),
                    'subjects' => $exam->subjects,
                    'deleted_at' => $exam->deleted_at,
                ];
            });

        return view('admin.exams.bin', [
            'exams' => $exams,
        ]);
    }

    public function index(): View
    {
        $exams = Exam::query()
            ->withCount('attempts')
            ->latest()
            ->paginate(15);

        return view('admin.exams.index', compact('exams'));
    }

    public function create(): View
    {
        $examType = Exam::TYPE_OBJECTIVE;
        $subjects = Subject::query()->orderBy('name')->get(['id', 'name', 'code']);
        $questions = ObjectiveQuestion::query()->with('subject')->orderBy('id')->get();
        $departments = Department::query()->orderBy('name')->get(['id', 'name', 'code']);
        $defaultDepartmentIds = Department::query()->where('is_default', true)->pluck('id')->map(fn ($id) => (int) $id)->all();

        $questionBank = $this->buildQuestionBank($subjects);

        return view('admin.exams.create', [
            'exam' => new Exam(),
            'subjects' => $subjects,
            'questions' => $questions,
            'selectedQuestions' => [],
            'departments' => $departments,
            'selectedDepartments' => [],
            'defaultDepartmentIds' => $defaultDepartmentIds,
            'questionBank' => $questionBank,
            'examType' => $examType,
        ]);
    }

    public function store(ExamRequest $request): RedirectResponse
    {
        $payload = $request->validated();
        $departmentIds = $payload['department_ids'] ?? [];
        $subjectIds = $payload['subject_ids'] ?? [];
        $questions = $payload['questions'] ?? [];
        unset($payload['department_ids'], $payload['subject_ids'], $payload['questions']);

        $payload['admin_id'] = $request->user('admin')->id;

        try {
            DB::transaction(function () use ($payload, $questions, $departmentIds, $subjectIds) {
                $exam = Exam::query()->create($payload);

                $exam->departments()->sync($departmentIds);
                $exam->subjects()->sync($subjectIds);

                foreach ($questions as $question) {
                    ExamQuestion::query()->create([
                        'exam_id' => $exam->id,
                        'objective_question_id' => (int) $question['objective_question_id'],
                        'marks' => (float) $question['marks'],
                        'display_order' => (int) $question['display_order'],
                    ]);
                }
            });

            NotificationService::CREATED('Exam created successfully.');

            return redirect()->route('admin.exams.index');
        } catch (\Throwable $throwable) {
            report($throwable);
            NotificationService::ERROR();

            return back()->withInput();
        }
    }

    public function edit(Exam $exam): View
    {
        $exam->load(['questions.question', 'departments', 'subjects']);

        $subjects = Subject::query()->orderBy('name')->get(['id', 'name', 'code']);
        $questions = ObjectiveQuestion::query()->with('subject')->orderBy('id')->get();
        $selectedQuestions = $exam->questions->pluck('objective_question_id')->map(fn ($id) => (int) $id)->all();
        $departments = Department::query()->orderBy('name')->get(['id', 'name', 'code']);
        $selectedDepartments = $exam->departments->pluck('id')->map(fn ($id) => (int) $id)->all();
        $selectedSubjectIds = $exam->subjects->pluck('id')->map(fn ($id) => (int) $id)->all();
        $questionBank = $this->buildQuestionBank($subjects);

        return view('admin.exams.edit', [
            'exam' => $exam,
            'subjects' => $subjects,
            'questions' => $questions,
            'selectedQuestions' => $selectedQuestions,
            'departments' => $departments,
            'selectedDepartments' => $selectedDepartments,
            'selectedSubjectIds' => $selectedSubjectIds,
            'questionBank' => $questionBank,
            'examType' => $exam->exam_type ?? Exam::TYPE_OBJECTIVE,
        ]);
    }

    public function update(ExamRequest $request, Exam $exam): RedirectResponse
    {
        $payload = $request->validated();
        $departmentIds = $payload['department_ids'] ?? [];
        $subjectIds = $payload['subject_ids'] ?? [];
        $questions = $payload['questions'] ?? [];
        unset($payload['department_ids'], $payload['subject_ids'], $payload['questions']);

        try {
            DB::transaction(function () use ($exam, $payload, $questions, $departmentIds, $subjectIds) {
                $exam->update($payload);
                $exam->departments()->sync($departmentIds);
                $exam->subjects()->sync($subjectIds);
                $exam->questions()->delete();

                foreach ($questions as $question) {
                    ExamQuestion::query()->create([
                        'exam_id' => $exam->id,
                        'objective_question_id' => (int) $question['objective_question_id'],
                        'marks' => (float) $question['marks'],
                        'display_order' => (int) $question['display_order'],
                    ]);
                }
            });

            NotificationService::UPDATED('Exam updated successfully.');

            return redirect()->route('admin.exams.index');
        } catch (\Throwable $throwable) {
            report($throwable);
            NotificationService::ERROR();

            return back()->withInput();
        }
    }

    private function buildQuestionBank($subjects): array
    {
        $subjectIds = collect($subjects)->pluck('id')->all();
        $questions = ObjectiveQuestion::query()
            ->whereIn('subject_id', $subjectIds)
            ->orderBy('id')
            ->get(['id', 'question_text', 'marks', 'subject_id']);

        return collect($subjects)->map(function ($subject) use ($questions) {
            $subjectQuestions = $questions
                ->where('subject_id', $subject->id)
                ->values()
                ->map(fn ($question) => [
                    'id' => $question->id,
                    'question_text' => $question->question_text,
                    'marks' => (float) $question->marks,
                ])
                ->all();

            return [
                'id' => $subject->id,
                'name' => $subject->name,
                'code' => $subject->code,
                'questions' => $subjectQuestions,
            ];
        })->values()->all();
    }

    public function destroy(Exam $exam): RedirectResponse
    {
        if ($exam->attempts()->exists()) {
            NotificationService::ERROR('This exam cannot be deleted because it has attempts. Delete attempts first.');

            return redirect()->route('admin.exams.index');
        }

        try {
            $exam->delete();
            NotificationService::DELETED('Exam deleted successfully.');
        } catch (\Throwable $throwable) {
            report($throwable);
            NotificationService::ERROR();
        }

        return redirect()->route('admin.exams.index');
    }

    public function restore(Request $request, string $type, int $exam): RedirectResponse
    {
        $supportsSoftDeletes = in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive(Exam::class), true);

        if (! $supportsSoftDeletes) {
            NotificationService::ERROR('Exam bin restore is not available in this configuration.');

            return back();
        }

        $trashed = Exam::query()
            ->withTrashed()
            ->where('exam_type', $type)
            ->find($exam);

        if (! $trashed || ! $trashed->trashed()) {
            NotificationService::ERROR('Deleted exam not found.');

            return back();
        }

        $trashed->restore();
        NotificationService::SUCCESS('Exam restored successfully.');

        return redirect()->route('admin.exams.bin');
    }

    public function forceDelete(Request $request, string $type, int $exam): RedirectResponse
    {
        $supportsSoftDeletes = in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive(Exam::class), true);

        if (! $supportsSoftDeletes) {
            NotificationService::ERROR('Exam bin permanent delete is not available in this configuration.');

            return back();
        }

        $trashed = Exam::query()
            ->withTrashed()
            ->where('exam_type', $type)
            ->find($exam);

        if (! $trashed || ! $trashed->trashed()) {
            NotificationService::ERROR('Deleted exam not found.');

            return back();
        }

        $trashed->forceDelete();
        NotificationService::SUCCESS('Exam deleted permanently.');

        return redirect()->route('admin.exams.bin');
    }
}

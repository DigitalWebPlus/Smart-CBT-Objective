<?php

declare(strict_types=1);

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User|null $candidate */
        $candidate = $request->user();
        $departmentIds = $this->candidateDepartmentIds($candidate);
        $candidateId = (int) $candidate?->getAuthIdentifier();

        $exams = Exam::query()
            ->where('status', Exam::STATUS_PUBLISHED)
            ->forDepartments($departmentIds)
            ->with([
                'subjects',
                'departments',
                'attempts' => function ($query) use ($candidateId): void {
                    $query->where('user_id', $candidateId);
                },
            ])
            ->latest('starts_at')
            ->paginate(12);

        $inProgressExamId = ExamAttempt::query()
            ->where('user_id', $candidateId)
            ->where('status', ExamAttempt::STATUS_IN_PROGRESS)
            ->latest('started_at')
            ->value('exam_id');

        return view('candidate.exams.index', compact('exams', 'inProgressExamId'));
    }

    public function show(Request $request, Exam $exam): View
    {
        /** @var User|null $candidate */
        $candidate = $request->user();
        $departmentIds = $this->candidateDepartmentIds($candidate);
        abort_if(! $exam->isPublished() || ! $this->examMatchesDepartments($exam, $departmentIds), 404);

        $candidateId = (int) $candidate?->getAuthIdentifier();

        $hasOtherInProgress = ExamAttempt::query()
            ->where('user_id', $candidateId)
            ->where('status', ExamAttempt::STATUS_IN_PROGRESS)
            ->where('exam_id', '!=', $exam->id)
            ->exists();

        if ($hasOtherInProgress) {
            NotificationService::ERROR('You have an exam in progress. Finish it before viewing another exam.');

            return redirect()->route('candidate.exams.index');
        }

        $exam->load([
            'questions.question.options',
            'departments',
            'attempts' => fn ($query) => $query->where('user_id', $candidateId),
        ]);

        $candidateAttempt = $exam->attempts->first();

        return view('candidate.exams.show', compact('exam', 'candidateAttempt'));
    }

    /**
     * @return array<int, int>
     */
    private function candidateDepartmentIds(?User $candidate): array
    {
        if ($candidate === null) {
            return [];
        }

        $ids = $candidate->departments()->pluck('departments.id')->map(fn ($id) => (int) $id)->all();

        if (! empty($ids)) {
            return $ids;
        }

        return $this->defaultDepartmentIds();
    }

    private function examMatchesDepartments(Exam $exam, array $departmentIds): bool
    {
        if (empty($departmentIds)) {
            return false;
        }

        return $exam->departments()
            ->whereIn('departments.id', $departmentIds)
            ->exists();
    }

    /**
     * @return array<int, int>
     */
    private function defaultDepartmentIds(): array
    {
        /** @var Collection<int, Department> $defaults */
        $defaults = cache()->remember('default_department_ids', 300, fn () => Department::query()
            ->where('is_default', true)
            ->get(['id']));

        return $defaults->pluck('id')->map(fn ($id) => (int) $id)->all();
    }
}

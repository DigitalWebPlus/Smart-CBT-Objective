<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CandidateRequest;
use App\Http\Requests\Admin\CandidateImportRequest;
use App\Models\Department;
use App\Models\User;
use App\Services\CandidateImportService;
use App\Services\NotificationService;
use App\Services\ProfileImageService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CandidateController extends Controller
{
    public function index(Request $request): View
    {
        $sortable = [
            'registration_number' => 'registration_number',
            'name' => 'name',
            'email' => 'email',
            'status' => 'status',
            'created_at' => 'created_at',
        ];

        $customSortable = ['department'];

        $sort = (string) $request->get('sort', 'created_at');
        $direction = strtolower((string) $request->get('direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $search = trim((string) $request->get('search', ''));
        $status = (string) $request->get('status', '');
        $departmentId = $request->filled('department') ? (int) $request->get('department') : null;

        if (! array_key_exists($sort, $sortable) && ! in_array($sort, $customSortable, true)) {
            $sort = 'created_at';
        }

        $candidatesQuery = User::query()->with('departments');

        if ($search !== '') {
            $candidatesQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('registration_number', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%');
            });
        }

        if (in_array($status, User::STATUSES, true)) {
            $candidatesQuery->where('status', $status);
        }

        if ($departmentId) {
            $candidatesQuery->whereHas('departments', function ($query) use ($departmentId) {
                $query->where('departments.id', $departmentId);
            });
        }

        if ($sort === 'department') {
            $candidatesQuery
                ->orderBy(
                    Department::query()
                        ->select('name')
                        ->join('department_user', 'departments.id', '=', 'department_user.department_id')
                        ->whereColumn('department_user.user_id', 'users.id')
                        ->orderBy('departments.name')
                        ->limit(1),
                    $direction
                )
                ->orderBy('users.name');
        } else {
            $candidatesQuery->orderBy($sortable[$sort], $direction);
        }

        $candidates = $candidatesQuery
            ->paginate(15)
            ->withQueryString();

        $departments = Department::query()->orderBy('name')->get(['id', 'name', 'code']);

        return view('admin.candidates.index', [
            'candidates' => $candidates,
            'sort' => $sort,
            'direction' => $direction,
            'departments' => $departments,
            'filters' => [
                'search' => $search,
                'status' => in_array($status, User::STATUSES, true) ? $status : null,
                'department' => $departmentId,
            ],
        ]);
    }

    public function create(): View
    {
        $statuses = User::STATUSES;
        $departments = Department::query()->orderBy('name')->get(['id', 'name', 'code']);
        $defaultDepartmentIds = $this->defaultDepartmentIds();

        return view('admin.candidates.create', compact('statuses', 'departments', 'defaultDepartmentIds'));
    }

    public function upload(): View
    {
        return view('admin.candidates.upload');
    }

    public function store(CandidateRequest $request): RedirectResponse
    {
        try {
            $data = $request->validated();
            $departmentIds = $this->sanitizeDepartmentIds($data['department_ids'] ?? []);
            unset($data['department_ids']);
            $data['password'] = Hash::make($data['password']);
            $data['photo'] = ProfileImageService::upload(
                $request->file('photo'),
                'candidates',
                null,
                $data['name'] ?? null
            );

            $candidate = User::create($data);
            $candidate->departments()->sync($departmentIds);
            NotificationService::CREATED('Candidate created successfully.');

            return redirect()->route('admin.candidates.index');
        } catch (\Throwable $throwable) {
            report($throwable);
            NotificationService::ERROR();

            return back()->withInput();
        }
    }

    public function edit(User $candidate): View
    {
        $candidate->load('departments');
        $statuses = User::STATUSES;
        $departments = Department::query()->orderBy('name')->get(['id', 'name', 'code']);
        $defaultDepartmentIds = $this->defaultDepartmentIds();

        return view('admin.candidates.edit', compact('candidate', 'statuses', 'departments', 'defaultDepartmentIds'));
    }

    public function update(CandidateRequest $request, User $candidate): RedirectResponse
    {
        try {
            $data = $request->validated();
            $departmentIds = $this->sanitizeDepartmentIds($data['department_ids'] ?? []);
            unset($data['department_ids']);

            if (! empty($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            } else {
                unset($data['password']);
            }

            $nameBase = $data['name'] ?? $candidate->name;
            $data['photo'] = ProfileImageService::upload(
                $request->file('photo'),
                'candidates',
                $candidate->photo,
                $nameBase
            );

            $candidate->update($data);
            $candidate->departments()->sync($departmentIds);
            NotificationService::UPDATED('Candidate updated successfully.');

            return redirect()->route('admin.candidates.index');
        } catch (\Throwable $throwable) {
            report($throwable);
            NotificationService::ERROR();

            return back()->withInput();
        }
    }

    public function destroy(User $candidate): RedirectResponse
    {
        try {
            ProfileImageService::delete($candidate->photo);
            $candidate->delete();

            NotificationService::DELETED('Candidate removed successfully.');
        } catch (\Throwable $throwable) {
            report($throwable);
            NotificationService::ERROR();
        }

        return redirect()->route('admin.candidates.index');
    }

    public function import(CandidateImportRequest $request, CandidateImportService $candidateImportService): RedirectResponse
    {
        try {
            $report = $candidateImportService->importFromUploadedFile(
                $request->file('file'),
                $request->boolean('overwrite')
            );

            $created = $report['created'] ?? 0;
            $updated = $report['updated'] ?? 0;
            $skipped = $report['skipped'] ?? 0;
            $errors = $report['errors'] ?? [];

            if ($created > 0 || $updated > 0) {
                NotificationService::SUCCESS(sprintf(
                    'Candidates import complete. %d created, %d updated, %d skipped.',
                    $created,
                    $updated,
                    $skipped
                ));
            }

            if (! empty($errors)) {
                $example = Str::limit($errors[0], 180);
                NotificationService::ERROR(sprintf(
                    'Import completed with %d error%s. Example: %s',
                    count($errors),
                    count($errors) === 1 ? '' : 's',
                    $example
                ));
            } elseif ($created === 0 && $updated === 0) {
                NotificationService::SUCCESS('Import finished. No records were created or updated.');
            }

            session()->flash('candidate_import_report', $report);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $throwable) {
            report($throwable);
            NotificationService::ERROR('Unable to import candidates. Please verify the file and try again.');
        }

        return back();
    }

    /**
     * @param  array<int, mixed>  $departmentIds
     * @return array<int, int>
     */
    private function sanitizeDepartmentIds(array $departmentIds): array
    {
        $normalized = collect($departmentIds)
            ->map(static fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (! empty($normalized)) {
            return $normalized;
        }

        return $this->defaultDepartmentIds();
    }

    /**
     * @return array<int, int>
     */
    private function defaultDepartmentIds(): array
    {
        $ids = Department::query()->where('is_default', true)->pluck('id')->all();

        if (! empty($ids)) {
            return array_map('intval', $ids);
        }

        $fallback = Department::query()->limit(1)->pluck('id')->all();

        return array_map('intval', $fallback);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DepartmentRequest;
use App\Models\Department;
use App\Services\NotificationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class DepartmentController extends Controller
{
    public function index(): View
    {
        $departments = Department::query()
            ->withCount(['users', 'exams'])
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.departments.index', compact('departments'));
    }

    public function show(Department $department): View
    {
        $candidates = $department->users()
            ->with('departments:id,name,code')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.departments.show', [
            'department' => $department,
            'candidates' => $candidates,
        ]);
    }

    public function create(): View
    {
        $department = new Department([
            'is_default' => false,
        ]);

        return view('admin.departments.create', compact('department'));
    }

    public function store(DepartmentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $isDefault = (bool) ($data['is_default'] ?? false);

        $department = Department::query()->create([
            'name' => $data['name'],
            'code' => $data['code'] ?? null,
            'description' => $data['description'] ?? null,
            'is_default' => $isDefault,
        ]);

        $this->syncDefaultDepartment($department, $isDefault);

        NotificationService::CREATED('Department created successfully.');

        return redirect()->route('admin.departments.index');
    }

    public function edit(Department $department): View
    {
        return view('admin.departments.edit', compact('department'));
    }

    public function update(DepartmentRequest $request, Department $department): RedirectResponse
    {
        $data = $request->validated();
        $isDefault = (bool) ($data['is_default'] ?? false);

        $department->update([
            'name' => $data['name'],
            'code' => $data['code'] ?? null,
            'description' => $data['description'] ?? null,
            'is_default' => $isDefault,
        ]);

        $this->syncDefaultDepartment($department, $isDefault);

        NotificationService::UPDATED('Department updated successfully.');

        return redirect()->route('admin.departments.index');
    }

    public function destroy(Department $department): RedirectResponse
    {
        if ($department->is_default) {
            NotificationService::ERROR('The default department cannot be deleted.');

            return back();
        }

        try {
            $department->delete();
            NotificationService::DELETED('Department removed successfully.');
        } catch (\Throwable $throwable) {
            report($throwable);
            NotificationService::ERROR();
        }

        if (! Department::query()->where('is_default', true)->exists()) {
            Department::query()->first()?->update(['is_default' => true]);
        }

        return redirect()->route('admin.departments.index');
    }

    private function syncDefaultDepartment(Department $department, bool $shouldBeDefault): void
    {
        if ($shouldBeDefault) {
            Department::query()
                ->where('id', '!=', $department->id)
                ->update(['is_default' => false]);

            if (! $department->is_default) {
                $department->forceFill(['is_default' => true])->save();
            }

            return;
        }

        $hasDefault = Department::query()
            ->where('id', '!=', $department->id)
            ->where('is_default', true)
            ->exists();

        if (! $hasDefault) {
            $department->forceFill(['is_default' => true])->save();
        }
    }
}

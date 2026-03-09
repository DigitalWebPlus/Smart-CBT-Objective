@extends('admin.layouts.master')

@php
    use Illuminate\Support\Str;
@endphp

@section('content')
    <div class="page-wrapper">
        <div class="page-header d-print-none">
            <div class="container-xl d-flex justify-content-between align-items-center">
                <div>
                    <div class="page-pretitle">Organisation</div>
                    <h2 class="page-title">Departments</h2>
                </div>
                <div class="btn-list">
                    <a href="{{ route('admin.departments.create') }}" class="btn btn-primary">
                        <i class="ti ti-plus me-1"></i>
                        New Department
                    </a>
                </div>
            </div>
        </div>

        <div class="page-body">
            <div class="container-xl">
                @if (session('status'))
                    <div class="alert alert-success">
                        {{ session('status') }}
                    </div>
                @endif

                <div class="card">
                    <div class="table-responsive">
                        <table class="table table-vcenter">
                            <thead>
                                <tr>
                                    <th class="w-1">#</th>
                                    <th>Name</th>
                                    <th>Department Code</th>
                                    <th>Candidates</th>
                                    <th>Exams</th>
                                    <th>Default</th>
                                    <th class="w-1">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($departments as $department)
                                    <tr>
                                        <td class="text-muted">{{ ($departments->firstItem() ?? 0) + $loop->index }}</td>
                                        <td>
                                            <div class="fw-semibold">
                                                <a href="{{ route('admin.departments.show', $department) }}" class="text-reset text-decoration-none">
                                                    {{ $department->name }}
                                                </a>
                                            </div>
                                            <small class="text-muted">{{ Str::limit($department->description, 80) }}</small>
                                        </td>
                                        <td>{{ $department->code ?? '—' }}</td>
                                        <td>{{ $department->users_count ?? 0 }}</td>
                                        <td>{{ $department->exams_count ?? 0 }}</td>
                                        <td>
                                            @if ($department->is_default)
                                                <span class="badge bg-success">Default</span>
                                            @else
                                                <span class="text-muted">No</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-list flex-nowrap">
                                                <a href="{{ route('admin.departments.show', $department) }}" class="btn btn-sm btn-outline-secondary">
                                                    View
                                                </a>
                                                <a href="{{ route('admin.departments.edit', $department) }}"
                                                    class="btn btn-sm btn-outline-primary">
                                                    Edit
                                                </a>
                                                    <form action="{{ route('admin.departments.destroy', $department) }}"
                                                        method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                                        {{ $department->is_default ? 'disabled' : '' }}>
                                                        Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-5">
                                            No departments found yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer d-flex justify-content-end">
                        {{ $departments->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@extends('admin.layouts.master')

@php
    use Illuminate\Support\Str;
@endphp

@section('content')
    <div class="page-wrapper">
        <div class="page-header d-print-none">
            <div class="container-xl d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                <div>
                    <div class="page-pretitle">Departments</div>
                    <h2 class="page-title">{{ $department->name }}</h2>
                    <p class="text-secondary mb-2">{{ Str::limit($department->description ?? 'No description provided.', 160) }}</p>
                    <div class="d-flex flex-wrap gap-2">
                        @if ($department->code)
                            <span class="badge bg-blue-lt text-uppercase">Department Code: {{ $department->code }}</span>
                        @endif
                        @if ($department->is_default)
                            <span class="badge bg-success">Default Department</span>
                        @endif
                        <span class="badge bg-secondary-lt text-dark">{{ $candidates->total() }} candidates</span>
                    </div>
                </div>
                <div class="btn-list mt-3 mt-md-0">
                    <a href="{{ route('admin.departments.index') }}" class="btn btn-outline-secondary">
                        <i class="ti ti-arrow-left"></i>
                        Back to Departments
                    </a>
                    <a href="{{ route('admin.departments.edit', $department) }}" class="btn btn-primary">
                        <i class="ti ti-edit"></i>
                        Edit Department
                    </a>
                </div>
            </div>
        </div>

        <div class="page-body">
            <div class="container-xl">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title mb-0">Candidates in {{ $department->name }}</h3>
                        <span class="text-secondary">Page {{ $candidates->currentPage() }} of {{ $candidates->lastPage() }}</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th class="w-1">#</th>
                                    <th>Reg. No.</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Other Departments</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                    <th class="w-1">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($candidates as $candidate)
                                    <tr>
                                        <td class="text-muted">{{ ($candidates->firstItem() ?? 0) + $loop->index }}</td>
                                        <td class="fw-semibold">{{ $candidate->registration_number ?? '—' }}</td>
                                        <td>{{ $candidate->name }}</td>
                                        <td>{{ $candidate->email }}</td>
                                        <td>
                                            @php
                                                $otherDepartments = $candidate->departments->where('id', '!=', $department->id);
                                            @endphp
                                            @if ($otherDepartments->isEmpty())
                                                <span class="text-secondary">None</span>
                                            @else
                                                <div class="d-flex flex-wrap gap-1">
                                                    @foreach ($otherDepartments as $otherDepartment)
                                                        <span class="badge bg-blue-lt text-uppercase">{{ $otherDepartment->code ?? $otherDepartment->name }}</span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge text-white bg-{{ $candidate->status === 'active' ? 'success' : ($candidate->status === 'inactive' ? 'secondary' : ($candidate->status === 'suspended' ? 'warning' : 'danger')) }}">
                                                {{ ucfirst($candidate->status ?? 'active') }}
                                            </span>
                                        </td>
                                        <td>{{ $candidate->created_at->format('M d, Y') }}</td>
                                        <td>
                                            <a href="{{ route('admin.candidates.edit', $candidate) }}" class="btn btn-sm btn-outline-secondary">
                                                Edit
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-secondary py-5">
                                            No candidates have been assigned to this department yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer">
                        {{ $candidates->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@extends('admin.layouts.master')

@section('content')
    <div class="page-wrapper">
        <div class="page-header d-print-none">
            <div class="container-xl d-flex justify-content-between align-items-center">
                <div>
                    <div class="page-pretitle">Manage Candidates</div>
                    <h2 class="page-title">Candidates</h2>
                </div>
                <a href="{{ route('admin.candidates.create') }}" class="btn btn-primary">
                    <i class="ti ti-plus"></i>
                    New Candidate
                </a>
            </div>
        </div>

        <div class="page-body">
            <div class="container-xl">
                <div class="card mb-3">
                    <div class="card-body">
                        <form method="GET" action="{{ route('admin.candidates.index') }}" class="row g-2 align-items-end">
                            <div class="col-12 col-md-4">
                                <label class="form-label">Search</label>
                                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="Name, email, reg no, phone">
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="">All</option>
                                    @foreach (\App\Models\User::STATUSES as $status)
                                        <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>
                                            {{ ucfirst($status) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label">Department</label>
                                <select name="department" class="form-select">
                                    <option value="">All</option>
                                    @foreach ($departments as $department)
                                        <option value="{{ $department->id }}" @selected(($filters['department'] ?? null) === $department->id)>
                                            {{ $department->code ?? $department->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-2 d-flex gap-2">
                                <button class="btn btn-primary w-100" type="submit">Apply</button>
                                <a class="btn btn-outline-secondary w-100" href="{{ route('admin.candidates.index') }}">Reset</a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="table-responsive">
                        @php
                            $currentSort = $sort ?? request('sort', 'created_at');
                            $currentDirection = $direction ?? request('direction', 'desc');
                            $sortUrl = function (string $column) use ($currentSort, $currentDirection) {
                                $nextDirection = $currentSort === $column && $currentDirection === 'asc' ? 'desc' : 'asc';

                                return request()->fullUrlWithQuery([
                                    'sort' => $column,
                                    'direction' => $nextDirection,
                                ]);
                            };
                            $sortIcon = function (string $column) use ($currentSort, $currentDirection): string {
                                if ($currentSort !== $column) {
                                    return 'ti ti-arrows-sort text-muted';
                                }

                                return $currentDirection === 'asc'
                                    ? 'ti ti-arrow-up text-primary'
                                    : 'ti ti-arrow-down text-primary';
                            };
                            $sortClass = fn (string $column) => $currentSort === $column ? 'text-primary fw-semibold' : 'text-reset';
                        @endphp
                        <table class="table table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th class="w-1">#</th>
                                    <th>Photo</th>
                                    <th>
                                        <a href="{{ $sortUrl('registration_number') }}"
                                            class="d-inline-flex align-items-center gap-1 text-decoration-none {{ $sortClass('registration_number') }}">
                                            Reg. No.
                                            <i class="{{ $sortIcon('registration_number') }}"></i>
                                        </a>
                                    </th>
                                    <th>
                                        <a href="{{ $sortUrl('name') }}"
                                            class="d-inline-flex align-items-center gap-1 text-decoration-none {{ $sortClass('name') }}">
                                            Name
                                            <i class="{{ $sortIcon('name') }}"></i>
                                        </a>
                                    </th>
                                    <th>
                                        <a href="{{ $sortUrl('email') }}"
                                            class="d-inline-flex align-items-center gap-1 text-decoration-none {{ $sortClass('email') }}">
                                            Email
                                            <i class="{{ $sortIcon('email') }}"></i>
                                        </a>
                                    </th>
                                    <th>
                                        <a href="{{ $sortUrl('department') }}"
                                            class="d-inline-flex align-items-center gap-1 text-decoration-none {{ $sortClass('department') }}">
                                            Departments
                                            <i class="{{ $sortIcon('department') }}"></i>
                                        </a>
                                    </th>
                                    <th>
                                        <a href="{{ $sortUrl('status') }}"
                                            class="d-inline-flex align-items-center gap-1 text-decoration-none {{ $sortClass('status') }}">
                                            Status
                                            <i class="{{ $sortIcon('status') }}"></i>
                                        </a>
                                    </th>
                                    <th>
                                        <a href="{{ $sortUrl('created_at') }}"
                                            class="d-inline-flex align-items-center gap-1 text-decoration-none {{ $sortClass('created_at') }}">
                                            Created At
                                            <i class="{{ $sortIcon('created_at') }}"></i>
                                        </a>
                                    </th>
                                    <th class="w-1">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($candidates as $candidate)
                                    <tr>
                                        <td class="text-muted">{{ ($candidates->firstItem() ?? 0) + $loop->index }}</td>
                                        <td>
                                            @php
                                                $photoPath = $candidate->photo ?: 'uploads/candidates/default.jpg';
                                                $photoUrl = '/' . ltrim($photoPath, '/');
                                            @endphp
                                            <img src="{{ $photoUrl }}" alt="{{ $candidate->name }}" class="avatar">
                                        </td>
                                        <td class="fw-bold">{{ $candidate->registration_number ?? 'N/A' }}</td>
                                        <td>{{ $candidate->name }}</td>
                                        <td>{{ $candidate->email }}</td>
                                        <td>
                                            @forelse ($candidate->departments as $department)
                                                <span class="badge bg-blue-lt text-uppercase me-1 mb-1">{{ $department->code ?? $department->name }}</span>
                                            @empty
                                                <span class="text-secondary">Unassigned</span>
                                            @endforelse
                                        </td>
                                        <td>
                                            <span class="badge text-white bg-{{ $candidate->status === 'active' ? 'success' : ($candidate->status === 'inactive' ? 'secondary' : ($candidate->status === 'suspended' ? 'warning' : 'danger')) }}">
                                                {{ ucfirst($candidate->status ?? 'active') }}
                                            </span>
                                        </td>
                                        <td>{{ $candidate->created_at->format('M d, Y') }}</td>
                                        <td>
                                            <div class="btn-list flex-nowrap">
                                                <a href="{{ route('admin.candidates.edit', $candidate) }}" class="btn btn-sm btn-outline-secondary">
                                                    Edit
                                                </a>
                                                <form action="{{ route('admin.candidates.destroy', $candidate) }}" method="POST"
                                                    data-swal-confirm="Delete this candidate?"
                                                    data-swal-title="Remove Candidate"
                                                    data-swal-confirm-button="Yes, delete">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-secondary">No candidates found.</td>
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

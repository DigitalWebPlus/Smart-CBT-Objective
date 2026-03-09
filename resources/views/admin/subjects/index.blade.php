@extends('admin.layouts.master')

@section('content')
    <div class="page-wrapper">
        <div class="page-header d-print-none">
            <div class="container-xl d-flex justify-content-between align-items-center">
                <div>
                    <div class="page-pretitle">Question Bank</div>
                    <h2 class="page-title">Subjects</h2>
                </div>
                <a href="{{ route('admin.subjects.create') }}" class="btn btn-primary">
                    <i class="ti ti-plus"></i>
                    New Subject
                </a>
            </div>
        </div>

        <div class="page-body">
            <div class="container-xl">
                <div class="card">
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Subject Code</th>
                                    <th>Status</th>
                                    <th>Questions</th>
                                    <th>Updated</th>
                                    <th class="w-1">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($subjects as $subject)
                                    <tr>
                                        <td class="fw-bold">{{ $subject->name }}</td>
                                        <td>{{ $subject->code }}</td>
                                        <td>
                                            <span class="badge {{ $subject->is_active ? 'bg-success' : 'bg-secondary' }}">
                                                {{ $subject->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-blue-lt">{{ $subject->questions_count }}</span>
                                        </td>
                                        <td>{{ optional($subject->updated_at)->format('M d, Y') }}</td>
                                        <td>
                                            <div class="btn-list flex-nowrap">
                                                <a href="{{ route('admin.subjects.edit', $subject) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                                <form action="{{ route('admin.subjects.destroy', $subject) }}" method="POST"
                                                    data-swal-confirm
                                                    data-swal-title="Delete this subject?"
                                                    data-swal-confirm="This subject and its related questions will be removed."
                                                    data-swal-confirm-button="Yes, delete"
                                                    data-swal-cancel-button="Cancel">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-secondary">No subjects found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer">
                        {{ $subjects->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

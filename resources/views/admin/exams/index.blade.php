@extends('admin.layouts.master')

@section('content')
    <div class="page-wrapper">
        <div class="page-header d-print-none">
            <div class="container-xl d-flex justify-content-between align-items-center">
                <div>
                    <div class="page-pretitle">Assessments</div>
                    <h2 class="page-title">Exams</h2>
                </div>
                <a href="{{ route('admin.exams.create') }}" class="btn btn-primary">
                    <i class="ti ti-plus"></i>
                    New Exam
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
                                    <th>Exam</th>
                                    <th>Status</th>
                                    <th>Questions</th>
                                    <th>Attempts</th>
                                    <th>Window</th>
                                    <th class="w-1">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($exams as $exam)
                                    <tr>
                                        <td class="fw-semibold">{{ $exam->title }}</td>
                                        <td>
                                            <span class="badge text-white bg-{{ $exam->status === \App\Models\Exam::STATUS_PUBLISHED ? 'success' : 'secondary' }}">
                                                {{ \Illuminate\Support\Str::headline($exam->status) }}
                                            </span>
                                        </td>
                                        <td>{{ $exam->questions()->count() }}</td>
                                        <td>{{ $exam->attempts_count }}</td>
                                        <td>
                                            @if ($exam->starts_at || $exam->ends_at)
                                                {{ optional($exam->starts_at)->format('M d, Y') }}
                                                @if ($exam->ends_at)
                                                    – {{ optional($exam->ends_at)->format('M d, Y') }}
                                                @endif
                                            @else
                                                <span class="text-secondary">Anytime</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-list flex-nowrap">
                                                <a href="{{ route('admin.exams.edit', $exam) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                                <a href="{{ route('admin.attempts.show', $exam) }}" class="btn btn-sm btn-outline-primary">Attempts</a>
                                                <form action="{{ route('admin.exams.destroy', $exam) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-secondary py-5">
                                            No exams available.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer">
                        {{ $exams->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

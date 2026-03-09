@extends('admin.layouts.master')

@section('content')
    <div class="page-wrapper">
        <div class="page-header d-print-none">
            <div class="container-xl d-flex justify-content-between align-items-center">
                <div>
                    <div class="page-pretitle">Results</div>
                    <h2 class="page-title">Attempt Answers</h2>
                    <p class="text-secondary mb-0">Open an attempt to review answered questions.</p>
                </div>
            </div>
        </div>

        <div class="page-body">
            <div class="container-xl">
                <div class="row g-3 mb-4">
                    @foreach (\App\Models\ExamAttempt::STATUSES as $attemptStatus)
                        <div class="col-6 col-md-3">
                            <div class="card">
                                <div class="card-body">
                                    <div class="text-secondary text-uppercase small">{{ \Illuminate\Support\Str::headline($attemptStatus) }}</div>
                                    <div class="h2 mb-0">{{ $statusCounts[$attemptStatus] ?? 0 }}</div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <form method="GET" class="card mb-4">
                    <div class="card-body row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">Exam</label>
                            <select name="exam_id" class="form-select">
                                <option value="">All exams</option>
                                @foreach ($exams as $exam)
                                    <option value="{{ $exam->id }}" @selected((int) $examId === (int) $exam->id)>
                                        {{ $exam->title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All statuses</option>
                                @foreach (\App\Models\ExamAttempt::STATUSES as $attemptStatus)
                                    <option value="{{ $attemptStatus }}" @selected($status === $attemptStatus)>
                                        {{ \Illuminate\Support\Str::headline($attemptStatus) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Candidate</label>
                            <input type="text" name="candidate" class="form-control" value="{{ $candidateQuery }}"
                                placeholder="Search name, email, registration">
                        </div>
                        <div class="col-md-2 text-end">
                            <button class="btn btn-primary w-100" type="submit">Filter</button>
                        </div>
                    </div>
                </form>

                <div class="card">
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th>Exam</th>
                                    <th>Candidate</th>
                                    <th>Status</th>
                                    <th>Score</th>
                                    <th class="w-1"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($attempts as $attempt)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $attempt->exam?->title ?? 'Exam' }}</div>
                                            <div class="text-secondary small">ID: {{ $attempt->exam_id }}</div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold">{{ $attempt->candidate?->name ?? 'Candidate' }}</div>
                                            <div class="text-secondary small">{{ $attempt->candidate?->email }}</div>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $attempt->status === \App\Models\ExamAttempt::STATUS_PUBLISHED ? 'green' : ($attempt->status === \App\Models\ExamAttempt::STATUS_GRADED ? 'blue' : 'yellow') }} text-white">
                                                {{ \Illuminate\Support\Str::headline($attempt->status) }}
                                            </span>
                                        </td>
                                        <td>
                                            {{ number_format((float) $attempt->total_score, 2) }}
                                            / {{ number_format((float) $attempt->exam?->total_marks, 2) }}
                                        </td>
                                        <td class="text-end">
                                            @if ($attempt->exam)
                                                <a href="{{ route('admin.monitor-exams.attempts.show', [$attempt->exam, $attempt]) }}" class="btn btn-sm btn-outline-secondary">View Answers</a>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-secondary">No attempts found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer">
                        {{ $attempts->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

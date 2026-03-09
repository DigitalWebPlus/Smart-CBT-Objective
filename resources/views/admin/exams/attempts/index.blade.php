@extends('admin.layouts.master')
@section('content')
    <div class="page-wrapper">
        <div class="page-header d-print-none">
            <div class="container-xl">
                <div class="row g-2 align-items-center">
                    <div class="col">
                        <div class="page-pretitle">Attempts</div>
                        <h2 class="page-title">{{ $exam->title }}</h2>
                        <div class="text-secondary">Review submitted attempts.</div>
                    </div>
                    <div class="col-auto ms-auto d-flex gap-2">
                        <a href="{{ route('admin.exams.edit', $exam) }}" class="btn btn-outline">Back to Exam</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="page-body">
            <div class="container-xl">
                <div class="card">
                    <div class="table-responsive">
                        <table class="table card-table table-vcenter">
                            <thead>
                                <tr>
                                    <th>Candidate</th>
                                    <th>Status</th>
                                    <th>Submitted</th>
                                    <th>Score</th>
                                    <th class="w-1"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($attempts as $attempt)
                                    <tr>
                                        <td>
                                            <div class="fw-bold">{{ $attempt->candidate?->name ?? 'Unknown' }}</div>
                                            <div class="text-secondary">{{ $attempt->candidate?->email }}</div>
                                        </td>
                                        @php
                                            $statusColors = [
                                                \App\Models\ExamAttempt::STATUS_SUBMITTED => 'yellow',
                                                \App\Models\ExamAttempt::STATUS_PUBLISHED => 'green',
                                                \App\Models\ExamAttempt::STATUS_CANCELED => 'red',
                                                \App\Models\ExamAttempt::STATUS_RETAKE => 'orange',
                                            ];
                                        @endphp
                                        <td>
                                            <span class="badge bg-{{ $statusColors[$attempt->status] ?? 'secondary' }} text-white">
                                                {{ \Illuminate\Support\Str::headline($attempt->status) }}
                                            </span>
                                        </td>
                                        <td>{{ optional($attempt->submitted_at)?->format('M d, h:i a') ?? '—' }}</td>
                                        <td>{{ number_format($attempt->score ?? 0, 2) }}</td>
                                        <td class="text-end">
                                            <a href="{{ route('admin.monitor-exams.attempts.show', [$exam, $attempt]) }}" class="btn btn-sm">Review</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-secondary py-5">No attempts yet.</td>
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

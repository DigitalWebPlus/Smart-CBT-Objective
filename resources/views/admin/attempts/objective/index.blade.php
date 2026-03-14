@extends('admin.layouts.master')

@section('content')
    <div class="page-wrapper">
        <div class="page-header d-print-none">
            <div class="container-xl d-flex justify-content-between align-items-center">
                <div>
                    <div class="page-pretitle">Attempts</div>
                    <h2 class="page-title">Objective Attempt Summaries</h2>
                    <p class="text-secondary mb-0">View objective attempt summaries and score distribution.</p>
                </div>
            </div>
        </div>

        <div class="page-body">
            <div class="container-xl">
                <div class="row g-3 mb-4">
                    @forelse ($exams as $exam)
                        @php
                            $cardThemes = [
                                'bg-primary text-white',
                                'bg-success text-white',
                                'bg-warning text-white',
                                'bg-info text-white',
                                'bg-danger text-white',
                                'bg-secondary text-white',
                                'bg-dark text-white',
                            ];
                            $theme = $cardThemes[$loop->index % count($cardThemes)];
                        @endphp
                        <div class="col-12 col-md-6 col-xl-4">
                            <a href="{{ route('admin.attempts.show', $exam) }}"
                                class="text-decoration-none">
                                <div class="card h-100 border-0 shadow-sm {{ $theme }} {{ (int) $examId === (int) $exam->id ? 'ring-1 ring-primary' : '' }}">
                                    @php
                                        $targetCount = (int) ($exam->candidate_target_count ?? 0);
                                        $attemptCount = (int) ($exam->attempts_count ?? 0);
                                        $coverage = $targetCount > 0
                                            ? min(100, round(($attemptCount / $targetCount) * 100, 1))
                                            : 0;
                                    @endphp
                                    <div class="card-header d-flex align-items-start justify-content-between">
                                        <div class="d-flex gap-3 align-items-start">
                                            <span class="badge rounded-circle bg-white text-primary p-2">
                                                <i class="ti ti-notebook"></i>
                                            </span>
                                            <div>

                                            <div class="h2 mb-0">{{ $exam->title }}</div>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            @php
                                                $isLive = ! $exam->ends_at || now()->lt($exam->ends_at);
                                            @endphp
                                            @if ($isLive)
                                                <span class="badge bg-success text-white mb-1">Live</span>
                                            @else
                                                <span class="badge bg-danger text-white mb-1">Ended</span>
                                            @endif
                                            <div class="text-white small">
                                                {{ number_format((int) ($exam->subjects_count ?? 0)) }} subjects
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body d-flex flex-column">
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span class="text-white small">Coverage</span>
                                                <span class="fw-semibold">{{ number_format($coverage, 1) }}%</span>
                                            </div>
                                            <div class="progress" style="height: 8px;">
                                                <div class="progress-bar" role="progressbar"
                                                    style="width: {{ $coverage }}%" aria-valuenow="{{ $coverage }}"
                                                    aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </div>
                                        <div class="d-grid gap-2 mb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="text-white">Taken</span>
                                                <span class="fw-semibold">{{ number_format((int) ($exam->attempts_count ?? 0)) }}</span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="text-white">Candidates</span>
                                                <span class="fw-semibold">{{ number_format((int) ($exam->candidate_target_count ?? 0)) }}</span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="text-white">Total Marks</span>
                                                <span class="fw-semibold">{{ number_format((float) ($exam->questions_sum_marks ?? 0), 2) }}</span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="text-white">Total Questions</span>
                                                <span class="fw-semibold">{{ number_format((int) ($exam->questions_count ?? 0)) }}</span>
                                            </div>
                                        </div>

                                        <div class="mt-auto d-flex justify-content-between align-items-center">
                                            <span class="text-white small">View attempts & scores</span>
                                            <span class="btn btn-sm btn-light">Open</span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="alert alert-info mb-0">No objective exams found.</div>
                        </div>
                    @endforelse
                </div>

                @if ($examId)
                    <div class="row g-3 mb-4">
                        @foreach ($statusOptions as $attemptStatus)
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
                                <label class="form-label">Exam Name</label>
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
                                    @foreach ($statusOptions as $attemptStatus)
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
                                        <th>#</th>
                                        <th>Candidate</th>
                                        <th>Status</th>
                                        <th>Exam Name</th>
                                        <th>Subjects</th>
                                        <th>Total Score</th>
                                        <th>% Score</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($attempts as $attempt)
                                        <tr>
                                            <td>
                                                {{ ($attempts->currentPage() - 1) * $attempts->perPage() + $loop->iteration }}
                                            </td>
                                            <td>
                                                <div class="fw-semibold">{{ $attempt->candidate?->name ?? 'Candidate' }}</div>
                                                <div class="text-secondary small">{{ $attempt->candidate?->email }}</div>
                                            </td>
                                            <td>
                                                @php
                                                    $statusColors = [
                                                        \App\Models\ExamAttempt::STATUS_PUBLISHED => 'green',
                                                        \App\Models\ExamAttempt::STATUS_SUBMITTED => 'yellow',
                                                        \App\Models\ExamAttempt::STATUS_CANCELED => 'red',
                                                        \App\Models\ExamAttempt::STATUS_RETAKE => 'orange',
                                                    ];
                                                @endphp
                                                <span class="badge bg-{{ $statusColors[$attempt->status] ?? 'secondary' }} text-white">
                                                    {{ \Illuminate\Support\Str::headline($attempt->status) }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="fw-semibold">{{ $attempt->exam?->title ?? 'Exam' }}</div>
                                            </td>
                                            <td>
                                                @php
                                                    $subjectScores = collect($attempt->subject_scores ?? $attempt->computed_subject_scores ?? []);
                                                    $subjectCollection = $attempt->exam?->subjects ?? collect();
                                                @endphp
                                                @if ($subjectScores->isNotEmpty())
                                                    <div class="d-flex flex-column gap-1">
                                                        @foreach ($subjectScores as $score)
                                                            <div class="d-flex flex-wrap gap-1">
                                                                <span class="badge bg-indigo-lt text-indigo">
                                                                    {{ $score['subject_code'] ?? 'GEN' }}
                                                                </span>
                                                                <span class="badge bg-green-lt text-green">
                                                                    {{ number_format((float) ($score['total_score'] ?? 0), 2) }}
                                                                    / {{ number_format((float) ($score['max_marks'] ?? 0), 2) }}
                                                                </span>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @elseif ($subjectCollection->isNotEmpty())
                                                    <div class="d-flex flex-column gap-1">
                                                        @foreach ($subjectCollection as $subject)
                                                            <span class="badge bg-blue-lt">
                                                                {{ $subject->code ?? $subject->name }}
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <span class="text-secondary">No subjects</span>
                                                @endif
                                            </td>
                                            <td>
                                                @php
                                                    $attemptScore = $attempt->total_score ?? $attempt->score ?? $attempt->auto_score ?? 0;
                                                    $displayTotal = (float) ($attempt->computed_total_marks ?? 0);
                                                    if ($displayTotal <= 0) {
                                                        $displayTotal = (float) ($attempt->exam?->questions_sum_marks ?? 0);
                                                    }
                                                    if ($displayTotal <= 0) {
                                                        $displayTotal = (float) ($attempt->exam?->total_marks ?? 0);
                                                    }
                                                    if ($displayTotal <= 0) {
                                                        $displayTotal = (float) collect($attempt->subject_scores ?? $attempt->computed_subject_scores ?? [])
                                                            ->sum('max_marks');
                                                    }
                                                    $percentage = $displayTotal > 0
                                                        ? round(((float) $attemptScore / $displayTotal) * 100, 2)
                                                        : 0;
                                                @endphp
                                                {{ number_format((float) $attemptScore, 2) }}
                                                / {{ number_format((float) ($displayTotal ?? 0), 2) }}
                                            </td>
                                            <td>
                                                {{ number_format((float) ($percentage ?? 0), 2) }}%
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-secondary">No attempts found for the selected exam.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer">
                            {{ $attempts->links() }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

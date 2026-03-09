@extends('admin.layouts.master')

@section('content')
    <div class="page-wrapper">
        <div class="page-header d-print-none">
            <div class="container-xl d-flex justify-content-between align-items-center">
                <div>
                    <div class="page-pretitle">Attempts</div>
                    <h2 class="page-title">{{ $exam->title }}</h2>
                    <p class="text-secondary mb-0">Objective attempts for this exam.</p>
                </div>
                <div class="btn-list">
                    <a href="{{ route('admin.attempts.index') }}" class="btn btn-outline-secondary">
                        Back to exams
                    </a>
                </div>
            </div>
        </div>

        <div class="page-body">
            <div class="container-xl">
                @php
                    $cardThemes = [
                        'bg-primary text-white',
                        'bg-success text-white',
                        'bg-warning text-white',
                        'bg-info text-white',
                    ];
                @endphp
                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="card {{ $cardThemes[0] }}">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <div class="text-uppercase small">Candidates</div>
                                        <div class="h2 mb-0">{{ number_format((int) ($exam->candidate_target_count ?? 0)) }}</div>
                                    </div>
                                    <span class="avatar bg-white text-primary">
                                        <i class="ti ti-users"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="card {{ $cardThemes[1] }}">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <div class="text-uppercase small">Taken</div>
                                        <div class="h2 mb-0">{{ number_format((int) ($exam->attempts_count ?? 0)) }}</div>
                                    </div>
                                    <span class="avatar bg-white text-success">
                                        <i class="ti ti-checks"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="card {{ $cardThemes[2] }}">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <div class="text-uppercase small">Subjects</div>
                                        <div class="h2 mb-0">{{ number_format((int) ($exam->subjects_count ?? 0)) }}</div>
                                    </div>
                                    <span class="avatar bg-white text-warning">
                                        <i class="ti ti-books"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="card {{ $cardThemes[3] }}">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <div class="text-uppercase small">Total Questions</div>
                                        <div class="h2 mb-0">{{ number_format((int) ($exam->questions_count ?? 0)) }}</div>
                                    </div>
                                    <span class="avatar bg-white text-info">
                                        <i class="ti ti-list-numbers"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    @foreach ($statusOptions as $attemptStatus)
                        @php
                            $statusThemeMap = [
                                \App\Models\ExamAttempt::STATUS_SUBMITTED => 'bg-primary text-white',
                                \App\Models\ExamAttempt::STATUS_PUBLISHED => 'bg-success text-white',
                                \App\Models\ExamAttempt::STATUS_CANCELED => 'bg-danger text-white',
                                \App\Models\ExamAttempt::STATUS_RETAKE => 'bg-warning text-white',
                            ];
                            $statusIconMap = [
                                \App\Models\ExamAttempt::STATUS_SUBMITTED => 'ti ti-send',
                                \App\Models\ExamAttempt::STATUS_PUBLISHED => 'ti ti-checks',
                                \App\Models\ExamAttempt::STATUS_CANCELED => 'ti ti-ban',
                                \App\Models\ExamAttempt::STATUS_RETAKE => 'ti ti-refresh',
                            ];
                            $statusTheme = $statusThemeMap[$attemptStatus] ?? 'bg-secondary text-white';
                            $statusIcon = $statusIconMap[$attemptStatus] ?? 'ti ti-circle';
                        @endphp
                        <div class="col-6 col-md-3">
                            <div class="card {{ $statusTheme }}">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <div class="text-uppercase small">{{ \Illuminate\Support\Str::headline($attemptStatus) }}</div>
                                            <div class="h2 mb-0">{{ $statusCounts[$attemptStatus] ?? 0 }}</div>
                                        </div>
                                        <span class="avatar bg-white text-dark">
                                            <i class="{{ $statusIcon }}"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <form method="GET" class="card mb-4">
                    <div class="card-body row g-3 align-items-end">
                        <div class="col-md-4">
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
                        <div class="col-md-4">
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
                                    <th>Subjects</th>
                                    <th>Total Score</th>
                                    <th>% Score</th>
                                    <th class="w-1">Action</th>
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
                                            <span class="badge bg-azure-lt text-azure">{{ $attempt->candidate?->email }}</span>
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
                                            @php
                                                $subjectScores = collect($attempt->subject_scores ?? $attempt->computed_subject_scores ?? []);
                                            @endphp
                                            @if ($subjectScores->isNotEmpty())
                                                <div class="d-flex flex-column gap-1">
                                                    @foreach ($subjectScores as $score)
                                                        <div class="d-flex flex-wrap gap-1">
                                                            <span class="badge bg-indigo-lt text-indigo">
                                                                {{ $score['subject_code'] ?? 'GEN' }}
                                                                ({{ number_format((float) ($score['total_score'] ?? 0), 2) }}
                                                                / {{ number_format((float) ($score['max_marks'] ?? 0), 2) }})
                                                            </span>
                                                        </div>
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
                                        <td class="text-end">
                                            <a href="{{ route('admin.attempts.answers.show', [$attempt->exam, $attempt]) }}" class="btn btn-primary">Manage Attempt</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-secondary">No attempts found for this exam.</td>
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

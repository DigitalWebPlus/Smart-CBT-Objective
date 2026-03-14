@extends('admin.layouts.master')

@section('content')
    <div class="page-wrapper">
        <div class="page-header d-print-none">
            <div class="container-xl d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="page-title">Live Exam Monitoring</h2>
                    <p class="text-secondary mb-0">Track candidates in real time while exams are in progress.</p>
                </div>
            </div>
        </div>

        <div class="page-body">
            <div class="container-xl">
                <form method="GET" class="card bg-azure-lt mb-4">
                    <div class="card-body row g-3 align-items-end">
                        <div class="col-md-5">
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
                        <div class="col-md-5">
                            <label class="form-label">Candidate</label>
                            <input type="text" name="candidate" class="form-control" value="{{ $candidateQuery }}"
                                placeholder="Search name, email, registration">
                        </div>
                        <div class="col-md-2 text-end">
                            <button class="btn btn-primary w-100" type="submit">Filter</button>
                        </div>
                    </div>
                </form>

                <div class="row row-cards mb-4">
                    <div class="col-sm-6 col-lg-3">
                        <div class="card bg-blue-lt">
                            <div class="card-body">
                                <div class="text-secondary">In Progress</div>
                                <div class="h2 mb-0">{{ number_format($monitorStats['in_progress_total']) }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="card bg-indigo-lt">
                            <div class="card-body">
                                <div class="text-secondary">Active Exams</div>
                                <div class="h2 mb-0">{{ number_format($monitorStats['active_exams']) }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="card bg-green-lt">
                            <div class="card-body">
                                <div class="text-secondary">Active Candidates</div>
                                <div class="h2 mb-0">{{ number_format($monitorStats['active_candidates']) }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="card bg-orange-lt">
                            <div class="card-body">
                                <div class="text-secondary">Avg. Logins / Attempt</div>
                                <div class="h2 mb-0">{{ number_format($monitorStats['average_logins'], 1) }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                @php
                    $now = now();
                @endphp

                <div class="card bg-purple-lt">
                    <div class="card-header">
                        <h3 class="card-title">Live Attempt Summaries</h3>
                        <div class="card-subtitle text-secondary">{{ number_format($attempts->total()) }} attempt(s) matched current filters</div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th>Exam</th>
                                    <th>Candidate</th>
                                    <th>Progress</th>
                                    <th>Timing</th>
                                    <th>Access</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($attempts as $attempt)
                                    @php
                                        $totalQuestions = (int) ($attempt->exam?->questions_count ?? 0);
                                        $answeredCount = (int) ($attempt->responses_count ?? 0);
                                        $rawProgress = $totalQuestions > 0
                                            ? (int) round(($answeredCount / $totalQuestions) * 100)
                                            : 0;
                                        $progress = max(0, min($rawProgress, 100));

                                        $startedAt = $attempt->started_at;
                                        $durationMinutes = (int) ($attempt->exam?->duration_minutes ?? 0);
                                        $elapsedMinutes = $startedAt ? $startedAt->diffInMinutes($now) : 0;
                                        $remainingMinutes = max($durationMinutes - $elapsedMinutes, 0);
                                        $expectedEnd = $startedAt && $durationMinutes > 0
                                            ? $startedAt->copy()->addMinutes($durationMinutes)
                                            : null;

                                        $loginIpCount = is_array($attempt->login_ips)
                                            ? count(array_filter($attempt->login_ips))
                                            : 0;
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $attempt->exam?->title ?? 'Exam' }}</div>
                                            <div class="text-secondary small">ID: {{ $attempt->exam_id }}</div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold">{{ $attempt->candidate?->name ?? 'Candidate' }}</div>
                                            <div class="text-secondary small">{{ $attempt->candidate?->email }}</div>
                                            <div class="text-secondary small">
                                                Reg: {{ $attempt->candidate?->registration_number ?? '—' }}
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-between small mb-1">
                                                <span>{{ $answeredCount }}/{{ $totalQuestions }}</span>
                                                <span>{{ $progress }}%</span>
                                            </div>
                                            <div class="progress progress-sm">
                                                <div class="progress-bar" role="progressbar" style="width: {{ $progress }}%"
                                                    aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold">Started: {{ $startedAt?->format('M d, h:i a') ?? '—' }}</div>
                                            <div class="text-secondary small">Elapsed: {{ $startedAt ? $startedAt->diffForHumans($now, true) : '—' }}</div>
                                            <div class="text-secondary small">
                                                Remaining:
                                                @if ($durationMinutes > 0)
                                                    {{ $remainingMinutes }} min
                                                    @if ($expectedEnd)
                                                        (ends {{ $expectedEnd->format('h:i a') }})
                                                    @endif
                                                @else
                                                    Untimed
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold">Logins: {{ (int) ($attempt->login_count ?? 0) }}</div>
                                            <div class="text-secondary small">IP addresses: {{ $loginIpCount }}</div>
                                        </td>
                                        <td>
                                            <span class="badge bg-warning text-white">
                                                {{ \Illuminate\Support\Str::headline($attempt->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-secondary">No in-progress attempts found.</td>
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

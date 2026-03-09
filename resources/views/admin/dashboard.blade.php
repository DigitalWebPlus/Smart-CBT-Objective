@extends('admin.layouts.master')

@section('content')
    <div class="page-wrapper">
        <div class="page-header d-print-none">
            <div class="container-xl">
                <div class="row align-items-center g-3">
                    <div class="col-auto">
                        @php($siteLogo = config('settings.site_logo'))
                        @php($siteName = config('settings.site_name', config('app.name', 'CBT Objective')))
                        @if ($siteLogo)
                            <img src="{{ asset($siteLogo) }}" alt="{{ $siteName }} logo"
                                style="width: 64px; height: 64px; object-fit: contain;" class="bg-white rounded shadow-sm p-1">
                        @else
                            <span class="avatar avatar-lg bg-white shadow-sm">
                                {{ strtoupper(substr($siteName, 0, 2)) }}
                            </span>
                        @endif
                    </div>
                    <div class="col">
                        <div class="page-pretitle">Admin Home</div>
                        <h2 class="page-title">Executive Control Center</h2>
                        <div class="text-secondary">Command overview for exams, candidates, and operations</div>
                    </div>
                    <div class="col-auto text-secondary">
                        Updated {{ $generatedAt->format('M d, Y h:i a') }}
                    </div>
                </div>
            </div>
        </div>

        <div class="page-body">
            <div class="container-xl">
                <div class="row row-cards mb-3">
                    <div class="col-sm-6 col-lg-2">
                        <div class="card bg-blue text-white">
                            <div class="card-body">
                                <div class="text-white text-opacity-75">Candidates</div>
                                <div class="h2 mb-0 text-white">{{ number_format($kpis['candidates']) }}</div>
                                <div class="text-white text-opacity-75 small">+{{ number_format($kpis['new_candidates_30d']) }} in last 30 days</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-2">
                        <div class="card bg-azure text-white">
                            <div class="card-body">
                                <div class="text-white text-opacity-75">Active Exams</div>
                                <div class="h2 mb-0 text-white">{{ number_format($kpis['active_exams']) }}</div>
                                <div class="text-white text-opacity-75 small">{{ number_format($kpis['exams']) }} total exams</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-2">
                        <div class="card bg-indigo text-white">
                            <div class="card-body">
                                <div class="text-white text-opacity-75">In Progress Attempts</div>
                                <div class="h2 mb-0 text-white">{{ number_format($kpis['in_progress_attempts']) }}</div>
                                <div class="text-white text-opacity-75 small">{{ number_format($kpis['attempts']) }} total attempts</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-2">
                        <div class="card bg-green text-white">
                            <div class="card-body">
                                <div class="text-white text-opacity-75">Completion Rate</div>
                                <div class="h2 mb-0 text-white">{{ number_format($kpis['completion_rate'], 1) }}%</div>
                                <div class="progress progress-sm mt-2">
                                    <div class="progress-bar bg-white" style="width: {{ min(100, max(0, $kpis['completion_rate'])) }}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-2">
                        <div class="card bg-lime text-white">
                            <div class="card-body">
                                <div class="text-white text-opacity-75">Pass Rate</div>
                                <div class="h2 mb-0 text-white">{{ number_format($kpis['pass_rate'], 1) }}%</div>
                                <div class="text-white text-opacity-75 small">based on scored attempts</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-2">
                        <div class="card bg-orange text-white">
                            <div class="card-body">
                                <div class="text-white text-opacity-75">Open Workload</div>
                                <div class="h2 mb-0 text-white">{{ number_format($kpis['open_tickets']) }}</div>
                                <div class="text-white text-opacity-75 small">{{ number_format($kpis['pending_reviews']) }} submitted awaiting review</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row row-cards mb-3">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Attempt & Submission Trend (Last 7 Days)</h3>
                            </div>
                            <div class="card-body">
                                <canvas id="attemptTrendChart" height="120"></canvas>
                                <div class="mt-3 d-flex flex-wrap gap-3 small text-secondary">
                                    <span><i class="ti ti-circle-filled text-blue"></i> Created attempts</span>
                                    <span><i class="ti ti-circle-filled text-green"></i> Submitted attempts</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Quick Actions</h3>
                            </div>
                            <div class="card-body d-grid gap-2">
                                @foreach ($quickActions as $action)
                                    <a href="{{ $action['route'] }}" class="btn btn-outline-primary">{{ $action['label'] }}</a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                @php($totalStatus = max(array_sum($charts['exam_status']), 1))

                <div class="row row-cards mb-3">
                    <div class="col-lg-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h3 class="card-title">Exam Status Mix</h3>
                            </div>
                            <div class="card-body">
                                @foreach ($charts['exam_status'] as $status => $count)
                                    @php($percent = round(($count / $totalStatus) * 100, 1))
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between small mb-1">
                                            <span>{{ \Illuminate\Support\Str::headline($status) }}</span>
                                            <span>{{ number_format($count) }} ({{ $percent }}%)</span>
                                        </div>
                                        <div class="progress progress-sm">
                                            <div class="progress-bar" style="width: {{ $percent }}%"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-8">
                        <div class="card h-100">
                            <div class="card-header">
                                <h3 class="card-title">Recent Activity</h3>
                            </div>
                            <div class="list-group list-group-flush list-group-hoverable">
                                @forelse ($recentActivity as $activity)
                                    <div class="list-group-item">
                                        <div class="row align-items-center">
                                            <div class="col text-truncate">
                                                <div class="text-body d-block">{{ $activity['title'] }}</div>
                                                <div class="d-block text-secondary text-truncate mt-n1">{{ \Illuminate\Support\Str::headline($activity['description']) }}</div>
                                            </div>
                                            <div class="col-auto text-secondary small">{{ optional($activity['timestamp'])->diffForHumans() }}</div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="list-group-item text-secondary">No recent activity yet.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row row-cards">
                    <div class="col-lg-6">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h3 class="card-title">Pending Ticket Replies</h3>
                                <a href="{{ route('admin.support-tickets.index') }}" class="btn btn-sm btn-outline-secondary">View all</a>
                            </div>
                            <div class="list-group list-group-flush">
                                @forelse ($pendingTickets as $ticket)
                                    <a href="{{ route('admin.support-tickets.show', $ticket) }}" class="list-group-item list-group-item-action">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <div class="fw-semibold">{{ $ticket->subject }}</div>
                                                <div class="text-secondary small">{{ $ticket->candidate?->name ?? 'Candidate' }}</div>
                                            </div>
                                            <div class="text-end">
                                                <span class="badge bg-orange-lt text-orange">{{ \Illuminate\Support\Str::headline($ticket->status) }}</span>
                                                <div class="text-secondary small mt-1">{{ optional($ticket->last_message_at)->diffForHumans() ?? 'No activity' }}</div>
                                            </div>
                                        </div>
                                    </a>
                                @empty
                                    <div class="list-group-item text-secondary">No pending tickets.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h3 class="card-title">Submitted Attempts Awaiting Review</h3>
                                <a href="{{ route('admin.attempts.index') }}" class="btn btn-sm btn-outline-secondary">View all</a>
                            </div>
                            <div class="list-group list-group-flush">
                                @forelse ($pendingReviews as $attempt)
                                    @if ($attempt->exam)
                                        <a href="{{ route('admin.monitor-exams.attempts.show', [$attempt->exam, $attempt]) }}"
                                            class="list-group-item list-group-item-action">
                                            <div class="d-flex justify-content-between">
                                                <div>
                                                    <div class="fw-semibold">{{ $attempt->exam?->title ?? 'Exam' }}</div>
                                                    <div class="text-secondary small">{{ $attempt->candidate?->name ?? 'Candidate' }}</div>
                                                </div>
                                                <div class="text-secondary small text-end">
                                                    {{ optional($attempt->submitted_at)->diffForHumans() ?? 'No submission time' }}
                                                </div>
                                            </div>
                                        </a>
                                    @else
                                        <div class="list-group-item">
                                            <div class="d-flex justify-content-between">
                                                <div>
                                                    <div class="fw-semibold">Exam unavailable</div>
                                                    <div class="text-secondary small">{{ $attempt->candidate?->name ?? 'Candidate' }}</div>
                                                </div>
                                                <div class="text-secondary small text-end">
                                                    {{ optional($attempt->submitted_at)->diffForHumans() ?? 'No submission time' }}
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @empty
                                    <div class="list-group-item text-secondary">No submitted attempts waiting review.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const canvas = document.getElementById('attemptTrendChart');
            if (!canvas) {
                return;
            }

            const labels = @json($charts['attempt_trend_labels']);
            const createdSeries = @json($charts['attempt_trend_series']);
            const submittedSeries = @json($charts['submission_trend_series']);

            const context = canvas.getContext('2d');
            const styles = getComputedStyle(document.documentElement);
            const colorPrimary = styles.getPropertyValue('--tblr-primary').trim() || 'currentColor';
            const colorSuccess = styles.getPropertyValue('--tblr-success').trim() || 'currentColor';
            const colorBorder = styles.getPropertyValue('--tblr-border-color').trim() || 'currentColor';
            const width = canvas.width = canvas.offsetWidth;
            const height = canvas.height = 220;
            const padding = 26;
            const maxValue = Math.max(1, ...createdSeries, ...submittedSeries);

            const toX = (index) => {
                if (labels.length <= 1) {
                    return padding;
                }

                return padding + (index * (width - (padding * 2))) / (labels.length - 1);
            };

            const toY = (value) => height - padding - (value / maxValue) * (height - (padding * 2));

            context.clearRect(0, 0, width, height);
            context.lineWidth = 1;
            context.strokeStyle = colorBorder;

            for (let i = 0; i <= 4; i++) {
                const y = padding + (i * (height - (padding * 2))) / 4;
                context.beginPath();
                context.moveTo(padding, y);
                context.lineTo(width - padding, y);
                context.stroke();
            }

            const drawSeries = (series, color) => {
                context.beginPath();
                context.lineWidth = 2;
                context.strokeStyle = color;

                series.forEach((value, index) => {
                    const x = toX(index);
                    const y = toY(value);
                    if (index === 0) {
                        context.moveTo(x, y);
                    } else {
                        context.lineTo(x, y);
                    }
                });

                context.stroke();

                series.forEach((value, index) => {
                    const x = toX(index);
                    const y = toY(value);
                    context.fillStyle = color;
                    context.beginPath();
                    context.arc(x, y, 3, 0, Math.PI * 2);
                    context.fill();
                });
            };

            drawSeries(createdSeries, colorPrimary);
            drawSeries(submittedSeries, colorSuccess);
        });
    </script>
@endpush

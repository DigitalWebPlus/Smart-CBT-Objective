@extends('admin.layouts.master')

@section('content')
    <div class="page-wrapper">
        <div class="page-header d-print-none">
            <div class="container-xl d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="page-title">Admin Logs</h2>
                    <p class="text-secondary mb-0">Review authentication and administrative actions across the platform.</p>
                </div>
            </div>
        </div>

        <div class="page-body">
            <div class="container-xl">
                <div class="row row-cards mb-4">
                    <div class="col-sm-6 col-lg-3">
                        <div class="card bg-blue-lt">
                            <div class="card-body">
                                <div class="text-secondary">Today</div>
                                <div class="h2 mb-0">{{ number_format($stats['today']) }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="card bg-indigo-lt">
                            <div class="card-body">
                                <div class="text-secondary">Last 7 Days</div>
                                <div class="h2 mb-0">{{ number_format($stats['last_7_days']) }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="card bg-orange-lt">
                            <div class="card-body">
                                <div class="text-secondary">Failed Logins Today</div>
                                <div class="h2 mb-0">{{ number_format($stats['failed_logins_today']) }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="card bg-red-lt">
                            <div class="card-body">
                                <div class="text-secondary">Candidate Actions Today</div>
                                <div class="h2 mb-0">{{ number_format($stats['candidate_actions_today']) }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row row-cards mb-4">
                    <div class="col-sm-12 col-lg-3">
                        <div class="card bg-red-lt">
                            <div class="card-body">
                                <div class="text-secondary">Critical Actions Today</div>
                                <div class="h2 mb-0">{{ number_format($stats['critical_actions_today']) }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <form method="GET" class="card bg-azure-lt mb-4">
                    <div class="card-body row g-3 align-items-end">
                        <div class="col-md-2">
                            <label class="form-label">Actor Type</label>
                            <select name="actor_type" class="form-select">
                                <option value="" @selected($filters['actor_type'] === '')>All</option>
                                <option value="admin" @selected($filters['actor_type'] === 'admin')>Admin</option>
                                <option value="candidate" @selected($filters['actor_type'] === 'candidate')>Candidate</option>
                                <option value="system" @selected($filters['actor_type'] === 'system')>System</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Action</label>
                            <input type="text" name="action" class="form-control" value="{{ $filters['action'] }}"
                                placeholder="e.g. candidate.exams">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Admin</label>
                            <select name="admin_id" class="form-select">
                                <option value="">All admins</option>
                                @foreach ($admins as $admin)
                                    <option value="{{ $admin->id }}" @selected((int) $filters['admin_id'] === (int) $admin->id)>
                                        {{ $admin->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Candidate</label>
                            <select name="candidate_id" class="form-select">
                                <option value="">All candidates</option>
                                @foreach ($candidates as $candidate)
                                    <option value="{{ $candidate->id }}" @selected((int) $filters['candidate_id'] === (int) $candidate->id)>
                                        {{ $candidate->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">From</label>
                            <input type="date" name="from" class="form-control" value="{{ $filters['from'] }}">
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">To</label>
                            <input type="date" name="to" class="form-control" value="{{ $filters['to'] }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Search JSON</label>
                            <input type="text" name="search" class="form-control" value="{{ $filters['search'] }}"
                                placeholder="ip, route, email...">
                        </div>
                        <div class="col-md-2 text-end">
                            <button class="btn btn-primary w-100" type="submit">Go</button>
                        </div>
                    </div>
                </form>

                <div class="card mb-4">
                    <div class="card-body py-2 d-flex flex-wrap align-items-center gap-2">
                        <span class="text-secondary small">Actor Legend:</span>
                        <span class="badge bg-blue-lt">Admin</span>
                        <span class="badge bg-green-lt">Candidate</span>
                        <span class="badge bg-secondary-lt">System</span>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Log Entries</h3>
                        <div class="d-flex align-items-center gap-2 ms-auto">
                            <div class="card-subtitle text-secondary">{{ number_format($logs->total()) }} record(s)</div>
                            <form method="POST" action="{{ route('admin.logs.toggle') }}" data-swal-confirm
                                data-swal-title="{{ $logsEnabled ? 'Disable logging?' : 'Enable logging?' }}"
                                data-swal-confirm="{{ $logsEnabled ? 'New actions will no longer be recorded until logging is turned back on.' : 'New actions will start being recorded.' }}"
                                data-swal-icon="warning"
                                data-swal-confirm-button="{{ $logsEnabled ? 'Yes, disable' : 'Yes, enable' }}"
                                data-swal-cancel-button="Cancel">
                                @csrf
                                <button type="submit" class="btn btn-sm {{ $logsEnabled ? 'btn-warning' : 'btn-success' }}">
                                    {{ $logsEnabled ? 'Turn Off Log' : 'Turn On Log' }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.logs.reset') }}" data-swal-confirm
                                data-swal-title="Reset all logs?"
                                data-swal-confirm="This will permanently delete all log records. This action cannot be undone."
                                data-swal-icon="warning"
                                data-swal-confirm-button="Yes, reset logs"
                                data-swal-cancel-button="Cancel">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-danger">Reset Logs</button>
                            </form>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Action</th>
                                    <th>Actor</th>
                                    <th>Email</th>
                                    <th>Method</th>
                                    <th>Path</th>
                                    <th>Status</th>
                                    <th class="w-1"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($logs as $log)
                                    @php
                                        $meta = $log->metadata ?? [];
                                        $statusCode = (int) ($meta['status_code'] ?? 0);
                                        $fallbackEmail = $meta['email'] ?? $meta['candidate_email'] ?? $meta['admin_email'] ?? null;
                                        $statusText = null;

                                        if ($statusCode > 0) {
                                            $statusText = (string) $statusCode;
                                        } elseif (str_contains($log->action, '.failed')) {
                                            $statusText = 'Failed';
                                        } elseif (str_contains($log->action, '.success')) {
                                            $statusText = 'Success';
                                        } elseif (str_contains($log->action, '.logout')) {
                                            $statusText = 'Completed';
                                        }
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $log->created_at?->format('M d, Y h:i:s a') }}</div>
                                            <div class="text-secondary small">{{ $log->created_at?->diffForHumans() }}</div>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary-lt text-blue">{{ $log->action }}</span>
                                        </td>
                                        <td>
                                            @if ($log->admin)
                                                <div class="fw-semibold">{{ $log->admin->name }} <span class="badge bg-blue-lt">Admin</span></div>
                                            @elseif ($log->candidate)
                                                <div class="fw-semibold">{{ $log->candidate->name }} <span class="badge bg-green-lt">Candidate</span></div>
                                            @else
                                                <div class="fw-semibold">System/Guest</div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="text-secondary small">
                                                {{ $log->admin?->email ?? $log->candidate?->email ?? $fallbackEmail ?? '—' }}
                                            </div>
                                        </td>
                                        <td>{{ strtoupper((string) ($meta['method'] ?? '—')) }}</td>
                                        <td>
                                            <div class="text-truncate" style="max-width: 260px;">
                                                {{ $meta['path'] ?? '—' }}
                                            </div>
                                        </td>
                                        <td>
                                            @if ($statusCode > 0)
                                                <span class="badge bg-{{ $statusCode >= 400 ? 'red' : 'green' }}-lt">{{ $statusText }}</span>
                                            @elseif ($statusText === 'Failed')
                                                <span class="badge bg-red-lt">{{ $statusText }}</span>
                                            @elseif ($statusText === 'Success' || $statusText === 'Completed')
                                                <span class="badge bg-green-lt">{{ $statusText }}</span>
                                            @else
                                                <span class="text-secondary">—</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('admin.logs.show', $log) }}" class="btn btn-sm btn-outline-secondary">View</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-secondary py-4">No logs found for the selected filters.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer">
                        {{ $logs->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

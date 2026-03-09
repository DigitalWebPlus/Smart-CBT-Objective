@extends('admin.layouts.master')

@section('content')
    <div class="page-wrapper">
        <div class="page-header d-print-none">
            <div class="container-xl d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="page-title">Log Details</h2>
                    <p class="text-secondary mb-0">Inspect one admin action record with full metadata.</p>
                </div>
                <a href="{{ route('admin.logs.index') }}" class="btn btn-outline-secondary">Back to Logs</a>
            </div>
        </div>

        <div class="page-body">
            <div class="container-xl">
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="card-title">Summary</h3>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="text-secondary small">Action</div>
                                <div class="fw-semibold">{{ $log->action }}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-secondary small">Time</div>
                                <div class="fw-semibold">{{ $log->created_at?->format('M d, Y h:i:s a') }}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-secondary small">Actor</div>
                                @if ($log->admin)
                                    <div class="fw-semibold">{{ $log->admin->name }} (Admin)</div>
                                    <div class="text-secondary small">{{ $log->admin->email }}</div>
                                @elseif ($log->candidate)
                                    <div class="fw-semibold">{{ $log->candidate->name }} (Candidate)</div>
                                    <div class="text-secondary small">{{ $log->candidate->email }}</div>
                                    <div class="text-secondary small">Reg: {{ $log->candidate->registration_number ?? '—' }}</div>
                                @else
                                    <div class="fw-semibold">System/Guest</div>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <div class="text-secondary small">Log ID</div>
                                <div class="fw-semibold">#{{ $log->id }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Metadata</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th style="width: 220px;">Key</th>
                                    <th>Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($metadata as $key => $value)
                                    <tr>
                                        <td class="fw-semibold">{{ $key }}</td>
                                        <td>
                                            @if (is_array($value))
                                                <pre class="m-0"><code>{{ json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                                            @else
                                                {{ is_bool($value) ? ($value ? 'true' : 'false') : (string) $value }}
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-center text-secondary py-4">No metadata attached to this log entry.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

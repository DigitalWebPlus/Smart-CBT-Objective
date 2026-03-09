@extends('admin.layouts.master')

@section('content')
    <div class="page-wrapper">
        <div class="page-header d-print-none">
            <div class="container-xl">
                <div class="card border-0 shadow-sm">
                    <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                        <div>
                            <div class="page-pretitle">Support</div>
                            <h2 class="page-title mb-1">Support Tickets</h2>
                            <p class="text-secondary mb-0">Manage and respond to candidate requests.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="page-body">
            <div class="container-xl">
                <div class="card mb-4 border-0 shadow-sm">
                    <div class="card-body">
                        <form method="GET" class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="">All</option>
                                    @foreach (\App\Models\SupportTicket::STATUSES as $ticketStatus)
                                        <option value="{{ $ticketStatus }}" @selected($status === $ticketStatus)>
                                            {{ ucfirst($ticketStatus) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Priority</label>
                                <select name="priority" class="form-select">
                                    <option value="">All</option>
                                    @foreach (\App\Models\SupportTicket::PRIORITIES as $ticketPriority)
                                        <option value="{{ $ticketPriority }}" @selected($priority === $ticketPriority)>
                                            {{ ucfirst($ticketPriority) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 text-end">
                                <button class="btn btn-primary" type="submit">Filter</button>
                                <a href="{{ route('admin.support-tickets.index') }}" class="btn btn-outline-secondary">Reset</a>
                            </div>
                        </form>
                    </div>
                </div>

                @php
                    $priorityColors = ['low' => 'success', 'medium' => 'warning', 'high' => 'danger'];
                @endphp

                <div class="card border-0 shadow-sm">
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th>Candidate</th>
                                    <th>Subject</th>
                                    <th>Status</th>
                                    <th>Priority</th>
                                    <th>Last Message</th>
                                    <th class="text-end"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($tickets as $ticket)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $ticket->candidate?->name ?? 'Candidate' }}</div>
                                            <div class="text-secondary small">{{ $ticket->candidate?->email }}</div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold">{{ $ticket->subject }}</div>
                                            <div class="text-secondary small">#{{ $ticket->id }}</div>
                                        </td>
                                        <td>
                                            <span class="badge rounded-pill bg-{{ $ticket->status === 'closed' ? 'secondary' : ($ticket->status === 'pending' ? 'warning' : 'success') }}">
                                                {{ ucfirst($ticket->status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge rounded-pill bg-{{ $priorityColors[$ticket->priority] ?? 'secondary' }}">
                                                {{ ucfirst($ticket->priority) }}
                                            </span>
                                        </td>
                                        <td class="text-secondary">{{ optional($ticket->last_message_at ?? $ticket->updated_at)->diffForHumans() }}</td>
                                        <td class="text-end">
                                            <a href="{{ route('admin.support-tickets.show', $ticket) }}" class="btn btn-sm btn-outline-primary">View</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6">
                                            <div class="text-center py-5">
                                                <i class="bi bi-inbox fs-1 text-muted"></i>
                                                <p class="mt-3 mb-0 text-muted">No support tickets found.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer">
                        {{ $tickets->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

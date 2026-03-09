@extends('candidate.layouts.app')

@section('title', 'Support Tickets')

@section('content')
    <div class="container">
        <div class="card border-0 shadow-sm mb-4 bg-primary text-white">
            <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <p class="text-uppercase small text-white mb-1">Support</p>
                    <h1 class="h3 fw-bold mb-1">My Support Tickets</h1>
                    <p class="text-white mb-0">Track your requests and chat with the admin team.</p>
                </div>
                <a href="{{ route('candidate.support-tickets.create') }}" class="btn btn-light btn-lg">
                    <i class="bi bi-plus-circle me-2"></i>New Ticket
                </a>
            </div>
        </div>

        @php
            $ticketCollection = $tickets->getCollection();
            $openCount = $ticketCollection->whereIn('status', ['open', 'pending'])->count();
            $pendingCount = $ticketCollection->where('status', 'pending')->count();
            $closedCount = $ticketCollection->where('status', 'closed')->count();
            $priorityColors = ['low' => 'success', 'medium' => 'warning', 'high' => 'danger'];
        @endphp

        <div class="row g-4">
            <div class="col-lg-4">
                @include('candidate.partials.profile-card', ['candidate' => auth()->user()])
            </div>

            <div class="col-lg-8">
                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <p class="text-uppercase small text-muted mb-1">Open Tickets</p>
                                <div class="d-flex align-items-center justify-content-between">
                                    <h3 class="fw-bold mb-0">{{ $openCount }}</h3>
                                    <span class="badge bg-success text-white">Active</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <p class="text-uppercase small text-muted mb-1">Pending Replies</p>
                                <div class="d-flex align-items-center justify-content-between">
                                    <h3 class="fw-bold mb-0">{{ $pendingCount }}</h3>
                                    <span class="badge bg-warning text-dark">Waiting</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <p class="text-uppercase small text-muted mb-1">Closed Tickets</p>
                                <div class="d-flex align-items-center justify-content-between">
                                    <h3 class="fw-bold mb-0">{{ $closedCount }}</h3>
                                    <span class="badge bg-secondary text-white">Resolved</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="table-responsive">
                        <table class="table table-vcenter mb-0">
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th>Status</th>
                                    <th>Priority</th>
                                    <th>Last Update</th>
                                    <th class="text-end"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($tickets as $ticket)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $ticket->subject }}</div>
                                            <div class="text-muted small">#{{ $ticket->id }}</div>
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
                                        <td class="text-muted">{{ optional($ticket->last_message_at ?? $ticket->updated_at)->diffForHumans() }}</td>
                                        <td class="text-end">
                                            <a href="{{ route('candidate.support-tickets.show', $ticket) }}" class="btn btn-sm btn-outline-primary">
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5">
                                            <div class="text-center py-5">
                                                <i class="bi bi-life-preserver fs-1 text-muted"></i>
                                                <p class="mt-3 mb-0 text-muted">No support tickets yet.</p>
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

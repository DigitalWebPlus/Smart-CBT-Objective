@extends('admin.layouts.master')

@section('content')
    <div class="page-wrapper">
        <div class="page-header d-print-none">
            <div class="container-xl">
                <div class="card border-0 shadow-sm">
                    <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                        <div>
                            <div class="page-pretitle">Support</div>
                            <h2 class="page-title mb-1">{{ $ticket->subject }}</h2>
                            <div class="d-flex flex-wrap gap-2 align-items-center text-secondary">
                                <span class="badge rounded-pill bg-{{ $ticket->status === 'closed' ? 'secondary' : ($ticket->status === 'pending' ? 'warning' : 'success') }}">
                                    {{ ucfirst($ticket->status) }}
                                </span>
                                <span class="badge rounded-pill bg-info text-dark text-capitalize">Priority: {{ $ticket->priority }}</span>
                                <span class="small">Candidate: {{ $ticket->candidate?->name }}</span>
                                <span class="small">Ticket #{{ $ticket->id }}</span>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('admin.support-tickets.index') }}" class="btn btn-outline-secondary">Back</a>
                            @if ($ticket->isClosed())
                                <form method="POST" action="{{ route('admin.support-tickets.reopen', $ticket) }}">
                                    @csrf
                                    <button class="btn btn-outline-success" type="submit">Reopen</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.support-tickets.close', $ticket) }}">
                                    @csrf
                                    <button class="btn btn-outline-danger" type="submit">Close</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="page-body">
            <div class="container-xl">
                <div class="card mb-4 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="vstack gap-3">
                            @forelse ($ticket->messages as $message)
                                <div class="row g-0">
                                    <div class="col-12 col-lg-9 {{ $message->sender_type === 'admin' ? 'ms-lg-auto' : '' }}">
                                        <div class="p-3 rounded-4 border shadow-sm {{ $message->sender_type === 'admin' ? 'bg-primary text-white' : 'bg-white' }}">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <strong>{{ $message->sender_type === 'admin' ? 'Admin' : 'Candidate' }}</strong>
                                                <span class="small {{ $message->sender_type === 'admin' ? 'text-white-50' : 'text-muted' }}">{{ $message->created_at->diffForHumans() }}</span>
                                            </div>
                                            <p class="mb-0">{{ $message->message }}</p>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-4">
                                    <i class="bi bi-chat-square-text fs-2 text-muted"></i>
                                    <p class="mt-2 mb-0 text-muted">No messages yet.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                @if (! $ticket->isClosed())
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <form method="POST" action="{{ route('admin.support-tickets.reply', $ticket) }}" class="vstack gap-3">
                                @csrf
                                <div>
                                    <label class="form-label fw-semibold">Reply</label>
                                    <textarea name="message" rows="4" class="form-control" placeholder="Write a response" required>{{ old('message') }}</textarea>
                                    @error('message')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="d-flex justify-content-end">
                                    <button class="btn btn-primary" type="submit">Send Reply</button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

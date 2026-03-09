@extends('candidate.layouts.app')

@section('title', 'Support Ticket')

@section('content')
    <div class="container">
        <div class="card border-0 shadow-sm mb-4 bg-primary text-white">
            <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <p class="text-uppercase small text-white mb-1">Support</p>
                    <h1 class="h3 fw-bold mb-1">{{ $ticket->subject }}</h1>
                    <div class="d-flex flex-wrap gap-2 align-items-center text-white">
                        <span class="badge rounded-pill bg-{{ $ticket->status === 'closed' ? 'secondary' : ($ticket->status === 'pending' ? 'warning' : 'success') }}">
                            {{ ucfirst($ticket->status) }}
                        </span>
                        <span class="badge rounded-pill bg-info text-dark text-capitalize">Priority: {{ $ticket->priority }}</span>
                        <span class="small">Ticket #{{ $ticket->id }}</span>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('candidate.support-tickets.index') }}" class="btn btn-outline-light">Back</a>
                    @if (! $ticket->isClosed())
                        <form method="POST" action="{{ route('candidate.support-tickets.close', $ticket) }}">
                            @csrf
                            <button class="btn btn-outline-danger" type="submit">Close Ticket</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-4">
                @include('candidate.partials.profile-card', ['candidate' => $ticket->candidate ?? auth()->user()])
            </div>

            <div class="col-lg-8">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <div class="vstack gap-3">
                            @forelse ($ticket->messages as $message)
                                <div class="row g-0">
                                    <div class="col-12 col-lg-9 {{ $message->sender_type === 'candidate' ? 'ms-lg-auto' : '' }}">
                                        <div class="p-3 rounded-4 border shadow-sm {{ $message->sender_type === 'candidate' ? 'bg-primary text-white' : 'bg-white' }}">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <strong>{{ $message->sender_type === 'candidate' ? 'You' : 'Admin' }}</strong>
                                                <span class="small {{ $message->sender_type === 'candidate' ? 'text-white-50' : 'text-muted' }}">{{ $message->created_at->diffForHumans() }}</span>
                                            </div>
                                            <p class="mb-0">{{ $message->message }}</p>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-4">
                                    <i class="bi bi-chat-dots fs-2 text-muted"></i>
                                    <p class="mt-2 mb-0 text-muted">No messages yet.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                @if (! $ticket->isClosed())
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <form method="POST" action="{{ route('candidate.support-tickets.reply', $ticket) }}" class="vstack gap-3">
                                @csrf
                                <div>
                                    <label class="form-label fw-semibold">Reply</label>
                                    <textarea name="message" rows="4" class="form-control" placeholder="Write your reply" required>{{ old('message') }}</textarea>
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

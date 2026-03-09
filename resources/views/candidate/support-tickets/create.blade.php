@extends('candidate.layouts.app')

@section('title', 'New Support Ticket')

@section('content')
    <div class="container">
        <div class="card border-0 shadow-sm mb-4 bg-primary text-white">
            <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <p class="text-uppercase small text-white mb-1">Support</p>
                    <h1 class="h3 fw-bold mb-1">Create Ticket</h1>
                    <p class="text-white mb-0">Share the issue and we will respond as soon as possible.</p>
                </div>
                <a href="{{ route('candidate.support-tickets.index') }}" class="btn btn-outline-light">Back to tickets</a>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('candidate.support-tickets.store') }}" class="vstack gap-4">
                    @csrf
                    <div>
                        <label class="form-label fw-semibold">Subject</label>
                        <input type="text" name="subject" class="form-control form-control-lg" placeholder="e.g. Unable to access my exam" value="{{ old('subject') }}" required>
                        @error('subject')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    <div>
                        <label class="form-label fw-semibold">Priority</label>
                        <select name="priority" class="form-select form-select-lg" required>
                            @foreach (\App\Models\SupportTicket::PRIORITIES as $priority)
                                <option value="{{ $priority }}" @selected(old('priority', 'medium') === $priority)>
                                    {{ ucfirst($priority) }}
                                </option>
                            @endforeach
                        </select>
                        @error('priority')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    <div>
                        <label class="form-label fw-semibold">Message</label>
                        <textarea name="message" rows="6" class="form-control" placeholder="Describe the issue with as much detail as possible" required>{{ old('message') }}</textarea>
                        @error('message')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    <div class="d-flex justify-content-end">
                        <button class="btn btn-primary btn-lg" type="submit">Submit Ticket</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

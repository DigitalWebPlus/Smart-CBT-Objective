@extends('candidate.layouts.app')

@section('title', 'Account Settings')

@section('content')
    <div class="container">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                <div>
                    <p class="text-uppercase small mb-1 text-muted">Candidate profile</p>
                    <h1 class="h3 fw-bold mb-0">{{ __('Welcome, :name', ['name' => auth()->user()?->name]) }}</h1>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('candidate.profile.show') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-person me-2"></i>{{ __('Back to profile') }}
                    </a>
                    <a href="{{ route('candidate.exams.index') }}" class="btn btn-primary">
                        <i class="bi bi-journal-text me-2"></i>{{ __('Browse exams') }}
                    </a>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-4">
                @include('candidate.partials.profile-card', ['candidate' => auth()->user()])
            </div>
            <div class="col-lg-8">
                <div class="vstack gap-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            @include('profile.partials.update-profile-information-form')
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            @include('profile.partials.update-password-form')
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection

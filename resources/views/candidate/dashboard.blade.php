@extends('candidate.layouts.app')

@section('title', 'Candidate Dashboard')

@section('content')
    <div class="container">
        <div class="card border-0 shadow-sm mb-4 bg-primary text-white">
            <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <p class="text-uppercase small text-white mb-1">Dashboard</p>
                    <h1 class="h4 fw-semibold mb-0">Welcome, {{ auth()->user()?->name }}</h1>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-4">
                @include('candidate.partials.profile-card', ['candidate' => auth()->user()])
            </div>
            <div class="col-lg-8">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm rounded-4 h-100">
                            <div class="card-body p-4 text-center">
                                <div class="rounded-circle bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center mb-3"
                                    style="width: 72px; height: 72px;">
                                    <i class="bi bi-lightning-charge-fill fs-3"></i>
                                </div>
                                <h2 class="h4 fw-semibold mb-2">Available Exams</h2>
                                <div class="display-6 fw-bold mb-3">{{ $stats['available_exams'] ?? 0 }}</div>
                                <a href="{{ route('candidate.exams.index') }}" class="btn btn-primary btn-lg w-100">Go to exams</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm rounded-4 h-100">
                            <div class="card-body p-4 text-center">
                                <div class="rounded-circle bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center mb-3"
                                    style="width: 72px; height: 72px;">
                                    <i class="bi bi-check2-circle fs-3"></i>
                                </div>
                                <h2 class="h4 fw-semibold mb-2">Attempts</h2>
                                <div class="display-6 fw-bold mb-3">{{ $stats['attempts'] ?? 0 }}</div>
                                <a href="{{ route('candidate.exams.index') }}" class="btn btn-primary btn-lg w-100">View exams</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm rounded-4 h-100">
                            <div class="card-body p-4 text-center">
                                <div class="rounded-circle bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center mb-3"
                                    style="width: 72px; height: 72px;">
                                    <i class="bi bi-life-preserver fs-3"></i>
                                </div>
                                <h2 class="h4 fw-semibold mb-2">Support</h2>
                                <p class="text-muted mb-3">Need help? Reach the admin team.</p>
                                <a href="{{ route('candidate.support-tickets.index') }}" class="btn btn-primary btn-lg w-100">Open support</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm rounded-4 h-100">
                            <div class="card-body p-4 text-center">
                                <div class="rounded-circle bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center mb-3"
                                    style="width: 72px; height: 72px;">
                                    <i class="bi bi-gear fs-3"></i>
                                </div>
                                <h2 class="h4 fw-semibold mb-2">Settings</h2>
                                <p class="text-muted mb-3">Manage your account preferences.</p>
                                <a href="{{ route('profile.edit') }}" class="btn btn-primary btn-lg w-100">Account settings</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

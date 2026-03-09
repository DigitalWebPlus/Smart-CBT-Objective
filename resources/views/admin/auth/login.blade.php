@extends('layouts.auth')

@section('title', __('Admin Login'))
@section('auth-heading', __('Administrator Console'))
@section('auth-subheading', __('Secure access to the CBT control room'))

@section('auth-icon')
    <i class="bi bi-speedometer2 fs-4"></i>
@endsection

@section('auth-before-content')
    @if (session('status'))
        <div class="alert alert-success" role="alert">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            {{ __('Please review the highlighted fields below.') }}
        </div>
    @endif
@endsection

@section('auth-content')
    <div class="text-center mb-4">
        <p class="text-muted mb-0">{{ __('Use your administrator credentials to continue.') }}</p>
    </div>

    <form method="POST" action="{{ route('admin.login') }}" novalidate>
        @csrf

        <div class="input-group mb-3">
            <span class="input-group-text bg-light border-0">
                <i class="bi bi-envelope"></i>
            </span>
            <div class="form-floating flex-grow-1">
                <input type="email" class="form-control border-start-0 @error('email') is-invalid @enderror" id="email"
                    name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                    placeholder="name@example.com">
                <label for="email">{{ __('Email address') }}</label>
            </div>
        </div>
        @error('email')
            <div class="invalid-feedback d-block mb-3">{{ $message }}</div>
        @enderror

        <div class="input-group mb-3">
            <span class="input-group-text bg-light border-0">
                <i class="bi bi-lock"></i>
            </span>
            <div class="form-floating flex-grow-1">
                <input type="password" class="form-control border-start-0 @error('password') is-invalid @enderror"
                    id="password" name="password" required autocomplete="current-password" placeholder="Password">
                <label for="password">{{ __('Password') }}</label>
            </div>
        </div>
        @error('password')
            <div class="invalid-feedback d-block mb-3">{{ $message }}</div>
        @enderror

        <div class="d-flex align-items-center justify-content-between mb-4">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" value="1" id="remember_me" name="remember"
                    {{ old('remember') ? 'checked' : '' }}>
                <label class="form-check-label" for="remember_me">
                    {{ __('Keep me signed in') }}
                </label>
            </div>
            <a class="text-decoration-none small text-primary" href="{{ route('login') }}">
                {{ __('Candidate sign in') }}
            </a>
        </div>

        <div class="d-grid">
            <button type="submit" class="btn btn-primary btn-lg rounded-pill">{{ __('Log in') }}</button>
        </div>
    </form>
@endsection

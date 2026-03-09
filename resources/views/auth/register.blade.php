<x-guest-layout>
    <div class="text-center mb-4">
        <h2 class="h5 fw-semibold mb-1">{{ __('Create your account') }}</h2>
        <p class="text-muted mb-0">{{ __('Join the CBT platform and access your exams in one place.') }}</p>
    </div>

    <form method="POST" action="{{ route('register') }}" novalidate>
        @csrf

        <div class="input-group mb-3">
            <span class="input-group-text bg-light border-0">
                <i class="bi bi-person"></i>
            </span>
            <div class="form-floating flex-grow-1">
                <input type="text" class="form-control border-start-0 @error('name') is-invalid @enderror" id="name"
                    name="name" value="{{ old('name') }}" required autofocus autocomplete="name" placeholder="Full name">
                <label for="name">{{ __('Full name') }}</label>
            </div>
        </div>
        @error('name')
            <div class="invalid-feedback d-block mb-3">{{ $message }}</div>
        @enderror

        <div class="input-group mb-3">
            <span class="input-group-text bg-light border-0">
                <i class="bi bi-envelope"></i>
            </span>
            <div class="form-floating flex-grow-1">
                <input type="email" class="form-control border-start-0 @error('email') is-invalid @enderror" id="email"
                    name="email" value="{{ old('email') }}" required autocomplete="username" placeholder="name@example.com">
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
                    id="password" name="password" required autocomplete="new-password" placeholder="Password">
                <label for="password">{{ __('Password') }}</label>
            </div>
        </div>
        @error('password')
            <div class="invalid-feedback d-block mb-3">{{ $message }}</div>
        @enderror

        <div class="input-group mb-4">
            <span class="input-group-text bg-light border-0">
                <i class="bi bi-shield-check"></i>
            </span>
            <div class="form-floating flex-grow-1">
                <input type="password" class="form-control border-start-0 @error('password_confirmation') is-invalid @enderror"
                    id="password_confirmation" name="password_confirmation" required autocomplete="new-password"
                    placeholder="Confirm password">
                <label for="password_confirmation">{{ __('Confirm password') }}</label>
            </div>
        </div>
        @error('password_confirmation')
            <div class="invalid-feedback d-block mb-3">{{ $message }}</div>
        @enderror

        <div class="d-grid">
            <button type="submit" class="btn btn-primary btn-lg rounded-pill">{{ __('Create account') }}</button>
        </div>
    </form>

    <p class="text-center text-muted mt-4 mb-0 small">
        {{ __('Already have an account?') }}
        <a class="text-decoration-none fw-semibold text-primary" href="{{ route('login') }}">
            {{ __('Sign in') }}
        </a>
    </p>
</x-guest-layout>

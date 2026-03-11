<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    @php
        $candidateLoginMode = config('settings.candidate_login_mode', 'registration_number');
        $usesRegistrationNumberOnly = $candidateLoginMode === 'registration_number';
    @endphp

    <form method="POST" action="{{ route('login') }}" novalidate>
        @csrf

        @if ($usesRegistrationNumberOnly)
            <div class="input-group mb-3">
                <span class="input-group-text bg-light border-0">
                    <i class="bi bi-person-badge"></i>
                </span>
                <div class="form-floating flex-grow-1">
                    <input type="text"
                        class="form-control border-start-0 @error('registration_number') is-invalid @enderror"
                        id="registration_number" name="registration_number" value="{{ old('registration_number') }}"
                        required autofocus autocomplete="username" placeholder="Registration number">
                    <label for="registration_number">{{ __('Registration Number') }}</label>
                </div>
            </div>
            @error('registration_number')
                <div class="invalid-feedback d-block mb-3">{{ $message }}</div>
            @enderror
        @else
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
            @endif

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
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="1" id="remember_me" name="remember">
                    <label class="form-check-label" for="remember_me">
                        {{ __('Remember me') }}
                    </label>
                </div>
                @if (Route::has('password.request'))
                    <a class="text-decoration-none small text-primary" href="{{ route('password.request') }}">
                        {{ __('Forgot password?') }}
                    </a>
                @endif
            </div>
        @endif

        <div class="d-grid">
            <button type="submit" class="btn btn-primary btn-lg rounded-pill">{{ __('Log in') }}</button>
        </div>
    </form>

    <p class="text-center text-muted mt-4 mb-0 small">
        {{ __('New here?') }}
        <a class="text-decoration-none fw-semibold text-primary" href="{{ route('register') }}">
            {{ __('Create an account') }}
        </a>
    </p>
</x-guest-layout>

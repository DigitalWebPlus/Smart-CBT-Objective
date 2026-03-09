<section>
    <header>
        <h2 class="h5 fw-semibold mb-1">
            {{ __('Profile Information') }}
        </h2>

        <p class="text-muted mb-0">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="row g-3 mt-3">
        @csrf
        @method('patch')

        <div class="col-12">
            <label for="name" class="form-label fw-semibold">{{ __('Name') }}</label>
            <input id="name" name="name" type="text" class="form-control"
                value="{{ old('name', $user->name) }}" required autofocus autocomplete="name" readonly />
            @error('name')
                <small class="text-danger">{{ $message }}</small>
            @enderror
        </div>

        <div class="col-12">
            <label for="email" class="form-label fw-semibold">{{ __('Email') }}</label>
            <input id="email" name="email" type="email" class="form-control"
                value="{{ old('email', $user->email) }}" required autocomplete="username" readonly />
            @error('email')
                <small class="text-danger">{{ $message }}</small>
            @enderror

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !$user->hasVerifiedEmail())
                <div class="mt-2">
                    <p class="small text-muted mb-1">
                        {{ __('Your email address is unverified.') }}
                    </p>
                    <button form="send-verification" class="btn btn-link p-0">
                        {{ __('Click here to re-send the verification email.') }}
                    </button>

                    @if (session('status') === 'verification-link-sent')
                        <p class="small text-success mt-2 mb-0">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="col-12 d-flex align-items-center gap-3">
            <button class="btn btn-primary" type="submit" disabled>{{ __('Cant Update') }}</button>

            @if (session('status') === 'profile-updated')
                <span x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)"
                    class="small text-muted">{{ __('Saved.') }}</span>
            @endif
        </div>
    </form>
</section>

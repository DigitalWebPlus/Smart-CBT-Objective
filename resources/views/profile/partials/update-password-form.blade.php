<section>
    <header>
        <h2 class="h5 fw-semibold mb-1">
            {{ __('Update Password') }}
        </h2>

        <p class="text-muted mb-0">
            {{ __('Ensure your account is using a long, random password to stay secure.') }}
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="row g-3 mt-3">
        @csrf
        @method('put')

        <div class="col-12">
            <label for="update_password_current_password" class="form-label fw-semibold">{{ __('Current Password') }}</label>
            <input id="update_password_current_password" name="current_password" type="password" class="form-control" autocomplete="current-password" />
            @if ($errors->updatePassword->has('current_password'))
                <small class="text-danger">{{ $errors->updatePassword->first('current_password') }}</small>
            @endif
        </div>

        <div class="col-12">
            <label for="update_password_password" class="form-label fw-semibold">{{ __('New Password') }}</label>
            <input id="update_password_password" name="password" type="password" class="form-control" autocomplete="new-password" />
            @if ($errors->updatePassword->has('password'))
                <small class="text-danger">{{ $errors->updatePassword->first('password') }}</small>
            @endif
        </div>

        <div class="col-12">
            <label for="update_password_password_confirmation" class="form-label fw-semibold">{{ __('Confirm Password') }}</label>
            <input id="update_password_password_confirmation" name="password_confirmation" type="password" class="form-control" autocomplete="new-password" />
            @if ($errors->updatePassword->has('password_confirmation'))
                <small class="text-danger">{{ $errors->updatePassword->first('password_confirmation') }}</small>
            @endif
        </div>

        <div class="col-12 d-flex align-items-center gap-3">
            <button class="btn btn-primary" type="submit">{{ __('Save') }}</button>

            @if (session('status') === 'password-updated')
                <span
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="small text-muted"
                >{{ __('Saved.') }}</span>
            @endif
        </div>
    </form>
</section>

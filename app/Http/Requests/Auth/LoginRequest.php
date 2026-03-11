<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        if ($this->isRegistrationNumberMode()) {
            return [
                'registration_number' => ['required', 'string', 'max:255'],
            ];
        }

        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if ($this->isRegistrationNumberMode()) {
            $candidate = User::query()
                ->where('registration_number', (string) $this->input('registration_number'))
                ->first();

            if (! $candidate instanceof User) {
                RateLimiter::hit($this->throttleKey());

                throw ValidationException::withMessages([
                    'registration_number' => trans('auth.failed'),
                ]);
            }

            if ($candidate->status !== User::STATUS_ACTIVE) {
                RateLimiter::hit($this->throttleKey());

                throw ValidationException::withMessages([
                    'registration_number' => $this->candidateStatusMessage($candidate->status),
                ]);
            }

            Auth::login($candidate, false);
            RateLimiter::clear($this->throttleKey());

            return;
        }

        $candidate = User::query()
            ->where('email', (string) $this->input('email'))
            ->first();

        if (
            $candidate instanceof User
            && Hash::check((string) $this->input('password'), (string) $candidate->password)
            && $candidate->status !== User::STATUS_ACTIVE
        ) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => $this->candidateStatusMessage($candidate->status),
            ]);
        }

        if (! Auth::attempt([
            'email' => (string) $this->input('email'),
            'password' => (string) $this->input('password'),
            'status' => User::STATUS_ACTIVE,
        ], $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            $this->loginField() => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string($this->loginField())).'|'.$this->ip());
    }

    private function isRegistrationNumberMode(): bool
    {
        return $this->candidateLoginMode() === 'registration_number';
    }

    private function candidateLoginMode(): string
    {
        return (string) config('settings.candidate_login_mode', 'registration_number');
    }

    private function loginField(): string
    {
        return $this->isRegistrationNumberMode() ? 'registration_number' : 'email';
    }

    private function candidateStatusMessage(string $status): string
    {
        return match ($status) {
            User::STATUS_BANNED => 'Your account has been banned. Please contact the administrator.',
            User::STATUS_SUSPENDED => 'Your account is suspended. Please contact the administrator.',
            User::STATUS_INACTIVE => 'Your account is inactive. Please contact the administrator.',
            default => 'Your account is not active. Please contact the administrator.',
        };
    }
}

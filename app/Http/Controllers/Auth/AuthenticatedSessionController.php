<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\ExamAttempt;
use App\Models\ObjectiveExamAttempt;
use App\Models\User;
use App\Services\AdminActionLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        try {
            $request->authenticate();
        } catch (ValidationException $exception) {
            app(AdminActionLogger::class)->log(
                action: 'candidate.auth.login.failed',
                metadata: [
                    'actor_type' => 'candidate',
                    'email' => (string) $request->input('email', ''),
                ],
                request: $request
            );

            throw $exception;
        }

        $request->session()->regenerate();

        $candidate = $request->user();

        if ($candidate instanceof User) {
            $this->trackExamLogin($candidate, $request->ip());

            app(AdminActionLogger::class)->log(
                action: 'candidate.auth.login.success',
                candidate: $candidate,
                metadata: [
                    'actor_type' => 'candidate',
                    'email' => $candidate->email,
                ],
                request: $request
            );
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $candidate = $request->user();

        if ($candidate instanceof User) {
            app(AdminActionLogger::class)->log(
                action: 'candidate.auth.logout',
                candidate: $candidate,
                metadata: [
                    'actor_type' => 'candidate',
                ],
                request: $request
            );
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function trackExamLogin(User $candidate, ?string $ipAddress): void
    {
        if ($ipAddress === null || $ipAddress === '') {
            return;
        }

        if (Schema::hasTable('exam_attempts')) {
            $this->updateAttemptsLogin(ExamAttempt::query(), $candidate->id, $ipAddress, 'user_id');
        }

        if (Schema::hasTable('exam_objective_attempts')) {
            $this->updateAttemptsLogin(ObjectiveExamAttempt::query(), $candidate->id, $ipAddress);
        }

    }

    private function updateAttemptsLogin($query, int $candidateId, string $ipAddress, string $userColumn = 'candidate_id'): void
    {
        $query->where($userColumn, $candidateId)
            ->where('status', ExamAttempt::STATUS_IN_PROGRESS)
            ->get()
            ->each(function ($attempt) use ($ipAddress): void {
                $ips = collect($attempt->login_ips ?? [])
                    ->filter(fn ($value) => filled($value))
                    ->values()
                    ->all();

                $ips[] = $ipAddress;
                $attempt->login_ips = array_values(array_unique($ips));
                $attempt->login_count = ((int) ($attempt->login_count ?? 0)) + 1;
                $attempt->save();
            });
    }
}

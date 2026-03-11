<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureCandidateIsActive
{
    public function handle(Request $request, Closure $next): mixed
    {
        $user = $request->user();

        if ($user instanceof User && $user->status !== User::STATUS_ACTIVE) {
            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('status', $this->candidateStatusMessage($user->status));
        }

        return $next($request);
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

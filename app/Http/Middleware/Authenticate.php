<?php

namespace App\Http\Middleware;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Throw an authentication exception with a guard-aware redirect target.
     */
    protected function unauthenticated($request, array $guards)
    {
        throw new AuthenticationException(
            'Unauthenticated.',
            $guards,
            $this->resolveRedirectPath($request, $guards)
        );
    }

    /**
     * Determine where to send unauthenticated users for the current guard.
     */
    protected function resolveRedirectPath(Request $request, array $guards): ?string
    {
        if ($request->expectsJson()) {
            return null;
        }

        $guard = $guards[0] ?? null;

        return match ($guard) {
            'admin' => route('admin.login'),
            default => route('login'),
        };
    }
}

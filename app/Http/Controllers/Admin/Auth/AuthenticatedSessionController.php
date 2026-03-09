<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Services\AdminActionLogger;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('admin.auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('admin')->attempt($credentials, $request->boolean('remember'))) {
            app(AdminActionLogger::class)->log(
                action: 'admin.auth.login.failed',
                metadata: [
                    'actor_type' => 'admin',
                    'email' => $credentials['email'],
                    'remember' => $request->boolean('remember'),
                ],
                request: $request
            );

            return back()->withErrors(['email' => trans('auth.failed')])->onlyInput('email');
        }

        $request->session()->regenerate();

        app(AdminActionLogger::class)->log(
            action: 'admin.auth.login.success',
            admin: $request->user('admin'),
            metadata: [
                'actor_type' => 'admin',
                'remember' => $request->boolean('remember'),
            ],
            request: $request
        );

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $admin = $request->user('admin');

        app(AdminActionLogger::class)->log(
            action: 'admin.auth.logout',
            admin: $admin,
            metadata: [
                'actor_type' => 'admin',
            ],
            request: $request
        );

        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}

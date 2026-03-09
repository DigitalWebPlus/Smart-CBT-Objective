<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('settings.site_name', config('app.name', 'CBT Exam')) }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
            integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.css">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            body {
                font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
                background: radial-gradient(circle at top, #eef2ff, #e2e8f0);
                min-height: 100vh;
            }

            .auth-card {
                border: none;
                border-radius: 1.25rem;
                box-shadow: 0 25px 65px rgba(15, 23, 42, 0.18);
            }

            .auth-icon {
                width: 96px;
                height: 96px;
            }
        </style>
    </head>
    <body class="d-flex align-items-center py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-5">
                    @php
                        $siteName = config('settings.site_name', config('app.name', 'CBT Exam'));
                        $siteLogo = config('settings.site_logo');
                    @endphp
                    <div class="card auth-card">
                        <div class="card-body p-4 p-md-5">
                            <div class="text-center mb-4">
                                <div class="auth-icon d-inline-flex align-items-center justify-content-center mb-3">
                                    @if ($siteLogo)
                                        <img src="{{ asset($siteLogo) }}" alt="{{ $siteName }} logo" style="width: 88px; height: 88px; object-fit: contain;">
                                    @else
                                        <i class="bi bi-shield-lock-fill fs-4"></i>
                                    @endif
                                </div>
                                <h1 class="h4 fw-semibold mb-1">{{ $siteName }}</h1>
                                <p class="text-muted mb-0">{{ __('Sign in or create an account to continue.') }}</p>
                            </div>

                            {{ $slot }}
                        </div>
                    </div>
                    <p class="text-center text-muted mt-4 mb-0 small">
                        Copyright &copy; {{ date('Y') }}
                        <a href="https://digitalwebplus.com/product/smart-cbt-objective" target="_blank" class="link-primary">DigitalWeb Plus</a> |
                        All rights reserved.
                    </p>
                </div>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.js"></script>
        @flasher_render
        @php
            $notifications = session('app_notifications', []);
        @endphp
        @if (!empty($notifications))
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const notifier = new Notyf();
                    const notifications = @json($notifications);
                    notifications.forEach((notification) => {
                        if (notification.type === 'error') {
                            notifier.error(notification.message);
                        } else {
                            notifier.success(notification.message);
                        }
                    });
                });
            </script>
        @endif
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
            crossorigin="anonymous"></script>
    </body>
</html>

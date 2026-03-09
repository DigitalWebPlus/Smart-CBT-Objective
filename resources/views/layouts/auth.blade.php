<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', __('Secure Access')) · {{ config('settings.site_name', config('app.name', 'CBT Exam')) }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
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

        .auth-icon-wrapper {
            width: 96px;
            height: 96px;
        }
    </style>
    @stack('auth-styles')
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
                            <div class="auth-icon-wrapper d-inline-flex align-items-center justify-content-center mb-3">
                                @if ($siteLogo)
                                    <img src="{{ asset($siteLogo) }}" alt="{{ $siteName }} logo" style="width: 88px; height: 88px; object-fit: contain;">
                                @elseif (trim($__env->yieldContent('auth-icon')) !== '')
                                    @yield('auth-icon')
                                @else
                                    <i class="bi bi-shield-lock-fill fs-4"></i>
                                @endif
                            </div>
                            <h2 class="mb-1">
                                <span class="badge text-bg-primary text-uppercase">@yield('title', __('Admin Login'))</span>
                            </h2>
                            <h1 class="h4 fw-semibold mb-1">{{ $siteName }}</h1>
                            <p class="text-muted mb-0">@yield('auth-subheading', __('Please sign in to continue.'))</p>
                        </div>

                        @yield('auth-before-content')

                        @yield('auth-content')
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>
    @stack('auth-scripts')
</body>

</html>

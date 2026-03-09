<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'CBT Candidate Portal')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.css">
    <style>
        :root {
            --candidate-primary: #2f5bea;
            --candidate-secondary: #f4f6fb;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background-color: var(--candidate-secondary);
            min-height: 100vh;
        }

        .brand-gradient {
            background: linear-gradient(120deg, #2f5bea, #4ad2ff);
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(47, 91, 234, 0.08);
            box-shadow: 0 20px 45px rgba(15, 23, 42, 0.08);
            backdrop-filter: blur(6px);
        }

        .nav-link.active {
            font-weight: 600;
            color: var(--candidate-primary) !important;
        }

        .status-badge {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
    </style>
    @stack('styles')
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="{{ route('dashboard') }}">
                <i class="bi bi-journal-text me-2"></i>CBT Candidate Portal
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#candidateNav"
                aria-controls="candidateNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="candidateNav">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-3">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                            href="{{ route('dashboard') }}">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('candidate.exams.*') ? 'active' : '' }}"
                            href="{{ route('candidate.exams.index') }}">Exams</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('candidate.profile.show') ? 'active' : '' }}"
                            href="{{ route('candidate.profile.show') }}">Profile</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('candidate.support-tickets.*') ? 'active' : '' }}"
                            href="{{ route('candidate.support-tickets.index') }}">Support</a>
                    </li>
                    @auth
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('profile.edit') ? 'active' : '' }}"
                                href="{{ route('profile.edit') }}">Settings</a>
                        </li>
                        <li class="nav-item">
                            <form method="POST" action="{{ route('logout') }}" class="d-inline">
                                @csrf
                                <button class="nav-link btn btn-link p-0 text-danger" type="submit">
                                    <i class="bi bi-box-arrow-right me-1"></i>Logout
                                </button>
                            </form>
                        </li>
                    @endauth
                </ul>
            </div>
        </div>
    </nav>

    <main class="py-5">
        @yield('content')
    </main>

    <footer class="py-4 bg-white border-top">
        <div class="container text-center small text-muted">
            &copy; {{ date('Y') }} CBT Examination Portal. All rights reserved.
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-HwwvtgBNo3bZJJLYd8oVXjrBZt8cqVSpeBNS5n7C8IVInixGAoxmnlMuBnhbgrkm" crossorigin="anonymous">
    </script>
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
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const navToggler = document.querySelector('.navbar-toggler');
            const navCollapse = document.querySelector('#candidateNav');
            if (navToggler && navCollapse) {
                navToggler.addEventListener('click', (event) => {
                    event.preventDefault();
                    navCollapse.classList.toggle('show');
                    const isExpanded = navCollapse.classList.contains('show');
                    navToggler.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
                });
            }

            const confirmableForms = Array.from(document.querySelectorAll('form[data-swal-title]'));
            if (confirmableForms.length && typeof Swal !== 'undefined') {
                confirmableForms.forEach((form) => {
                    form.addEventListener('submit', (event) => {
                        if (form.dataset.swalSubmitting === 'true') {
                            return;
                        }

                        event.preventDefault();

                        const title = form.dataset.swalTitle || 'Are you sure?';
                        const text = form.dataset.swalConfirm || 'Please confirm to continue.';
                        const icon = form.dataset.swalIcon || 'warning';
                        const confirmText = form.dataset.swalConfirmButton || 'Yes, continue';
                        const cancelText = form.dataset.swalCancelButton || 'Cancel';

                        Swal.fire({
                            title,
                            text,
                            icon,
                            showCancelButton: true,
                            confirmButtonText: confirmText,
                            cancelButtonText: cancelText,
                        }).then((result) => {
                            if (result.isConfirmed) {
                                form.dataset.swalSubmitting = 'true';
                                form.submit();
                            }
                        });
                    });
                });
            }

        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @stack('scripts')
</body>

</html>

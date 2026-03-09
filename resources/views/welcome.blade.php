<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('settings.site_name', config('app.name', 'CBT Objective')) }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=space-grotesk:400,500,600,700|playfair-display:600" rel="stylesheet" />
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

        <style>
            :root {
                --ink: #0b0f1e;
                --slate: #5b6675;
                --royal: #113c7c;
                --teal: #1fb6c3;
                --gold: #f5c16c;
                --rose: #f06b6b;
                --sand: #f8f2ea;
                --sky: #e5f2ff;
            }

            body {
                font-family: "Space Grotesk", system-ui, -apple-system, sans-serif;
                color: var(--ink);
                background:
                    radial-gradient(circle at 10% 10%, rgba(31, 182, 195, 0.28), transparent 45%),
                    radial-gradient(circle at 80% 15%, rgba(245, 193, 108, 0.3), transparent 40%),
                    linear-gradient(180deg, var(--sky), var(--sand));
            }

            .brand-font {
                font-family: "Playfair Display", "Times New Roman", serif;
            }

            .hero-panel {
                background: linear-gradient(140deg, rgba(17, 60, 124, 0.08), rgba(31, 182, 195, 0.12)), #ffffff;
                border: 1px solid rgba(17, 60, 124, 0.12);
                box-shadow: 0 25px 55px rgba(17, 60, 124, 0.16);
            }

            .pill {
                letter-spacing: 0.25em;
                font-size: 0.7rem;
                color: var(--slate);
            }

            .btn-premium {
                background: linear-gradient(120deg, var(--royal), var(--teal));
                color: #ffffff;
                border: none;
                box-shadow: 0 18px 30px rgba(17, 60, 124, 0.3);
            }

            .btn-premium:hover {
                color: #ffffff;
                opacity: 0.9;
            }

            .btn-ghost {
                border: 1px solid rgba(17, 60, 124, 0.3);
                color: var(--royal);
                background: rgba(255, 255, 255, 0.7);
            }

            .btn-ghost:hover {
                color: var(--royal);
                border-color: rgba(17, 60, 124, 0.5);
            }

            .badge-glow {
                background: linear-gradient(120deg, var(--teal), var(--gold));
                color: #0b0f1e;
                border: none;
                box-shadow: 0 12px 20px rgba(31, 182, 195, 0.3);
            }

            .feature-card {
                border: 1px solid rgba(17, 60, 124, 0.1);
                box-shadow: 0 20px 35px rgba(17, 60, 124, 0.08);
                transition: transform 0.35s ease, box-shadow 0.35s ease;
            }

            .feature-card:hover {
                transform: translateY(-6px);
                box-shadow: 0 30px 50px rgba(17, 60, 124, 0.18);
            }

            .preview-frame {
                border-radius: 24px;
                border: 1px solid rgba(17, 60, 124, 0.2);
                box-shadow: 0 28px 55px rgba(17, 60, 124, 0.25);
                overflow: hidden;
                background: linear-gradient(140deg, #0b1430, #1d2752);
            }

            .stat-card {
                background: linear-gradient(140deg, rgba(255, 255, 255, 0.95), rgba(245, 242, 234, 0.85));
                border: 1px solid rgba(17, 60, 124, 0.12);
                border-radius: 18px;
                padding: 20px;
            }

            .panel-dark {
                background: linear-gradient(140deg, #0b1430, #1a2a4d);
            }
        </style>
    </head>
    <body>
        @php
            $siteName = config('settings.site_name', config('app.name', 'CBT Objective'));
            $siteLogo = config('settings.site_logo');
            $siteEmail = config('settings.site_email');
            $sitePhone = config('settings.site_phone');
            $siteAddress = config('settings.site_address');
        @endphp
        <nav class="navbar navbar-expand-lg py-4">
            <div class="container">
                <a class="navbar-brand d-flex align-items-center gap-3" href="{{ url('/') }}">
                    <span class="d-inline-flex align-items-center justify-content-center rounded-4 bg-white shadow-sm" style="width: 44px; height: 44px;">
                        @if ($siteLogo)
                            <img src="{{ asset($siteLogo) }}" alt="{{ $siteName }} logo" style="width: 120px; height: 90px; object-fit: contain;">
                        @else
                            CB
                        @endif
                    </span>
                    @unless ($siteLogo)
                        <span class="fw-semibold">{{ $siteName }}</span>
                    @endunless
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarLanding" aria-controls="navbarLanding" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarLanding">
                    <ul class="navbar-nav ms-auto align-items-lg-center gap-2">
                        <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
                        <li class="nav-item"><a class="nav-link" href="#workflow">Workflow</a></li>
                        <li class="nav-item"><a class="nav-link" href="#preview">Preview</a></li>
                        @if (Route::has('login'))
                            @auth
                                <li class="nav-item"><a class="btn btn-premium rounded-pill px-4" href="{{ url('/dashboard') }}">Dashboard</a></li>
                            @else
                                <li class="nav-item"><a class="btn btn-ghost rounded-pill px-4" href="{{ route('login') }}">Log in</a></li>
                                @if (Route::has('register'))
                                    <li class="nav-item"><a class="btn btn-premium rounded-pill px-4" href="{{ route('register') }}">Register</a></li>
                                @endif
                            @endauth
                        @endif
                    </ul>
                </div>
            </div>
        </nav>

        <section class="py-5">
            <div class="container">
                <div class="row g-5 align-items-center">
                    <div class="col-lg-6">
                        <p class="pill text-uppercase fw-semibold">{{ $siteName }}</p>
                        <h1 class="display-5 fw-semibold brand-font">Build confident, objective assessments with speed and integrity.</h1>
                        <p class="mt-3 text-secondary" style="max-width: 520px;">
                            Create rich question banks, run timed exams, and deliver instant results. Designed for schools,
                            departments, and training centers that need clarity from question to report.
                        </p>
                        <div class="d-flex flex-wrap gap-3 mt-4">
                            <a class="btn btn-premium btn-lg rounded-pill px-4" href="{{ route('login') }}">Launch an exam</a>
                            <a class="btn btn-ghost btn-lg rounded-pill px-4" href="#features">Explore features</a>
                        </div>
                        <div class="row g-3 mt-5">
                            <div class="col-md-4">
                                <div class="stat-card">
                                    <h3 class="h4 fw-semibold mb-1">99.9%</h3>
                                    <p class="small text-secondary mb-0">Uptime-ready delivery</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="stat-card">
                                    <h3 class="h4 fw-semibold mb-1">3 min</h3>
                                    <p class="small text-secondary mb-0">Average setup time</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="stat-card">
                                    <h3 class="h4 fw-semibold mb-1">50k+</h3>
                                    <p class="small text-secondary mb-0">Ready to import</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="hero-panel rounded-4 p-4 p-lg-5">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="text-uppercase small text-secondary mb-1">Live exam pulse</p>
                                    <h2 class="h4 fw-semibold mb-0">Realtime monitoring</h2>
                                </div>
                                <span class="badge badge-glow rounded-pill">Active</span>
                            </div>
                            <div class="row g-3 mt-4">
                                <div class="col-4">
                                    <div class="bg-white rounded-4 p-3 text-center">
                                        <p class="h4 fw-semibold mb-1">124</p>
                                        <p class="small text-secondary mb-0">Active</p>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="bg-white rounded-4 p-3 text-center">
                                        <p class="h4 fw-semibold mb-1">92%</p>
                                        <p class="small text-secondary mb-0">Avg score</p>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="bg-white rounded-4 p-3 text-center">
                                        <p class="h4 fw-semibold mb-1">14</p>
                                        <p class="small text-secondary mb-0">Centers</p>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 rounded-4 panel-dark text-white p-4">
                                <p class="small text-uppercase text-secondary mb-2">Secure workflow</p>
                                <ul class="small mb-0">
                                    <li>Timed access, randomization, and attempt controls.</li>
                                    <li>Auto-grading with audit-ready reports.</li>
                                    <li>Bulk uploads and question bank transfers.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="features" class="py-5">
            <div class="container">
                <div class="d-flex flex-wrap align-items-end justify-content-between gap-3">
                    <div>
                        <p class="pill text-uppercase fw-semibold">Platform highlights</p>
                        <h2 class="h1 fw-semibold brand-font">Everything you need for objective assessment.</h2>
                    </div>
                    <span class="badge text-bg-light border rounded-pill px-3 py-2 text-secondary">Trusted by departments</span>
                </div>
                <div class="row g-4 mt-4">
                    <div class="col-md-6 col-lg-4">
                        <div class="feature-card bg-white rounded-4 p-4 h-100">
                            <h3 class="h5 fw-semibold">Question Bank Control</h3>
                            <p class="text-secondary">Organize by subject, difficulty, and topic. Reuse, version, and import at scale.</p>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="feature-card bg-white rounded-4 p-4 h-100">
                            <h3 class="h5 fw-semibold">Candidate Experience</h3>
                            <p class="text-secondary">Responsive test rooms, clear timers, and low-friction submissions on any device.</p>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="feature-card bg-white rounded-4 p-4 h-100">
                            <h3 class="h5 fw-semibold">Analytics & Reporting</h3>
                            <p class="text-secondary">Auto-scored reports, banded grading, and exports for accountability.</p>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="feature-card bg-white rounded-4 p-4 h-100">
                            <h3 class="h5 fw-semibold">Live Monitoring</h3>
                            <p class="text-secondary">Track ongoing exams, catch issues early, and keep invigilators aligned.</p>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="feature-card bg-white rounded-4 p-4 h-100">
                            <h3 class="h5 fw-semibold">Security & Integrity</h3>
                            <p class="text-secondary">Attempt limits, randomized questions, and secure uploads keep results defensible.</p>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="feature-card bg-white rounded-4 p-4 h-100">
                            <h3 class="h5 fw-semibold">Admin Toolkit</h3>
                            <p class="text-secondary">Role-based controls, settings, and audit logs in one clear workspace.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="preview" class="py-5">
            <div class="container">
                <div class="row g-5 align-items-center">
                    <div class="col-lg-5">
                        <p class="pill text-uppercase fw-semibold">Product preview</p>
                        <h2 class="h2 fw-semibold brand-font">See the platform in action.</h2>
                        <p class="text-secondary">Use the candidate-friendly interface while admins keep control of timing, results, and visibility.</p>
                        <div class="mt-4">
                            <a class="btn btn-premium rounded-pill px-4" href="{{ route('login') }}">Try the dashboard</a>
                        </div>
                    </div>
                    <div class="col-lg-7">
                        <div class="preview-frame" style="transform: scale(1.12);">
                            <img src="{{ asset('assets/images/student-writing.svg') }}" alt="Product preview" class="img-fluid" style="background: #0f172a; width: 100%; height: 420px; object-fit: contain;">
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="workflow" class="py-5">
            <div class="container">
                <div class="row g-4">
                    <div class="col-lg-7">
                        <div class="bg-white rounded-4 p-5 shadow-sm border">
                            <p class="pill text-uppercase fw-semibold">How it flows</p>
                            <h2 class="h2 fw-semibold brand-font">From question bank to report in four steps.</h2>
                            <ol class="mt-4 text-secondary">
                                <li class="mb-3">Build or import questions and options.</li>
                                <li class="mb-3">Assign subjects, schedules, and time limits.</li>
                                <li class="mb-3">Candidates take secure, timed exams.</li>
                                <li>Instantly generate scores and insights.</li>
                            </ol>
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="bg-dark text-white rounded-4 p-4 mb-4">
                            <p class="pill text-uppercase text-white-50 fw-semibold">Quick tip</p>
                            <p class="mb-0">Use bulk upload to seed an entire semester in one pass, then iterate safely.</p>
                        </div>
                        <div class="bg-white rounded-4 p-4 border shadow-sm">
                            <p class="pill text-uppercase fw-semibold">Default admin</p>
                            
                            <p class="text-secondary small mb-0">Reset this after onboarding.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <footer class="border-top py-4 bg-white">
            <div class="container d-flex flex-wrap align-items-center justify-content-between gap-3">
                <p class="text-secondary small mb-0">{{ $siteName }} — Built for educators who need clarity, speed, and integrity.</p>
                <div class="d-flex flex-wrap gap-3">
                    @if ($siteEmail)
                        <span class="text-secondary small">{{ $siteEmail }}</span>
                    @endif
                    @if ($sitePhone)
                        <span class="text-secondary small">{{ $sitePhone }}</span>
                    @endif
                    @if ($siteAddress)
                        <span class="text-secondary small">{{ $siteAddress }}</span>
                    @endif
                </div>
                <div class="d-flex gap-3">
                    <a class="text-decoration-none text-secondary" href="{{ route('login') }}">Sign in</a>
                    <a class="text-decoration-none text-secondary" href="#features">Features</a>
                </div>
            </div>
        </footer>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    </body>
</html>

<!-- Sidebar -->
<style>
    .navbar-vertical .nav-item.active > .nav-link,
    .navbar-vertical .nav-link.active {
        background-color: rgba(255, 255, 255, 0.08);
        border-radius: 8px;
    }

    .navbar-vertical .dropdown-item.active,
    .navbar-vertical .dropdown-item:active {
        background-color: rgba(59, 130, 246, 0.22) !important;
        color: #fff !important;
        border-radius: 6px;
    }

    .navbar-vertical .dropdown-item.active:hover,
    .navbar-vertical .dropdown-item:active:hover {
        background-color: rgba(59, 130, 246, 0.3) !important;
    }

    .navbar-brand-autodark .navbar-brand-image {
        filter: none !important;
    }
</style>
@php
    $siteName = config('settings.site_name', config('app.name', 'CBT Objective'));
    $siteLogo = config('settings.site_logo');
    $isDashboard = request()->routeIs('admin.dashboard');
    $isCandidates = request()->routeIs('admin.candidates.*');
    $isDepartments = request()->routeIs('admin.departments.*');
    $isSubjects = request()->routeIs('admin.subjects.*');
    $isQuestionBanks = request()->routeIs('admin.question-banks.*');
    $isExams = request()->routeIs('admin.exams.*');
    $isAttempts = request()->routeIs('admin.attempts.*');
    $isResults = request()->routeIs('admin.results.*');
    $isMonitor = request()->routeIs('admin.monitor-exams.*');
    $isSupport = request()->routeIs('admin.support-tickets.*');
    $isLogs = request()->routeIs('admin.logs.*');
    $isSettings = request()->routeIs('admin.settings.*');
@endphp
<aside class="navbar navbar-vertical navbar-expand-lg" data-bs-theme="dark">
    <div class="container-fluid">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-menu"
            aria-controls="sidebar-menu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <h1 class="navbar-brand navbar-brand-autodark">
            <a href="{{ route('admin.dashboard') }}">
                @if ($siteLogo)
                    <img src="{{ asset($siteLogo) }}" alt="{{ $siteName }} logo" class="navbar-brand-image" style="height: 54px;">
                @else
                    <span class="navbar-brand-image text-white fw-bold">{{ $siteName }}</span>
                @endif
            </a>
        </h1>
        <div class="navbar-nav flex-row d-lg-none">

            <div class="d-none d-lg-flex">
                <a href="?theme=dark" class="nav-link px-0 hide-theme-dark" title="Enable dark mode"
                    data-bs-toggle="tooltip" data-bs-placement="bottom">
                    <!-- Download SVG icon from http://tabler-icons.io/i/moon -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                        viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round"
                        stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <path d="M12 3c.132 0 .263 0 .393 0a7.5 7.5 0 0 0 7.92 12.446a9 9 0 1 1 -8.313 -12.454z" />
                    </svg>
                </a>
                <a href="?theme=light" class="nav-link px-0 hide-theme-light" title="Enable light mode"
                    data-bs-toggle="tooltip" data-bs-placement="bottom">
                    <!-- Download SVG icon from http://tabler-icons.io/i/sun -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                        viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round"
                        stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <path d="M12 12m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0" />
                        <path
                            d="M3 12h1m8 -9v1m8 8h1m-9 8v1m-6.4 -15.4l.7 .7m12.1 -.7l-.7 .7m0 11.4l.7 .7m-12.1 -.7l-.7 .7" />
                    </svg>
                </a>

            </div>
        </div>


        <div class="collapse navbar-collapse" id="sidebar-menu">
            <ul class="navbar-nav pt-lg-3">

                {{-- Home nav --}}
                <li class="nav-item">
                    <a class="nav-link {{ $isDashboard ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <i class="ti ti-home"></i>
                        </span>
                        <span class="nav-link-title">
                            Home
                        </span>
                    </a>
                </li>

                <li class="nav-item dropdown {{ $isCandidates ? 'active show' : '' }}">
                    <a class="nav-link dropdown-toggle {{ $isCandidates ? 'show' : '' }}" href="#candidates-menu"
                        data-bs-toggle="dropdown" data-bs-auto-close="false" role="button"
                        aria-expanded="{{ $isCandidates ? 'true' : 'false' }}">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <i class="ti ti-users"></i>
                        </span>
                        <span class="nav-link-title">
                            Candidates
                        </span>
                    </a>
                    <div class="dropdown-menu {{ $isCandidates ? 'show' : '' }}">
                        <div class="dropdown-menu-columns">
                            <div class="dropdown-menu-column">
                                <a class="dropdown-item {{ request()->routeIs('admin.candidates.index') ? 'active' : '' }}"
                                    href="{{ route('admin.candidates.index') }}">
                                    <i class="ti ti-list me-2"></i>
                                    All Candidates
                                </a>
                                <a class="dropdown-item {{ request()->routeIs('admin.candidates.create') ? 'active' : '' }}"
                                    href="{{ route('admin.candidates.create') }}">
                                    <i class="ti ti-plus me-2"></i>
                                    Add Candidate
                                </a>
                                <a class="dropdown-item {{ request()->routeIs('admin.candidates.upload') ? 'active' : '' }}"
                                    href="{{ route('admin.candidates.upload') }}">
                                    <i class="ti ti-upload me-2"></i>
                                    Upload Candidates
                                </a>
                            </div>
                        </div>
                    </div>
                </li>

                <li class="nav-item dropdown {{ $isDepartments ? 'active show' : '' }}">
                    <a class="nav-link dropdown-toggle {{ $isDepartments ? 'show' : '' }}" href="#departments-menu"
                        data-bs-toggle="dropdown" data-bs-auto-close="false" role="button"
                        aria-expanded="{{ $isDepartments ? 'true' : 'false' }}">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <i class="ti ti-building"></i>
                        </span>
                        <span class="nav-link-title">
                            Departments
                        </span>
                    </a>
                    <div class="dropdown-menu {{ $isDepartments ? 'show' : '' }}">
                        <div class="dropdown-menu-columns">
                            <div class="dropdown-menu-column">
                                <a class="dropdown-item {{ request()->routeIs('admin.departments.index') ? 'active' : '' }}"
                                    href="{{ route('admin.departments.index') }}">
                                    <i class="ti ti-list me-2"></i>
                                    All Departments
                                </a>
                                <a class="dropdown-item {{ request()->routeIs('admin.departments.create') ? 'active' : '' }}"
                                    href="{{ route('admin.departments.create') }}">
                                    <i class="ti ti-plus me-2"></i>
                                    Add Department
                                </a>
                            </div>
                        </div>
                    </div>
                </li>

                <li class="nav-item dropdown {{ $isQuestionBanks || $isSubjects ? 'active show' : '' }}">
                    <a class="nav-link dropdown-toggle {{ $isQuestionBanks || $isSubjects ? 'show' : '' }}" href="#question-banks" data-bs-toggle="dropdown"
                        data-bs-auto-close="false" role="button" aria-expanded="{{ $isQuestionBanks || $isSubjects ? 'true' : 'false' }}">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <i class="ti ti-notes"></i>
                        </span>
                        <span class="nav-link-title">
                            Question Banks
                        </span>
                    </a>
                    <div class="dropdown-menu {{ $isQuestionBanks || $isSubjects ? 'show' : '' }}">
                        <div class="dropdown-menu-columns">
                            <div class="dropdown-menu-column">
                                <a class="dropdown-item {{ request()->routeIs('admin.subjects.*') ? 'active' : '' }}" href="{{ route('admin.subjects.index') }}">
                                    <i class="ti ti-book me-2"></i>
                                    Subjects
                                </a>
                                <a class="dropdown-item {{ request()->routeIs('admin.question-banks.*') ? 'active' : '' }}" href="{{ route('admin.question-banks.index') }}">
                                    <i class="ti ti-checkbox me-2"></i>
                                    Questions
                                </a>
                                <a class="dropdown-item {{ request()->routeIs('admin.question-banks.create') ? 'active' : '' }}" href="{{ route('admin.question-banks.create') }}">
                                    <i class="ti ti-plus me-2"></i>
                                    New Question
                                </a>
                                <a class="dropdown-item {{ request()->routeIs('admin.question-banks.upload') ? 'active' : '' }}" href="{{ route('admin.question-banks.upload') }}">
                                    <i class="ti ti-upload me-2"></i>
                                    Upload Question
                                </a>
                            </div>
                        </div>
                    </div>
                </li>
                <li class="nav-item dropdown {{ $isExams ? 'active show' : '' }}">
                    <a class="nav-link dropdown-toggle {{ $isExams ? 'show' : '' }}" href="#exams-menu"
                        data-bs-toggle="dropdown" data-bs-auto-close="false" role="button"
                        aria-expanded="{{ $isExams ? 'true' : 'false' }}">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <i class="ti ti-clipboard"></i>
                        </span>
                        <span class="nav-link-title">
                            Exams
                        </span>
                    </a>
                    <div class="dropdown-menu {{ $isExams ? 'show' : '' }}">
                        <div class="dropdown-menu-columns">
                            <div class="dropdown-menu-column">
                                <a class="dropdown-item {{ request()->routeIs('admin.exams.index') ? 'active' : '' }}"
                                    href="{{ route('admin.exams.index') }}">
                                    <i class="ti ti-list me-2"></i>
                                    All Exams
                                </a>
                                <a class="dropdown-item {{ request()->routeIs('admin.exams.create') ? 'active' : '' }}"
                                    href="{{ route('admin.exams.create') }}">
                                    <i class="ti ti-plus me-2"></i>
                                    Add Exam
                                </a>
                            </div>
                        </div>
                    </div>
                </li>

                <li class="nav-item {{ $isAttempts ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('admin.attempts.index') }}">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <i class="ti ti-clipboard-check"></i>
                        </span>
                        <span class="nav-link-title">
                            Attempts
                        </span>
                    </a>
                </li>

                <li class="nav-item dropdown {{ $isResults ? 'active show' : '' }}">
                    <a class="nav-link dropdown-toggle {{ $isResults ? 'show' : '' }}" href="#results-menu"
                        data-bs-toggle="dropdown" data-bs-auto-close="false" role="button"
                        aria-expanded="{{ $isResults ? 'true' : 'false' }}">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <i class="ti ti-report-analytics"></i>
                        </span>
                        <span class="nav-link-title">
                            Result Management
                        </span>
                    </a>
                    <div class="dropdown-menu {{ $isResults ? 'show' : '' }}">
                        <div class="dropdown-menu-columns">
                            <div class="dropdown-menu-column">
                                <a class="dropdown-item {{ request()->routeIs('admin.results.index') ? 'active' : '' }}"
                                    href="{{ route('admin.results.index') }}">
                                    <i class="ti ti-list-details me-2"></i>
                                    Manage Results
                                </a>
                            </div>
                        </div>
                    </div>
                </li>

                <li class="nav-item {{ $isMonitor ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('admin.monitor-exams.index') }}">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <i class="ti ti-activity"></i>
                        </span>
                        <span class="nav-link-title">
                            Monitor Live Exam
                        </span>
                    </a>
                </li>

                <li class="nav-item {{ $isSupport ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('admin.support-tickets.index') }}">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <i class="ti ti-headset"></i>
                        </span>
                        <span class="nav-link-title">
                            Support
                        </span>
                    </a>
                </li>

                <li class="nav-item {{ $isLogs ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('admin.logs.index') }}">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <i class="ti ti-clipboard-text"></i>
                        </span>
                        <span class="nav-link-title">
                            System Logs
                        </span>
                    </a>
                </li>

                <li class="nav-item {{ $isSettings ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('admin.settings.edit') }}">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <i class="ti ti-settings"></i>
                        </span>
                        <span class="nav-link-title">
                            Settings
                        </span>
                    </a>
                </li>

                <li class="nav-item">
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <a class="nav-link" href="javascript:;" onclick="event.preventDefault(); this.closest('form').submit();">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <i class="ti ti-logout"></i>
                            </span>
                            <span class="nav-link-title">
                                Logout
                            </span>
                        </a>
                    </form>
                </li>





            </ul>
        </div>
    </div>
</aside>

<!-- Navbar -->
<header class="navbar navbar-expand-md d-none d-lg-flex d-print-none">
    <div class="container-xl">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu"
            aria-controls="navbar-menu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="navbar-nav flex-row order-md-last ">
            <div class="d-none d-md-flex me-4">
                <a href="?theme=dark" class="nav-link px-0 hide-theme-dark " data-bs-toggle="tooltip"
                    data-bs-placement="bottom" aria-label="Enable dark mode"
                    data-bs-original-title="Enable dark mode">
                    <!-- Download SVG icon from http://tabler-icons.io/i/moon -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                        viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                        <path d="M12 3c.132 0 .263 0 .393 0a7.5 7.5 0 0 0 7.92 12.446a9 9 0 1 1 -8.313 -12.454z"></path>
                    </svg>
                </a>
                <a href="?theme=light" class="nav-link px-0 hide-theme-light" data-bs-toggle="tooltip"
                    data-bs-placement="bottom" aria-label="Enable light mode"
                    data-bs-original-title="Enable light mode">
                    <!-- Download SVG icon from http://tabler-icons.io/i/sun -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                        viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                        <path d="M12 12m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0"></path>
                        <path
                            d="M3 12h1m8 -9v1m8 8h1m-9 8v1m-6.4 -15.4l.7 .7m12.1 -.7l-.7 .7m0 11.4l.7 .7m-12.1 -.7l-.7 .7">
                        </path>
                    </svg>
                </a>

            </div>
        </div>
        <div class="collapse navbar-collapse" id="navbar-menu">

        </div>
    </div>
</header>

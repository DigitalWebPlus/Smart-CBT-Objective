<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <meta name="csrf" content="{{ csrf_token() }}" />
    <title>{{ config('settings.site_name', config('app.name', 'CBT Objective')) }} - Admin</title>

     <!-- Favicon icon-->
    {{-- <link rel="icon" href="{{ asset(config('settings.favicon')) }}" sizes="16x16"> --}}

    <!-- Libs CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tabler-icons/3.21.0/tabler-icons.min.css"
        integrity="sha512-XrgoTBs7P5YtpkeKqKOKkruURsawIaRrhe8QrcWeMnFeyRZiOcRNjBAX+AQeXOvx9/9fSY32dVct1PccRoCICQ=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Select2 CSS -->
    <link rel="stylesheet" href="{{ asset('assets/dashboard/css/select2.min.css') }}">

    <!-- CSS files -->
    <link href="{{ asset('assets/dashboard/css/tabler.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/dashboard/css/tabler-flags.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/dashboard/css/tabler-payments.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/dashboard/css/tabler-vendors.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/dashboard/css/demo.min.css') }}" rel="stylesheet" />

    <!-- Global CSS files -->
    <link href="{{ asset('assets/global/css/default/bootstrap-tagsinput.css') }}" rel="stylesheet" />

    @stack('styles')

    <style>
        @import url('https://rsms.me/inter/inter.css');

        :root {
            --tblr-font-sans-serif: 'Inter Var', -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif;
        }

        body {
            font-feature-settings: "cv03", "cv04", "cv11";
        }

        .page-header {
            margin-bottom: 1.5rem;
        }

        .page-header .container-xl {
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.06);
        }

    </style>

</head>

<body>
    <script src="{{ asset('assets/dashboard/js/demo-theme.min.js') }}"></script>
    <div class="page">

        <!-- Sidebar -->
        @include('admin.layouts.sidebar')

        <!-- Dynamic Content -->
        @yield('content')

        <footer class="footer footer-transparent d-print-none">
            <div class="container-xl">
                <div class="row text-right align-items-right flex-row-reverse">

                    <div class="col-12 col-lg-auto mt-3 mt-lg-0">
                        <ul class="list-inline list-inline-dots mb-0">
                            <li class="list-inline-item">
                                Copyright &copy; {{ date('Y') }}
                                <a href="https://digitalwebplus.com/product/smart-cbt-objective" target="_blank" class="link-primary">DigitalWeb Plus</a> |
                                All rights reserved.
                            </li>

                        </ul>
                    </div>
                </div>
            </div>
        </footer>

    </div>

    <!-- Libs JS -->
    <script src="{{ asset('assets/dashboard/js/jquery.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('assets/global/js/sweetalert2.js') }}"></script>
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

    <!-- Tabler Core -->
    <script src="{{ asset('assets/dashboard/js/tabler.min.js') }}" defer></script>
    <script src="{{ asset('assets/dashboard/js/demo.min.js') }}" defer></script>

    {{-- <!-- TinyMce -->
    <script src="{{ asset('assets/global/js/tinymce/tinymce.min.js') }}"></script> --}}

    <!-- Admin JS - Initialization files -->
    <script src="{{ asset('assets/dashboard/js/default/admin.js') }}" defer></script>

    <!-- jS for Select2 -->
    <script src="{{ asset('assets/dashboard/js/select2.min.js') }}"></script>

     <!-- Global JS Files -->
     <script src="{{ asset('assets/global/js/default/bootstrap-tagsinput.min.js') }}"></script>

    @stack('scripts')

</body>

</html>

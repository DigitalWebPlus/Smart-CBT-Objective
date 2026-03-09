<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.css">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
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
    </body>
</html>

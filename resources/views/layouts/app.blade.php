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

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen" style="background: linear-gradient(135deg, #14213D 0%, #000000 50%, #14213D 100%);">
            <livewire:layout.navigation />

            <!-- Page Heading -->
            @if (isset($header))
                <header class="shadow" style="background: rgba(20, 33, 61, 0.8); backdrop-filter: blur(10px);">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        <div style="color: #FFFFFF;">
                            {{ $header }}
                        </div>
                    </div>
                </header>
            @endif

            <!-- Page Content -->
            <main style="background: linear-gradient(135deg, #14213D 0%, #000000 50%, #14213D 100%); min-height: 100vh;">
                {{ $slot }}
            </main>
        </div>

        @stack('scripts')
    </body>
</html>

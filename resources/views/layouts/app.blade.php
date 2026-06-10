<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="/favicon-96x96.png?v=20260610" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="/favicon.svg?v=20260610" />
    <link rel="shortcut icon" href="/favicon.ico?v=20260610" />
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png?v=20260610" />
    <meta name="apple-mobile-web-app-title" content="318" />
    <link rel="manifest" href="/site.webmanifest?v=20260610" />

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="antialiased m-0">
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
        @include('layouts.navigation')

        <!-- Page Heading -->
        @isset($header)
        <header class="bg-white dark:bg-gray-800 shadow">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                {{ $header }}
            </div>
        </header>
        @endisset

        <!-- Page Content -->
        <main>
            @if (session('success'))
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-4">
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            </div>
            @endif

            @if (session('error'))
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-4">
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            </div>
            @endif

            @if ($errors->any())
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-4">
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                        @if($error == 'validation.max.file' || str_contains($error, 'greater than'))
                        <li>O arquivo enviado é muito grande. O tamanho máximo permitido é 50MB.</li>
                        @else
                        <li>{{ $error }}</li>
                        @endif
                        @endforeach
                    </ul>
                </div>
            </div>
            @endif

            {{ $slot }}
        </main>
    </div>
</body>

</html>
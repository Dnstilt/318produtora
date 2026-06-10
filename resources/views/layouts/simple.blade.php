<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="/favicons/favicon-96x96.png?v=20260610" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="/favicons/favicon.svg?v=20260610" />
    <link rel="shortcut icon" href="/favicons/favicon.ico?v=20260610" />
    <link rel="apple-touch-icon" sizes="180x180" href="/favicons/apple-touch-icon.png?v=20260610" />
    <meta name="apple-mobile-web-app-title" content="318" />
    <link rel="manifest" href="/site.webmanifest?v=20260610" />
    <title>{{ $title ?? '318 Produtora' }}</title>
    @vite(['resources/css/app.css'])
</head>

<body class="bg-white text-gray-900 antialiased">
    @yield('content')
</body>

</html>

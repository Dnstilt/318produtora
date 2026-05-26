<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? '318 Produtora' }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-white text-gray-900 antialiased">
@yield('content')
</body>
</html>


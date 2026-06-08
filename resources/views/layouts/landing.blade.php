<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>318 Produtora</title>
    @vite(['resources/css/app.css', 'resources/js/landing.js'])
</head>

<body class="bg-black text-white antialiased">
    @yield('content')
</body>

</html>
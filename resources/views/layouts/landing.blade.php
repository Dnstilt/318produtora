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
    <title>318 Produtora</title>

    <meta name="google-site-verification" content="kSk_cf24AnZzPsKL8LwpiUL0i9bm1rQvuueAQExiRsU" />
    <meta name="description" content="318 Produtora — Produção audiovisual de publicidade, OOH, documentários e natureza.">
    <meta name="keywords" content="produtora de vídeo, publicidade, documentários, produção audiovisual, Brasil, São Paulo, SP">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://318produtora.com.br">
    <!-- Open Graph -->
    <meta property="og:title" content="318 Produtora">
    <meta property="og:description" content="Produção audiovisual de publicidade, OOH, documentários e natureza.">
    <meta property="og:image" content="https://318produtora.com.br/logo/Logo_318_Produtora_laranja.png">
    <meta property="og:url" content="https://318produtora.com.br">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="pt_BR">
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="318 Produtora">
    <meta name="twitter:description" content="Produção audiovisual de publicidade, OOH, documentários e natureza.">
    <meta name="twitter:image" content="https://318produtora.com.br/logo/Logo_318_Produtora_laranja.png">


    @vite(['resources/css/app.css', 'resources/js/landing.js'])
</head>

<body class="bg-black text-white antialiased">
    @yield('content')
</body>

</html>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>318 Produtora</title>
    @vite(['resources/css/app.css', 'resources/js/landing.js'])
    <style>
        .nav-item {
            position: relative;
            /* contexto para o label absoluto */
            height: 40px;
            display: flex;
            align-items: center;
            opacity: 0.90;
            transition: opacity 200ms ease-out;
            overflow: visible;
            /* não cortar o label */
            white-space: nowrap;
        }
 
        .nav-item:hover,
        .nav-item.is-active {
            opacity: 1;
        }

        /* ícone — ocupa espaço fixo, some no hover */
        .nav-icon {
            width: 40px;
            height: 40px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: opacity 200ms ease-out;
        }

        .nav-item:hover .nav-icon {
            opacity: 0;
        }

        /* label — flutua sobre o ícone, aparece no hover */
        .nav-label {
            position: absolute;
            left: 0;
            padding-left: 12px;
            font-weight: 500;
            letter-spacing: 0.04em;
            opacity: 0;
            pointer-events: none;
            transition: opacity 200ms ease-out;
        }

        .nav-item:hover .nav-label {
            opacity: 1;
        }
    </style>
</head>

<body class="bg-black text-white antialiased">
    @yield('content')
</body>

</html>
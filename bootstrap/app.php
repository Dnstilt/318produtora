<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'auth' => \Illuminate\Auth\Middleware\Authenticate::class,
            'guest' => \Illuminate\Auth\Middleware\RedirectIfAuthenticated::class,
            'can' => \Illuminate\Auth\Middleware\Authorize::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\SecurityHeaders::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Illuminate\Http\Exceptions\PostTooLargeException $e, \Illuminate\Http\Request $request) {
            \Illuminate\Support\Facades\Log::warning('http.post_too_large', [
                'path' => $request->path(),
                'method' => $request->method(),
                'ip' => $request->ip(),
                'content_length' => $request->server('CONTENT_LENGTH'),
            ]);

            return redirect()
                ->back()
                ->with('error', 'O arquivo enviado é grande demais para o limite atual do servidor (PHP). Aumente post_max_size e upload_max_filesize.');
        });
    })->create();

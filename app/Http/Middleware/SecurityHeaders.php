<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Permissions-Policy', implode(', ', [
            'camera=()',
            'microphone=()',
            'geolocation=()',
            'payment=()',
        ]));
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('Cross-Origin-Resource-Policy', 'same-origin');

        $csp = $this->buildCsp();
        $response->headers->set('Content-Security-Policy', $csp);

        if (app()->environment('production') && $request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    private function buildCsp(): string
    {
        $connect = ["'self'"];
        $script  = ["'self'", "'unsafe-inline'", "'unsafe-eval'"];
        $style   = ["'self'", "'unsafe-inline'", 'https://fonts.bunny.net'];

        if (app()->environment('local')) {
            $viteSrc = [
                'http://localhost:5173',
                'http://127.0.0.1:5173',
                'http://[::1]:5173',
            ];
            $viteWs = [
                'ws://localhost:5173',
                'ws://127.0.0.1:5173',
                'ws://[::1]:5173',
            ];
            $connect = array_merge($connect, $viteSrc, $viteWs);
            $script  = array_merge($script,  $viteSrc);
            $style   = array_merge($style,   $viteSrc);
        }

        $script = ["'self'", "'unsafe-inline'", "'unsafe-eval'"];

        $style = ["'self'", "'unsafe-inline'", 'https://fonts.bunny.net'];

        $directives = [
            "default-src 'self'",
            'base-uri \'self\'',
            'object-src \'none\'',
            'frame-ancestors \'none\'',
            'img-src \'self\' data: blob:',
            'media-src \'self\' blob: https://res.cloudinary.com',
            'font-src \'self\' https://fonts.bunny.net data:',
            'script-src ' . implode(' ', $script),
            'style-src ' . implode(' ', $style),
            'connect-src ' . implode(' ', $connect),
        ];

        if (app()->environment('production')) {
            $directives[] = 'upgrade-insecure-requests';
        }

        return implode('; ', $directives);
    }
}

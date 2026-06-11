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
        if (app()->environment('local')) {
            $connect[] = 'http://localhost:5173';
            $connect[] = 'ws://localhost:5173';
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
            'script-src '.implode(' ', $script),
            'style-src '.implode(' ', $style),
            'connect-src '.implode(' ', $connect),
        ];

        if (app()->environment('production')) {
            $directives[] = 'upgrade-insecure-requests';
        }

        return implode('; ', $directives);
    }
}

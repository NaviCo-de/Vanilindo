<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        Vite::useCspNonce();
        $nonce = Vite::cspNonce();
        $admin = $request->is('admin', 'admin/*', 'livewire*');
        $response = $next($request);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        // Filament's Alpine expressions require eval and dynamic style attributes.
        // The public site uses neither; inline scripts still require a nonce everywhere.
        if (! app()->environment('local')) {
            $scripts = "'self' 'nonce-{$nonce}'".($admin ? " 'unsafe-eval'" : '');
            $styles = $admin ? "'self' 'nonce-{$nonce}'" : "'self'";
            $styleAttributes = $admin ? "'unsafe-inline'" : "'none'";
            $images = "'self' data: blob:".($admin ? ' https://ui-avatars.com' : '');
            $workers = $admin ? "'self' blob:" : "'none'";
            $response->headers->set('Content-Security-Policy',
                "default-src 'self'; script-src {$scripts}; style-src {$styles}; style-src-attr {$styleAttributes}; "
                ."img-src {$images}; worker-src {$workers}; font-src 'self' data:; connect-src 'self'; "
                ."base-uri 'self'; object-src 'none'; frame-ancestors 'self'; form-action 'self'"
            );
        }
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        if ($admin) {
            $response->headers->set('Cache-Control', 'no-store, private');
        }

        return $response;
    }
}

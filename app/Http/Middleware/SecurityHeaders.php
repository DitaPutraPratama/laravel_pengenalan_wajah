<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = Vite::useCspNonce();
        $response = $next($request);

        $response->headers->set('Permissions-Policy', 'camera=(self), microphone=(), geolocation=()');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'no-referrer');

        if ($request->is('api/*')) {
            $response->headers->set('Cache-Control', 'no-store');
        } else {
            $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicy($nonce));
        }

        return $response;
    }

    private function contentSecurityPolicy(string $nonce): string
    {
        $model = $this->origin((string) config('face.model_url'));
        // Vite dev server (composer dev) serves scripts, styles and HMR from another port.
        $dev = Vite::isRunningHot() ? $this->origin(trim((string) file_get_contents(public_path('hot')))) : '';
        $devSocket = $dev !== '' ? preg_replace('#^http#', 'ws', $dev) : '';

        return implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}' 'wasm-unsafe-eval' {$dev}",
            "style-src 'self' 'unsafe-inline' {$dev}",
            "connect-src 'self' {$model} {$dev} {$devSocket}",
            "img-src 'self' data: blob:",
            "media-src 'self' blob: mediastream:",
            "worker-src 'self' blob:",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
        ]);
    }

    private function origin(string $url): string
    {
        $parts = parse_url($url);

        if (! isset($parts['scheme'], $parts['host'])) {
            return '';
        }

        $host = str_contains($parts['host'], ':') && ! str_starts_with($parts['host'], '[') ? "[{$parts['host']}]" : $parts['host'];

        return $parts['scheme'].'://'.$host.(isset($parts['port']) ? ':'.$parts['port'] : '');
    }
}

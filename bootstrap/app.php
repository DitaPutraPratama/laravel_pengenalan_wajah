<?php

declare(strict_types=1);

use App\Enums\FaceErrorCode;
use App\Exceptions\Face\FaceException;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Behind Vercel (TRUSTED_PROXIES=* in vercel.json) this gives Laravel the real
        // https scheme and client IP. Unset elsewhere, so X-Forwarded-* is ignored.
        $middleware->trustProxies(at: env('TRUSTED_PROXIES'));
        $middleware->append(SecurityHeaders::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontReport(FaceException::class);

        // Every /api/* error uses the format in docs/API.md. Unknown errors become server_error without details.
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return match (true) {
                $e instanceof FaceException => $e->render(),
                $e instanceof ValidationException => FaceException::response(FaceErrorCode::ValidationFailed, [
                    'fields' => array_values(array_unique(array_map(
                        fn (string $key): string => (string) preg_replace('/\.\d+(?=\.|$)/', '.*', $key),
                        array_keys($e->errors()),
                    ))),
                ]),
                $e instanceof ThrottleRequestsException => FaceException::response(FaceErrorCode::RateLimited, [
                    'retry_after' => (int) ($e->getHeaders()['Retry-After'] ?? 60),
                ])->withHeaders($e->getHeaders()),
                default => FaceException::response(FaceErrorCode::ServerError),
            };
        });
    })->create();

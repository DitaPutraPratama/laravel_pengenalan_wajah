<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Keep tokens, names and embeddings out of stack traces in the log.
        ini_set('zend.exception_ignore_args', '1');

        foreach (['sessions', 'enroll', 'login'] as $name) {
            RateLimiter::for("face-{$name}", fn (Request $request) => Limit::perMinute((int) config("face.rate_limits.{$name}"))->by($request->ip()));
        }
    }
}

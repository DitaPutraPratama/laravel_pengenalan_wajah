<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\FaceErrorCode;
use App\Exceptions\Face\FaceException;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/** Refuses /api/face/* while a required FACE_* variable is empty (F-13). */
class EnsureFaceConfigured
{
    private const REQUIRED = [
        'FACE_MATCH_THRESHOLD' => 'face.match_threshold',
        'FACE_EMBEDDING_DIM' => 'face.embedding_dim',
        'FACE_MODEL_VERSION' => 'face.model_version',
        'FACE_MODEL_URL' => 'face.model_url',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $missing = array_keys(array_filter(self::REQUIRED, fn (string $key): bool => blank(config($key))));

        if ($missing !== []) {
            Log::error('Face config is missing required variables.', ['variables' => $missing]);

            throw new FaceException(FaceErrorCode::ServerError);
        }

        return $next($request);
    }
}

<?php

declare(strict_types=1);

namespace App\Exceptions\Face;

use App\Enums\FaceErrorCode;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class FaceException extends RuntimeException
{
    /** @param array<string, mixed> $details */
    public function __construct(public readonly FaceErrorCode $errorCode, public readonly array $details = [])
    {
        parent::__construct($errorCode->message());
    }

    /** @param array<string, mixed> $details */
    public static function response(FaceErrorCode $code, array $details = []): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => $code->value,
                'message' => $code->message(),
                'details' => (object) $details,
            ],
        ], $code->status());
    }

    public function render(): JsonResponse
    {
        return self::response($this->errorCode, $this->details);
    }
}

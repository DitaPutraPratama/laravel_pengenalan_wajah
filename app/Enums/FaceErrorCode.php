<?php

declare(strict_types=1);

namespace App\Enums;

enum FaceErrorCode: string
{
    case ValidationFailed = 'validation_failed';
    case ConsentRequired = 'consent_required';
    case SessionInvalid = 'session_invalid';
    case LivenessFailed = 'liveness_failed';
    case FaceAlreadyRegistered = 'face_already_registered';
    case FaceNotFound = 'face_not_found';
    case CapacityReached = 'capacity_reached';
    case RateLimited = 'rate_limited';
    case ServerError = 'server_error';

    public function status(): int
    {
        return match ($this) {
            self::ValidationFailed, self::ConsentRequired, self::SessionInvalid, self::LivenessFailed => 422,
            self::FaceAlreadyRegistered => 409,
            self::FaceNotFound => 404,
            self::CapacityReached => 503,
            self::RateLimited => 429,
            self::ServerError => 500,
        };
    }

    /** Generic developer-facing text. The UI maps the code to lang/id/face.php instead. */
    public function message(): string
    {
        return match ($this) {
            self::ValidationFailed => 'The given data was invalid.',
            self::ConsentRequired => 'Consent is required.',
            self::SessionInvalid => 'The liveness session is invalid, expired, or already used.',
            self::LivenessFailed => 'Liveness telemetry did not pass validation.',
            self::FaceAlreadyRegistered => 'This face is already registered.',
            self::FaceNotFound => 'No matching face was found.',
            self::CapacityReached => 'The template limit has been reached.',
            self::RateLimited => 'Too many requests.',
            self::ServerError => 'Internal server error.',
        };
    }
}

<?php

declare(strict_types=1);

namespace App\Services\Face;

use App\Enums\ChallengeType;
use App\Enums\FaceErrorCode;
use App\Exceptions\Face\FaceException;
use App\Models\LivenessSession;

/**
 * Plausibility check of browser telemetry (F-03, ADR-008).
 * Not an anti-spoofing guarantee: a crafted client can still fake these numbers.
 */
final class LivenessValidator
{
    /** @param list<array<string, mixed>> $challenges */
    public function validate(LivenessSession $session, array $challenges): void
    {
        if (! $this->passes($session, $challenges)) {
            throw new FaceException(FaceErrorCode::LivenessFailed);
        }
    }

    /** @param list<array<string, mixed>> $challenges */
    private function passes(LivenessSession $session, array $challenges): bool
    {
        $rules = config('face.liveness');

        if (array_column($challenges, 'type') !== $session->challenges) {
            return false;
        }

        $total = 0;
        foreach ($challenges as $challenge) {
            $duration = (int) $challenge['duration_ms'];
            $total += $duration;

            if ($duration < $rules['min_duration_ms'] || $duration > $rules['max_duration_ms'] || (int) $challenge['frames'] < $rules['min_frames']) {
                return false;
            }

            $passed = match (ChallengeType::from($challenge['type'])) {
                ChallengeType::Blink => $this->blinked($challenge, $rules),
                ChallengeType::TurnRight => $this->turned($challenge, 1, $rules),
                ChallengeType::TurnLeft => $this->turned($challenge, -1, $rules),
            };

            if (! $passed) {
                return false;
            }
        }

        $elapsedMs = $session->created_at->diffInMilliseconds(now());

        return $total <= $elapsedMs + $rules['clock_tolerance_ms'];
    }

    /**
     * @param  array<string, mixed>  $c
     * @param  array<string, float|int>  $rules
     */
    private function blinked(array $c, array $rules): bool
    {
        if (! isset($c['min_ear'], $c['max_ear'])) {
            return false;
        }

        $min = (float) $c['min_ear'];
        $max = (float) $c['max_ear'];

        return $min >= 0 && $max <= 1 && $min < $rules['ear_closed'] && $max > $rules['ear_open'];
    }

    /**
     * Yaw convention: positive = the user's right (docs/API.md).
     *
     * @param  array<string, mixed>  $c
     * @param  array<string, float|int>  $rules
     */
    private function turned(array $c, int $direction, array $rules): bool
    {
        if (! isset($c['peak_yaw'], $c['end_yaw'])) {
            return false;
        }

        $peak = (float) $c['peak_yaw'] * $direction;
        $end = abs((float) $c['end_yaw']);

        return $peak >= $rules['yaw_peak'] && $peak <= 90 && $end <= $rules['yaw_return'];
    }
}

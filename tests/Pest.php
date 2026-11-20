<?php

declare(strict_types=1);

use App\Models\LivenessSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)->use(RefreshDatabase::class)->in('Feature', 'Unit');

/** Telemetry that passes every rule for the given challenge order. */
function passingTelemetry(array $order): array
{
    return array_map(fn (string $type) => match ($type) {
        'blink' => ['type' => 'blink', 'duration_ms' => 400, 'frames' => 12, 'min_ear' => 0.12, 'max_ear' => 0.32],
        'turn_right' => ['type' => 'turn_right', 'duration_ms' => 400, 'frames' => 12, 'peak_yaw' => 26.0, 'end_yaw' => 3.0],
        'turn_left' => ['type' => 'turn_left', 'duration_ms' => 400, 'frames' => 12, 'peak_yaw' => -26.0, 'end_yaw' => -2.0],
    }, $order);
}

/** Creates a session through the API and returns [token, challenges]. */
function startSession(string $purpose): array
{
    $data = test()->postJson('/api/face/sessions', ['purpose' => $purpose])->assertCreated()->json('data');

    return [$data['session_token'], $data['challenges']];
}

/** A full, valid scan payload for login/enroll. Moves the clock so durations are plausible. */
function scanPayload(string $purpose, array $embedding): array
{
    [$token, $order] = startSession($purpose);
    test()->travel(5)->seconds();

    return ['embedding' => $embedding, 'liveness' => ['session_token' => $token, 'challenges' => passingTelemetry($order)]];
}

function sessionRow(string $token): LivenessSession
{
    return LivenessSession::query()->where('token_hash', hash('sha256', $token))->firstOrFail();
}

<?php

declare(strict_types=1);

use App\Exceptions\Face\FaceException;
use App\Models\LivenessSession;
use App\Services\Face\LivenessValidator;

function sessionWith(array $order): LivenessSession
{
    return LivenessSession::query()->create([
        'token_hash' => hash('sha256', (string) mt_rand()),
        'purpose' => 'enroll',
        'challenges' => $order,
        'expires_at' => now()->addMinutes(2),
    ]);
}

$order = ['blink', 'turn_right', 'turn_left'];

it('passes valid telemetry', function () use ($order) {
    $session = sessionWith($order);
    $this->travel(5)->seconds();

    app(LivenessValidator::class)->validate($session, passingTelemetry($order));
})->throwsNoExceptions();

it('fails when a rule is broken', function (Closure $break) use ($order) {
    $session = sessionWith($order);
    $this->travel(5)->seconds();
    $telemetry = passingTelemetry($order);
    $break($telemetry);

    app(LivenessValidator::class)->validate($session, $telemetry);
})->throws(FaceException::class)->with([
    'wrong order' => [function (&$t) {
        [$t[0], $t[1]] = [$t[1], $t[0]];
    }],
    'missing challenge' => [function (&$t) {
        array_pop($t);
    }],
    'too short' => [function (&$t) {
        $t[0]['duration_ms'] = 200;
    }],
    'too long' => [function (&$t) {
        $t[0]['duration_ms'] = 16000;
    }],
    'too few frames' => [function (&$t) {
        $t[0]['frames'] = 4;
    }],
    'eyes never closed' => [function (&$t) {
        $t[0]['min_ear'] = 0.22;
    }],
    'eyes never reopened' => [function (&$t) {
        $t[0]['max_ear'] = 0.24;
    }],
    'blink values missing' => [function (&$t) {
        unset($t[0]['min_ear']);
    }],
    'turn too small' => [function (&$t) {
        $t[1]['peak_yaw'] = 15.0;
    }],
    'turned the wrong way' => [function (&$t) {
        $t[1]['peak_yaw'] = -26.0;
    }],
    'did not return to center' => [function (&$t) {
        $t[2]['end_yaw'] = -14.0;
    }],
    'impossible yaw' => [function (&$t) {
        $t[1]['peak_yaw'] = 120.0;
    }],
]);

it('fails when telemetry claims more time than the server saw', function () use ($order) {
    $session = sessionWith($order);
    $this->travel(1)->seconds();
    $telemetry = passingTelemetry($order);
    $telemetry[0]['duration_ms'] = 5000;

    app(LivenessValidator::class)->validate($session, $telemetry);
})->throws(FaceException::class);

<?php

declare(strict_types=1);

use App\Models\LivenessSession;

it('creates a session with a random token, shuffled challenges and a 120 s TTL', function () {
    $res = $this->postJson('/api/face/sessions', ['purpose' => 'enroll'])->assertCreated();

    $token = $res->json('data.session_token');
    expect(strlen($token))->toBeGreaterThanOrEqual(32)
        ->and($res->json('data.challenges'))->toEqualCanonicalizing(['blink', 'turn_right', 'turn_left'])
        ->and($res->json('data.expires_in'))->toBe(120)
        ->and($res->json('data.challenge_timeout'))->toBe(15);

    $row = sessionRow($token);
    expect($row->purpose)->toBe('enroll')
        ->and((int) round(now()->diffInSeconds($row->expires_at)))->toBe(120)
        ->and(LivenessSession::query()->where('token_hash', $token)->exists())->toBeFalse();
});

it('rejects an unknown purpose', function () {
    $this->postJson('/api/face/sessions', ['purpose' => 'attendance'])
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'validation_failed')
        ->assertJsonPath('error.details.fields', ['purpose']);
});

it('cleans up expired sessions sometimes', function () {
    config(['face.session.cleanup_lottery' => 1]);
    LivenessSession::query()->create(['token_hash' => str_repeat('a', 64), 'purpose' => 'enroll', 'challenges' => [], 'expires_at' => now()->subMinute()]);

    $this->postJson('/api/face/sessions', ['purpose' => 'identify'])->assertCreated();

    expect(LivenessSession::query()->count())->toBe(1);
});

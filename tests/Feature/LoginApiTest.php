<?php

declare(strict_types=1);

use App\Models\FaceTemplate;

it('signs in the account whose face matches and answers nothing else', function () {
    FaceTemplate::factory()->withEmbedding([0, 1, 0, 0, 0, 0, 0, 0])->create();
    $mine = FaceTemplate::factory()->withEmbedding([1, 0, 0, 0, 0, 0, 0, 0])->create();

    $res = $this->postJson('/api/face/login', scanPayload('login', [1, 0.02, 0, 0, 0, 0, 0, 0]))->assertOk();

    $this->assertAuthenticatedAs($mine);
    expect($res->json())->toBe(['data' => ['redirect' => route('face.dashboard')]]);
});

it('answers face_not_found and stays signed out for an unknown face', function () {
    FaceTemplate::factory()->withEmbedding([1, 0, 0, 0, 0, 0, 0, 0])->create();

    $this->postJson('/api/face/login', scanPayload('login', [0, 1, 0, 0, 0, 0, 0, 0]))
        ->assertNotFound()
        ->assertJsonPath('error.code', 'face_not_found');

    $this->assertGuest();
});

it('fails liveness and session checks like enroll', function () {
    FaceTemplate::factory()->withEmbedding([1, 0, 0, 0, 0, 0, 0, 0])->create();
    $payload = scanPayload('login', [1, 0, 0, 0, 0, 0, 0, 0]);
    $payload['liveness']['challenges'][0]['duration_ms'] = 100;

    $this->postJson('/api/face/login', $payload)->assertStatus(422)->assertJsonPath('error.code', 'liveness_failed');
    $this->postJson('/api/face/login', $payload)->assertStatus(422)->assertJsonPath('error.code', 'session_invalid');
    $this->assertGuest();
});

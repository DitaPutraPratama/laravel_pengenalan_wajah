<?php

declare(strict_types=1);

use App\Models\FaceTemplate;

it('answers registered true or false and nothing else', function (array $probe, bool $expected) {
    FaceTemplate::factory()->withEmbedding([1, 0, 0, 0, 0, 0, 0, 0])->create();

    $res = $this->postJson('/api/face/identify', scanPayload('identify', $probe))->assertOk();

    expect($res->json())->toBe(['data' => ['registered' => $expected]]);
})->with([
    'same face' => [[1, 0.02, 0, 0, 0, 0, 0, 0], true],
    'other face' => [[0, 1, 0, 0, 0, 0, 0, 0], false],
]);

it('fails liveness and session checks like enroll', function () {
    $payload = scanPayload('identify', [1, 0, 0, 0, 0, 0, 0, 0]);
    $payload['liveness']['challenges'][0]['duration_ms'] = 100;

    $this->postJson('/api/face/identify', $payload)->assertStatus(422)->assertJsonPath('error.code', 'liveness_failed');
    $this->postJson('/api/face/identify', $payload)->assertStatus(422)->assertJsonPath('error.code', 'session_invalid');
});

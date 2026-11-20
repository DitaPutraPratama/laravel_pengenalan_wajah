<?php

declare(strict_types=1);

use App\Models\FaceTemplate;
use Database\Factories\FaceTemplateFactory;

function enrollPayload(array $embedding, array $overrides = []): array
{
    return array_replace(scanPayload('enroll', $embedding), ['consent' => true, 'consent_version' => '1.0'], $overrides);
}

it('enrolls a face and returns a delete code once', function () {
    $res = $this->postJson('/api/face/enroll', enrollPayload(FaceTemplateFactory::randomVector(8)))->assertCreated();

    $code = $res->json('data.delete_code');
    $row = FaceTemplate::query()->sole();
    expect($code)->toMatch('/^[A-Z2-7]{26}$/')
        ->and($res->json('data'))->toHaveKeys(['delete_code'])->toHaveCount(1)
        ->and($row->delete_code_hash)->toBe(hash('sha256', $code))
        ->and($row->embedding_dim)->toBe(8)
        ->and($row->model_version)->toBe('test-v1')
        ->and($row->consent_version)->toBe('1.0')
        ->and($row->external_ref)->toBeNull();
});

it('stores no plaintext vector', function () {
    $this->postJson('/api/face/enroll', enrollPayload([1, 2, 3, 4, 5, 6, 7, 8]))->assertCreated();

    $stored = FaceTemplate::query()->sole()->embedding;
    expect($stored)->not->toContain('0.1')->not->toContain('[');
});

it('rejects a face that is already registered without leaking a score', function () {
    FaceTemplate::factory()->withEmbedding([1, 0, 0, 0, 0, 0, 0, 0])->create();

    $res = $this->postJson('/api/face/enroll', enrollPayload([0.99, 0.05, 0, 0, 0, 0, 0, 0]))
        ->assertStatus(409)
        ->assertJsonPath('error.code', 'face_already_registered');

    expect(FaceTemplate::query()->count())->toBe(1)
        ->and($res->getContent())->not->toContain('similar')->not->toContain('score');
});

it('requires consent', function (array $overrides) {
    $payload = enrollPayload(FaceTemplateFactory::randomVector(8));
    unset($payload['consent']);

    $this->postJson('/api/face/enroll', array_replace($payload, $overrides))
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'consent_required');
})->with([
    'missing' => [[]],
    'false' => [['consent' => false]],
    'string' => [['consent' => 'true']],
]);

it('rejects invalid input before touching the session', function (array $overrides, string $field) {
    $payload = enrollPayload(FaceTemplateFactory::randomVector(8), $overrides);

    $this->postJson('/api/face/enroll', $payload)
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'validation_failed')
        ->assertJsonPath('error.details.fields', fn (array $fields) => in_array($field, $fields, true));

    expect(sessionRow($payload['liveness']['session_token'])->used_at)->toBeNull();
})->with([
    'wrong dimension' => [['embedding' => [1, 2, 3]], 'embedding'],
    'not numbers' => [['embedding' => array_fill(0, 8, 'x')], 'embedding.*'],
    'other consent version' => [['consent_version' => '0.9'], 'consent_version'],
]);

it('rejects the zero vector', function () {
    $this->postJson('/api/face/enroll', enrollPayload(array_fill(0, 8, 0)))
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'validation_failed');
});

it('rejects an unknown, reused or wrong-purpose session', function () {
    $payload = enrollPayload(FaceTemplateFactory::randomVector(8));
    $this->postJson('/api/face/enroll', $payload)->assertCreated();

    // reused
    $this->postJson('/api/face/enroll', $payload)->assertStatus(422)->assertJsonPath('error.code', 'session_invalid');

    // unknown
    $payload['liveness']['session_token'] = str_repeat('f', 64);
    $this->postJson('/api/face/enroll', $payload)->assertStatus(422)->assertJsonPath('error.code', 'session_invalid');

    // wrong purpose
    $identify = scanPayload('identify', FaceTemplateFactory::randomVector(8));
    $this->postJson('/api/face/enroll', $identify + ['consent' => true, 'consent_version' => '1.0'])
        ->assertStatus(422)->assertJsonPath('error.code', 'session_invalid');
});

it('rejects an expired session', function () {
    $payload = enrollPayload(FaceTemplateFactory::randomVector(8));
    $this->travel(121)->seconds();

    $this->postJson('/api/face/enroll', $payload)->assertStatus(422)->assertJsonPath('error.code', 'session_invalid');
});

it('consumes the session even when liveness fails', function () {
    $payload = enrollPayload(FaceTemplateFactory::randomVector(8));
    $payload['liveness']['challenges'][0]['frames'] = 1;

    $this->postJson('/api/face/enroll', $payload)->assertStatus(422)->assertJsonPath('error.code', 'liveness_failed');

    expect(sessionRow($payload['liveness']['session_token'])->used_at)->not->toBeNull();
    $payload['liveness']['challenges'][0]['frames'] = 12;
    $this->postJson('/api/face/enroll', $payload)->assertStatus(422)->assertJsonPath('error.code', 'session_invalid');
});

it('stops at the template limit', function () {
    config(['face.max_templates' => 1]);
    FaceTemplate::factory()->create();

    $this->postJson('/api/face/enroll', enrollPayload(FaceTemplateFactory::randomVector(8)))
        ->assertStatus(503)
        ->assertJsonPath('error.code', 'capacity_reached');

    expect(FaceTemplate::query()->count())->toBe(1);
});

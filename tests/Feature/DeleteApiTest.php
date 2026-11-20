<?php

declare(strict_types=1);

use App\Models\FaceTemplate;
use App\Services\Face\DeleteCodeService;

it('deletes the template that owns the code', function () {
    $code = app(DeleteCodeService::class)->generate();
    $mine = FaceTemplate::factory()->create(['delete_code_hash' => hash('sha256', $code)]);
    $other = FaceTemplate::factory()->create();

    $this->postJson('/api/face/delete/code', ['delete_code' => strtolower(chunk_split($code, 4, ' '))])
        ->assertOk()
        ->assertExactJson(['data' => ['deleted' => true]]);

    expect(FaceTemplate::query()->find($mine->id))->toBeNull()
        ->and(FaceTemplate::query()->find($other->id))->not->toBeNull();
});

it('answers 404 for a wrong code', function () {
    FaceTemplate::factory()->create();

    $this->postJson('/api/face/delete/code', ['delete_code' => 'AAAAAAAAAAAAAAAAAAAAAAAAAA'])
        ->assertNotFound()
        ->assertExactJson(['error' => ['code' => 'delete_code_invalid', 'message' => 'The delete code is invalid.', 'details' => []]]);

    expect(FaceTemplate::query()->count())->toBe(1);
});

it('validates the code', function () {
    $this->postJson('/api/face/delete/code', [])->assertStatus(422)->assertJsonPath('error.code', 'validation_failed');
});

<?php

declare(strict_types=1);

use App\Models\FaceTemplate;

const MY_FACE = [1, 0, 0, 0, 0, 0, 0, 0];

function account(array $attributes = []): FaceTemplate
{
    return FaceTemplate::factory()->withEmbedding(MY_FACE)->create($attributes + ['name' => 'Sari']);
}

it('records attendance from a photo of the signed-in face, once per day at any time', function () {
    $this->travelTo('2026-11-20 23:50:00');
    $account = account();
    $this->actingAs($account);

    $this->postJson('/api/face/attendance', ['embedding' => [1, 0.02, 0, 0, 0, 0, 0, 0]])
        ->assertOk()
        ->assertExactJson(['data' => ['redirect' => route('face.dashboard')]])
        ->assertSessionHas('status', __('face.dashboard.attended'));
    $this->postJson('/api/face/attendance', ['embedding' => MY_FACE])->assertOk()
        ->assertSessionHas('status', __('face.dashboard.already_attended'));
    $this->travelTo('2026-11-21 00:05:00');
    $this->postJson('/api/face/attendance', ['embedding' => MY_FACE])->assertOk();

    expect($account->attendances()->pluck('date')->map->toDateString()->all())
        ->toEqualCanonicalizing(['2026-11-20', '2026-11-21']);
    $this->get('/dashboard')->assertSee('Sudah absen pukul 00.05');
});

it('needs no liveness session', function () {
    $this->actingAs(account());

    $this->postJson('/api/face/attendance', ['embedding' => MY_FACE])->assertOk();
});

it('rejects a face that is not the signed-in account, even when it is registered', function () {
    $other = FaceTemplate::factory()->withEmbedding([0, 1, 0, 0, 0, 0, 0, 0])->create(['name' => 'Lain']);
    $account = account();
    $this->actingAs($account);

    $res = $this->postJson('/api/face/attendance', ['embedding' => [0, 1, 0, 0, 0, 0, 0, 0]])
        ->assertNotFound()
        ->assertJsonPath('error.code', 'face_not_found');

    expect($account->attendances()->count())->toBe(0)
        ->and($other->attendances()->count())->toBe(0)
        ->and($res->getContent())->not->toContain('similar');
});

it('rejects a template from another model version', function () {
    $this->actingAs(account(['model_version' => 'old-model']));

    $this->postJson('/api/face/attendance', ['embedding' => MY_FACE])->assertNotFound();
});

it('asks for the name first', function () {
    $this->actingAs(account(['name' => null]));

    $this->postJson('/api/face/attendance', ['embedding' => MY_FACE])
        ->assertStatus(422)
        ->assertJsonPath('error.details.fields', ['name']);
});

it('validates the embedding', function () {
    $this->actingAs(account());

    $this->postJson('/api/face/attendance', ['embedding' => [1, 2]])
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'validation_failed');
});

it('answers session_invalid when signed out, so the page reloads to the login', function () {
    $this->postJson('/api/face/attendance', ['embedding' => MY_FACE])
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'session_invalid');
});

it('rate limits attendance attempts', function () {
    $this->actingAs(account());
    foreach (range(1, 10) as $_) {
        $this->postJson('/api/face/attendance', ['embedding' => [0, 1, 0, 0, 0, 0, 0, 0]]);
    }

    $this->postJson('/api/face/attendance', ['embedding' => MY_FACE])->assertStatus(429)->assertJsonPath('error.code', 'rate_limited');
});

it('shows the camera only while today is still open', function () {
    $account = account();
    $this->actingAs($account)->get('/dashboard')->assertSee('data-start', false)->assertSee('data-page="dashboard"', false);

    $account->attendances()->create(['date' => today()]);
    $this->get('/dashboard')->assertDontSee('data-start', false);
});

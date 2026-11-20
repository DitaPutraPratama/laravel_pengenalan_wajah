<?php

declare(strict_types=1);

use App\Models\Attendance;
use App\Models\FaceTemplate;
use Illuminate\Support\Facades\DB;

it('keeps guests out of the dashboard and accounts out of the scan pages', function () {
    $this->get('/dashboard')->assertRedirect('/');

    $this->actingAs(FaceTemplate::factory()->create());
    $this->get('/')->assertRedirect('/dashboard');
    $this->get('/enroll')->assertRedirect('/dashboard');
});

it('asks for a name before attendance and stores it encrypted', function () {
    $account = FaceTemplate::factory()->create();
    $this->actingAs($account);

    $this->get('/dashboard')->assertOk()->assertSee(__('face.dashboard.name_first'))->assertDontSee(__('face.dashboard.attend'));

    $this->put('/account/name', ['name' => '  Sari Dewi '])->assertRedirect();
    expect($account->fresh()->name)->toBe('Sari Dewi')
        ->and(DB::table('face_templates')->value('name'))->not->toContain('Sari');

    $this->get('/dashboard')->assertSee('Halo, Sari Dewi')->assertSee(__('face.dashboard.attend'));
});

it('validates the name', function (array $body) {
    $this->actingAs(FaceTemplate::factory()->create(['name' => 'Lama']));

    $this->from('/dashboard')->put('/account/name', $body)->assertRedirect('/dashboard')->assertSessionHasErrors('name');
})->with([
    'missing' => [[]],
    'too long' => [['name' => str_repeat('a', 101)]],
]);

it('clears only the attendance of the signed-in account', function () {
    $other = FaceTemplate::factory()->create(['name' => 'Lain']);
    $other->attendances()->create(['date' => '2026-11-19']);
    $account = FaceTemplate::factory()->create(['name' => 'Sari']);
    $account->attendances()->create(['date' => '2026-11-19']);

    $this->actingAs($account)->delete('/attendance')->assertRedirect();

    expect($account->attendances()->count())->toBe(0)
        ->and($other->attendances()->count())->toBe(1)
        ->and($account->fresh())->not->toBeNull();
});

it('deletes the account with every attendance and signs out', function () {
    $other = FaceTemplate::factory()->create();
    $account = FaceTemplate::factory()->create(['name' => 'Sari']);
    $account->attendances()->create(['date' => '2026-11-19']);

    $this->actingAs($account)->delete('/account')->assertRedirect('/');

    $this->assertGuest();
    expect(FaceTemplate::query()->pluck('id')->all())->toBe([$other->id])
        ->and(Attendance::query()->count())->toBe(0);
});

it('signs out', function () {
    $this->actingAs(FaceTemplate::factory()->create())->post('/logout')->assertRedirect('/');
    $this->assertGuest();
});

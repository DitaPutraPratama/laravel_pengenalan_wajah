<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

it('rate limits each endpoint with a standard 429', function (string $uri, array $body, int $limit) {
    foreach (range(1, $limit) as $_) {
        $this->postJson($uri, $body);
    }

    $this->postJson($uri, $body)
        ->assertStatus(429)
        ->assertHeader('Retry-After')
        ->assertJsonPath('error.code', 'rate_limited')
        ->assertJsonPath('error.details.retry_after', fn ($v) => is_int($v) && $v > 0);
})->with([
    'sessions' => ['/api/face/sessions', ['purpose' => 'enroll'], 20],
    'enroll' => ['/api/face/enroll', [], 5],
    'identify' => ['/api/face/identify', [], 10],
    'delete' => ['/api/face/delete/code', ['delete_code' => 'X'], 5],
]);

it('answers server_error and names the missing variable when config is empty', function () {
    config(['face.model_url' => '']);
    Log::spy();

    $this->postJson('/api/face/sessions', ['purpose' => 'enroll'])
        ->assertStatus(500)
        ->assertExactJson(['error' => ['code' => 'server_error', 'message' => 'Internal server error.', 'details' => []]]);

    Log::shouldHaveReceived('error')->withArgs(fn ($msg, $ctx) => $ctx === ['variables' => ['FACE_MODEL_URL']]);
});

it('turns unknown errors into server_error without details', function () {
    Route::post('/api/face/boom', fn () => throw new RuntimeException('secret internals'));

    $res = $this->postJson('/api/face/boom')->assertStatus(500)->assertJsonPath('error.code', 'server_error');

    expect($res->getContent())->not->toContain('secret internals');
});

it('sends no-store on the API and camera/CSP headers on pages', function () {
    $this->postJson('/api/face/sessions', ['purpose' => 'enroll'])->assertHeader('Cache-Control', 'no-store, private');

    $page = $this->get('/enroll')->assertOk()->assertHeader('Permissions-Policy', 'camera=(self), microphone=(), geolocation=()');
    expect($page->headers->get('Content-Security-Policy'))
        ->toContain("connect-src 'self' https://models.example.test")
        ->toContain("'wasm-unsafe-eval'");
});

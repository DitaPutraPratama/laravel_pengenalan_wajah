<?php

declare(strict_types=1);

it('renders every page in Indonesian with the shared layout', function (string $uri) {
    $this->get($uri)->assertOk()
        ->assertSee('lang="id"', false)
        ->assertSee(__('face.nav.enroll'))
        ->assertSee(__('face.footer.privacy'));
})->with(['/', '/enroll', '/check', '/delete']);

it('never sends the match threshold to the browser', function (string $uri) {
    config(['face.match_threshold' => 0.8642]);

    $this->get($uri)->assertDontSee('0.8642')->assertDontSee('threshold');
})->with(['/', '/enroll', '/check', '/delete']);

it('gives the camera pages their config through data attributes', function () {
    $this->get('/enroll')
        ->assertSee('data-model-url="https://models.example.test/human/"', false)
        ->assertSee('data-embedding-dim="8"', false);
});

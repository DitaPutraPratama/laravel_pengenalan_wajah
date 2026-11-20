<?php

declare(strict_types=1);

use App\Services\Face\DeleteCodeService;

it('makes 26 base32 characters (130 bits)', function () {
    expect(app(DeleteCodeService::class)->generate())->toMatch('/^[A-Z2-7]{26}$/');
});

it('does not repeat codes', function () {
    $service = app(DeleteCodeService::class);
    $codes = array_map(fn () => $service->generate(), range(1, 200));

    expect(array_unique($codes))->toHaveCount(200);
});

it('hashes with SHA-256 and ignores case, spaces and dashes', function () {
    $service = app(DeleteCodeService::class);

    expect($service->hash('ABCD EFGH-2345'))->toBe(hash('sha256', 'ABCDEFGH2345'))
        ->and($service->hash('abcdefgh2345'))->toBe($service->hash('ABCDEFGH2345'));
});

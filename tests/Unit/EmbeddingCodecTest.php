<?php

declare(strict_types=1);

use App\Exceptions\Face\FaceException;
use App\Services\Face\EmbeddingCodec;

it('normalizes to unit length', function () {
    $v = app(EmbeddingCodec::class)->normalize([3, 4, 0, 0, 0, 0, 0, 0]);

    expect($v[0])->toEqualWithDelta(0.6, 1e-9)
        ->and($v[1])->toEqualWithDelta(0.8, 1e-9);
});

it('round trips through encryption', function () {
    $codec = app(EmbeddingCodec::class);
    $v = $codec->normalize([1, -2, 3, -4, 5, -6, 7, -8]);

    expect($codec->decrypt($codec->encrypt($v)))->each->toBeFloat()
        ->and(array_map(fn ($a, $b) => abs($a - $b), $codec->decrypt($codec->encrypt($v)), $v))->each->toBeLessThan(1e-6);
});

it('does not leave plaintext in the ciphertext', function () {
    $codec = app(EmbeddingCodec::class);
    $v = $codec->normalize([1, 2, 3, 4, 5, 6, 7, 8]);
    $cipher = $codec->encrypt($v);

    expect($cipher)->not->toContain(pack('g*', ...$v))
        ->and(base64_decode($cipher))->not->toContain(pack('g*', ...$v))
        ->and($cipher)->not->toContain((string) $v[0]);
});

it('rejects the wrong dimension and the zero vector', function (array $vector) {
    app(EmbeddingCodec::class)->normalize($vector);
})->throws(FaceException::class)->with([
    'too short' => [[1, 2, 3]],
    'zero' => [[0, 0, 0, 0, 0, 0, 0, 0]],
]);

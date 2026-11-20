<?php

declare(strict_types=1);

use App\Models\FaceTemplate;
use App\Services\Face\FaceMatcher;

beforeEach(fn () => config(['face.match_threshold' => 0.9]));

it('finds a matching template', function () {
    $t = FaceTemplate::factory()->withEmbedding([1, 0, 0, 0, 0, 0, 0, 0])->create();
    FaceTemplate::factory()->withEmbedding([0, 1, 0, 0, 0, 0, 0, 0])->create();

    expect(app(FaceMatcher::class)->bestMatchId([0.99, 0.141, 0, 0, 0, 0, 0, 0]))->toBe($t->id);
});

it('returns null below the threshold', function () {
    FaceTemplate::factory()->withEmbedding([1, 0, 0, 0, 0, 0, 0, 0])->create();

    // cos = 0.8
    expect(app(FaceMatcher::class)->bestMatchId([0.8, 0.6, 0, 0, 0, 0, 0, 0]))->toBeNull();
});

it('matches exactly at the threshold', function () {
    config(['face.match_threshold' => 0.8]);
    $t = FaceTemplate::factory()->withEmbedding([1, 0, 0, 0, 0, 0, 0, 0])->create();

    expect(app(FaceMatcher::class)->bestMatchId([0.8, 0.6, 0, 0, 0, 0, 0, 0]))->toBe($t->id);
});

it('ignores templates from another model version', function () {
    FaceTemplate::factory()->withEmbedding([1, 0, 0, 0, 0, 0, 0, 0])->create(['model_version' => 'old']);

    expect(app(FaceMatcher::class)->bestMatchId([1, 0, 0, 0, 0, 0, 0, 0]))->toBeNull();
});

it('picks the most similar of several matches', function () {
    FaceTemplate::factory()->withEmbedding([0.95, 0.312, 0, 0, 0, 0, 0, 0])->create();
    $closest = FaceTemplate::factory()->withEmbedding([1, 0.05, 0, 0, 0, 0, 0, 0])->create();

    expect(app(FaceMatcher::class)->bestMatchId([1, 0, 0, 0, 0, 0, 0, 0]))->toBe($closest->id);
});

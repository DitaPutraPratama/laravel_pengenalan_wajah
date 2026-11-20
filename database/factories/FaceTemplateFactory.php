<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\FaceTemplate;
use App\Services\Face\EmbeddingCodec;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Synthetic random vectors only. Never put real face data in fixtures.
 *
 * @extends Factory<FaceTemplate>
 */
class FaceTemplateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'embedding' => $this->encrypt(self::randomVector((int) config('face.embedding_dim'))),
            'embedding_dim' => (int) config('face.embedding_dim'),
            'model_version' => config('face.model_version'),
            'consent_version' => config('face.consent_version'),
        ];
    }

    /** @param list<float> $vector */
    public function withEmbedding(array $vector): static
    {
        return $this->state(fn () => ['embedding' => $this->encrypt($vector)]);
    }

    /** @return list<float> */
    public static function randomVector(int $dim): array
    {
        return array_map(fn () => mt_rand() / mt_getrandmax() * 2 - 1, range(1, $dim));
    }

    /** @param list<float> $vector */
    private function encrypt(array $vector): string
    {
        $codec = app(EmbeddingCodec::class);

        return $codec->encrypt($codec->normalize($vector));
    }
}

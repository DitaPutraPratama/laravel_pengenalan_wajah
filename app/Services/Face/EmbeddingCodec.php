<?php

declare(strict_types=1);

namespace App\Services\Face;

use App\Enums\FaceErrorCode;
use App\Exceptions\Face\FaceException;
use Illuminate\Support\Facades\Crypt;
use RuntimeException;

/** L2 normalization, float32 packing and encryption of embeddings (F-10). */
final class EmbeddingCodec
{
    public function dimensions(): int
    {
        return (int) config('face.embedding_dim');
    }

    /**
     * @param  array<int, float|int|string>  $vector
     * @return list<float>
     */
    public function normalize(array $vector): array
    {
        $vector = array_map(floatval(...), array_values($vector));
        $norm = sqrt(array_sum(array_map(fn (float $v): float => $v * $v, $vector)));

        if (count($vector) !== $this->dimensions() || ! is_finite($norm) || $norm === 0.0) {
            throw new FaceException(FaceErrorCode::ValidationFailed, ['fields' => ['embedding']]);
        }

        return array_map(fn (float $v): float => $v / $norm, $vector);
    }

    /** @param list<float> $normalized */
    public function encrypt(array $normalized): string
    {
        return Crypt::encryptString(pack('g*', ...$normalized));
    }

    /** @return list<float> */
    public function decrypt(string $ciphertext): array
    {
        $vector = array_values(unpack('g*', Crypt::decryptString($ciphertext)) ?: []);

        if (count($vector) !== $this->dimensions()) {
            throw new RuntimeException('Stored embedding has an unexpected dimension.');
        }

        return $vector;
    }
}

<?php

declare(strict_types=1);

namespace App\Services\Face;

use App\Models\FaceTemplate;
use Illuminate\Support\Facades\Log;

/** 1:N cosine matching. Similarity scores never leave this class (ADR-011). */
final class FaceMatcher
{
    public function __construct(private readonly EmbeddingCodec $codec) {}

    /**
     * Id of the most similar template at or above the threshold, or null.
     *
     * @param  list<float>  $normalized  L2-normalized embedding
     */
    public function bestMatchId(array $normalized): ?int
    {
        // ponytail: full scan in PHP, fine up to FACE_MAX_TEMPLATES (ADR-005); pgvector if it must scale.
        $bestId = null;
        $best = -INF;
        $templates = FaceTemplate::query()
            ->select(['id', 'embedding'])
            ->where('model_version', config('face.model_version'))
            ->where('embedding_dim', $this->codec->dimensions())
            ->lazyById(200);

        foreach ($templates as $template) {
            $score = $this->dot($normalized, $this->codec->decrypt($template->embedding));
            if ($score > $best) {
                $best = $score;
                $bestId = $template->id;
            }
        }

        $threshold = (float) config('face.match_threshold');
        if (config('face.log_similarity')) {
            Log::debug('face.match', ['best_similarity' => is_finite($best) ? round($best, 4) : null, 'threshold' => $threshold]);
        }

        return $best >= $threshold ? $bestId : null;
    }

    /**
     * @param  list<float>  $a
     * @param  list<float>  $b
     */
    private function dot(array $a, array $b): float
    {
        $sum = 0.0;
        foreach ($a as $i => $value) {
            $sum += $value * $b[$i];
        }

        return $sum;
    }
}

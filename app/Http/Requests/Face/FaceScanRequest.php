<?php

declare(strict_types=1);

namespace App\Http\Requests\Face;

use Illuminate\Foundation\Http\FormRequest;

/** Embedding plus liveness telemetry (docs/API.md). Used by login; enroll adds consent. */
class FaceScanRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        $dim = (int) config('face.embedding_dim');

        return [
            'embedding' => ['required', 'array', 'list', "size:{$dim}"],
            'embedding.*' => ['required', 'numeric'],
            'liveness' => ['required', 'array'],
            'liveness.session_token' => ['required', 'string', 'max:128'],
            'liveness.challenges' => ['required', 'array', 'list', 'max:10'],
            'liveness.challenges.*' => ['required', 'array'],
            'liveness.challenges.*.type' => ['required', 'string', 'max:20'],
            'liveness.challenges.*.duration_ms' => ['required', 'integer'],
            'liveness.challenges.*.frames' => ['required', 'integer'],
            'liveness.challenges.*.peak_yaw' => ['nullable', 'numeric'],
            'liveness.challenges.*.end_yaw' => ['nullable', 'numeric'],
            'liveness.challenges.*.min_ear' => ['nullable', 'numeric'],
            'liveness.challenges.*.max_ear' => ['nullable', 'numeric'],
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Requests\Face;

use Illuminate\Foundation\Http\FormRequest;

/** Embedding only: attendance has no liveness challenges (ADR-021). */
class AttendRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return FaceScanRequest::embeddingRules();
    }
}

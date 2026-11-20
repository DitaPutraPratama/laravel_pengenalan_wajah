<?php

declare(strict_types=1);

namespace App\Http\Requests\Face;

use App\Enums\LivenessPurpose;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SessionRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['purpose' => ['required', Rule::enum(LivenessPurpose::class)]];
    }
}

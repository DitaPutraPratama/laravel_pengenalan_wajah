<?php

declare(strict_types=1);

namespace App\Http\Requests\Face;

use Illuminate\Foundation\Http\FormRequest;

class NameRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:100']];
    }

    /** There is no lang/id/validation.php, so the two possible messages live in lang/id/face.php. */
    public function messages(): array
    {
        return [
            'name.required' => __('face.dashboard.name_required'),
            'name.string' => __('face.dashboard.name_required'),
            'name.max' => __('face.dashboard.name_too_long'),
        ];
    }
}

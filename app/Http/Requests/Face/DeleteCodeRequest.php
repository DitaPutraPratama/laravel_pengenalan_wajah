<?php

declare(strict_types=1);

namespace App\Http\Requests\Face;

use Illuminate\Foundation\Http\FormRequest;

class DeleteCodeRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['delete_code' => ['required', 'string', 'max:64']];
    }
}

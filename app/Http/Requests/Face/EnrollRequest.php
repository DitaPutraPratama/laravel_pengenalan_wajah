<?php

declare(strict_types=1);

namespace App\Http\Requests\Face;

use Illuminate\Validation\Rule;

/** `consent` itself is checked by the service so a missing value gives consent_required. */
class EnrollRequest extends FaceScanRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return parent::rules() + [
            'consent_version' => ['required', 'string', Rule::in([config('face.consent_version')])],
        ];
    }
}

<?php

declare(strict_types=1);
use App\Models\FaceTemplate;

// Only the user model differs from Laravel's defaults: the face template is the account (ADR-020).
return [
    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => FaceTemplate::class,
        ],
    ],
];

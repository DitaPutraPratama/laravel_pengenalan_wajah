<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LivenessSession extends Model
{
    protected $table = 'face_liveness_sessions';

    protected $fillable = ['token_hash', 'purpose', 'challenges', 'expires_at', 'used_at'];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return [
            'challenges' => 'array',
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }
}

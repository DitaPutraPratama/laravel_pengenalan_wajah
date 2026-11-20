<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\FaceTemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

/** A registered face is also the account (ADR-020). There is no password: login is a face scan. */
class FaceTemplate extends Authenticatable
{
    /** @use HasFactory<FaceTemplateFactory> */
    use HasFactory;

    protected $fillable = ['embedding', 'embedding_dim', 'model_version', 'consent_version', 'external_ref', 'name'];

    protected $hidden = ['embedding'];

    protected function casts(): array
    {
        return ['name' => 'encrypted'];
    }

    /** @return HasMany<Attendance, $this> */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }
}

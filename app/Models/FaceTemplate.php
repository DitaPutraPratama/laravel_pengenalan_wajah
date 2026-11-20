<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\FaceTemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FaceTemplate extends Model
{
    /** @use HasFactory<FaceTemplateFactory> */
    use HasFactory;

    protected $fillable = ['embedding', 'embedding_dim', 'model_version', 'delete_code_hash', 'consent_version', 'external_ref'];

    protected $hidden = ['embedding', 'delete_code_hash'];
}

<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One row per account per day (F-20). The check-in time is created_at. */
class Attendance extends Model
{
    protected $table = 'face_attendances';

    protected $fillable = ['date'];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }
}

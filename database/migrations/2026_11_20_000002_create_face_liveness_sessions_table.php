<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('face_liveness_sessions', function (Blueprint $table) {
            $table->id();
            $table->char('token_hash', 64)->unique();
            $table->string('purpose', 20);
            $table->json('challenges');
            $table->timestamp('expires_at')->index();
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('face_liveness_sessions');
    }
};

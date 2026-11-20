<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('face_templates', function (Blueprint $table) {
            $table->id();
            $table->text('embedding');
            $table->unsignedSmallInteger('embedding_dim');
            $table->string('model_version', 40)->index();
            $table->char('delete_code_hash', 64)->unique();
            $table->string('consent_version', 20);
            $table->string('external_ref', 191)->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('face_templates');
    }
};

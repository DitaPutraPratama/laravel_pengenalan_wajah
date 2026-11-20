<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** The face becomes the account (ADR-020): a name, daily attendance, no delete code. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('face_templates', function (Blueprint $table) {
            $table->dropUnique(['delete_code_hash']);
        });
        Schema::table('face_templates', function (Blueprint $table) {
            $table->dropColumn('delete_code_hash');
            $table->text('name')->nullable(); // encrypted by the model
        });

        Schema::create('face_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('face_template_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->timestamps();
            $table->unique(['face_template_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('face_attendances');

        Schema::table('face_templates', function (Blueprint $table) {
            $table->dropColumn('name');
            $table->char('delete_code_hash', 64)->nullable()->unique();
        });
    }
};

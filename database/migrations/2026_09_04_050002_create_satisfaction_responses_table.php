<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Satu kiriman survei (anonim). Identitas sengaja tidak disimpan;
        // hanya saran opsional yang ditulis pengaju.
        Schema::create('satisfaction_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('satisfaction_survey_id')->constrained()->cascadeOnDelete();
            $table->text('suggestion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('satisfaction_responses');
    }
};

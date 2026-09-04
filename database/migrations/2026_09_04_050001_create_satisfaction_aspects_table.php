<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sub kepuasan layanan: tiap baris = satu aspek yang dinilai pengaju
        // dengan satu slider emoji sendiri (mis. "Keramahan Petugas").
        Schema::create('satisfaction_aspects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('satisfaction_survey_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->string('help_text')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('satisfaction_aspects');
    }
};

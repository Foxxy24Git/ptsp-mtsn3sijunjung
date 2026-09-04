<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Nilai per aspek disimpan satu baris per aspek (bukan JSON) supaya
        // rekap rata-rata cukup satu query AVG()+GROUP BY, tanpa memuat
        // seluruh jawaban ke memori seperti pada form_submissions.
        Schema::create('satisfaction_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('satisfaction_response_id')->constrained()->cascadeOnDelete();
            $table->foreignId('satisfaction_aspect_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('score');
            $table->timestamps();

            $table->unique(
                ['satisfaction_response_id', 'satisfaction_aspect_id'],
                'satisfaction_answers_response_aspect_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('satisfaction_answers');
    }
};

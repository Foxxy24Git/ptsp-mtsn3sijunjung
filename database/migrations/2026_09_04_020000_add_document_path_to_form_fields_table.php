<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_fields', function (Blueprint $table) {
            // Dipakai khusus tipe 'document': berkas yang diunggah ADMIN (bukan
            // pemohon) untuk diunduh pemohon, mis. blanko formulir keluar/masuk
            // siswa. Path pada disk public, sama seperti logo/hero/slide.
            $table->string('document_path')->nullable()->after('options');
        });
    }

    public function down(): void
    {
        Schema::table('form_fields', function (Blueprint $table) {
            $table->dropColumn('document_path');
        });
    }
};

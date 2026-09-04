<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            // Sebagian layanan (mis. pengumuman/pendaftaran internal) tidak perlu
            // meminta Nama/WhatsApp/Email pemohon di formulir Ajukan.
            $table->boolean('requires_applicant_identity')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->dropColumn('requires_applicant_identity');
        });
    }
};

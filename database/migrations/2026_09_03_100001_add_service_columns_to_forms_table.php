<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            // Pembeda layanan PTSP dari form biasa (mis. form kontak).
            $table->boolean('is_service')->default(true);
            // Bagian kiri badge kartu: "PTSP / Tata Usaha (TU)".
            $table->string('organizer', 100)->default('PTSP');
            $table->foreignId('work_unit_id')->nullable()->constrained('work_units')->nullOnDelete();
            // Teks bebas karena satuannya bercampur: menit, jam, hari, dan rentang.
            $table->string('duration_text', 50)->nullable();
            $table->string('fee_text', 50)->default('Gratis');
            $table->unsignedInteger('sort_order')->default(0);
            $table->longText('requirements')->nullable();
            $table->longText('legal_basis')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->dropConstrainedForeignId('work_unit_id');
            $table->dropColumn([
                'is_service', 'organizer', 'duration_text',
                'fee_text', 'sort_order', 'requirements', 'legal_basis',
            ]);
        });
    }
};

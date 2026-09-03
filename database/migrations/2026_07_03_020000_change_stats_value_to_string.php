<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ubah `value` menjadi string agar bisa diisi teks (mis. "A", "500+"),
     * tidak lagi hanya angka.
     */
    public function up(): void
    {
        Schema::table('stats', function (Blueprint $table) {
            $table->string('value')->default('')->change();
        });
    }

    public function down(): void
    {
        Schema::table('stats', function (Blueprint $table) {
            $table->unsignedBigInteger('value')->default(0)->change();
        });
    }
};

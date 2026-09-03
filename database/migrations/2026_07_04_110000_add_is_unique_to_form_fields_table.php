<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_fields', function (Blueprint $table) {
            // Bila true, satu nilai hanya boleh dipakai sekali di form ini
            // (mis. Email/NIS). Default false: boleh duplikat (mis. Umur/Kelas).
            $table->boolean('is_unique')->default(false)->after('required');
        });
    }

    public function down(): void
    {
        Schema::table('form_fields', function (Blueprint $table) {
            $table->dropColumn('is_unique');
        });
    }
};

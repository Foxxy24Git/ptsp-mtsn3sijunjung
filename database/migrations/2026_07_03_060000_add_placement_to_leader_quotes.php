<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leader_quotes', function (Blueprint $table) {
            // 'pimpinan' = sambutan besar (satu orang). 'pendamping' = barisan foto di bawah pimpinan (waka).
            $table->string('placement')->default('pimpinan')->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('leader_quotes', function (Blueprint $table) {
            $table->dropColumn('placement');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leader_quotes', function (Blueprint $table) {
            $table->string('title')->nullable()->after('name');
            $table->string('background_style')->default('arch')->after('quote');
        });
    }

    public function down(): void
    {
        Schema::table('leader_quotes', function (Blueprint $table) {
            $table->dropColumn(['title', 'background_style']);
        });
    }
};

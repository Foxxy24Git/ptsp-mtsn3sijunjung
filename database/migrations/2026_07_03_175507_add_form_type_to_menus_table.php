<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // SQLite doesn't support modifying enums directly, so we need to recreate the table
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('ALTER TABLE menus RENAME TO menus_old');

            Schema::create('menus', function (Blueprint $table) {
                $table->id();
                $table->string('label');
                $table->unsignedInteger('sort_order')->default(0);
                $table->enum('type', ['page', 'post', 'url', 'form'])->default('url');
                $table->string('target')->nullable();
                $table->foreignId('parent_id')
                    ->nullable()
                    ->constrained('menus')
                    ->cascadeOnDelete();
                $table->timestamps();
            });

            DB::statement('INSERT INTO menus SELECT * FROM menus_old');
            DB::statement('DROP TABLE menus_old');
        } else {
            // For other databases (MySQL, PostgreSQL), use a different approach
            Schema::table('menus', function (Blueprint $table) {
                $table->enum('type', ['page', 'post', 'url', 'form'])->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('ALTER TABLE menus RENAME TO menus_old');

            Schema::create('menus', function (Blueprint $table) {
                $table->id();
                $table->string('label');
                $table->unsignedInteger('sort_order')->default(0);
                $table->enum('type', ['page', 'post', 'url'])->default('url');
                $table->string('target')->nullable();
                $table->foreignId('parent_id')
                    ->nullable()
                    ->constrained('menus')
                    ->cascadeOnDelete();
                $table->timestamps();
            });

            DB::statement('INSERT INTO menus SELECT * FROM menus_old');
            DB::statement('DROP TABLE menus_old');
        } else {
            Schema::table('menus', function (Blueprint $table) {
                $table->enum('type', ['page', 'post', 'url'])->change();
            });
        }
    }
};

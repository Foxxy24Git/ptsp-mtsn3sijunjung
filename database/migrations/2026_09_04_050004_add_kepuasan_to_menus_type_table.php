<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah tipe menu 'kepuasan' supaya slug survei Kepuasan Layanan bisa
     * ditempel ke menu navigasi. Mengikuti pola migrasi penambahan tipe
     * 'form' sebelumnya: SQLite tidak bisa mengubah enum in-place, jadi
     * tabelnya dibuat ulang.
     */
    public function up(): void
    {
        $this->setTypeEnum(['page', 'post', 'url', 'form', 'kepuasan']);
    }

    public function down(): void
    {
        // Menu bertipe 'kepuasan' tidak lagi valid setelah rollback; ubah
        // dulu jadi 'url' agar barisnya tidak melanggar enum yang menyempit.
        DB::table('menus')->where('type', 'kepuasan')->update(['type' => 'url']);

        $this->setTypeEnum(['page', 'post', 'url', 'form']);
    }

    /**
     * @param  array<int, string>  $types
     */
    private function setTypeEnum(array $types): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('ALTER TABLE menus RENAME TO menus_old');

            Schema::create('menus', function (Blueprint $table) use ($types) {
                $table->id();
                $table->string('label');
                $table->unsignedInteger('sort_order')->default(0);
                $table->enum('type', $types)->default('url');
                $table->string('target')->nullable();
                $table->foreignId('parent_id')
                    ->nullable()
                    ->constrained('menus')
                    ->cascadeOnDelete();
                $table->timestamps();
            });

            DB::statement('INSERT INTO menus SELECT * FROM menus_old');
            DB::statement('DROP TABLE menus_old');

            return;
        }

        Schema::table('menus', function (Blueprint $table) use ($types) {
            $table->enum('type', $types)->change();
        });
    }
};

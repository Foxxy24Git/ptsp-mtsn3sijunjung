<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Background section Hero di beranda (hero fallback, yang tampil saat
        // belum ada slide aktif). Admin bisa pakai gambar sendiri atau gradasi
        // 2 warna. Default null semua: hero.blade.php otomatis memakai
        // primary_color sebagai gradasi, jadi tampilan tetap senada dengan tema
        // situs sampai admin mengaturnya sendiri.
        if (! $this->migrator->exists('general.hero_bg_image')) {
            $this->migrator->add('general.hero_bg_image', null);
        }
        if (! $this->migrator->exists('general.hero_bg_from')) {
            $this->migrator->add('general.hero_bg_from', null);
        }
        if (! $this->migrator->exists('general.hero_bg_to')) {
            $this->migrator->add('general.hero_bg_to', null);
        }
    }

    public function down(): void
    {
        $this->migrator->deleteIfExists('general.hero_bg_image');
        $this->migrator->deleteIfExists('general.hero_bg_from');
        $this->migrator->deleteIfExists('general.hero_bg_to');
    }
};

<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Background section Zona Integritas: admin bisa pakai gambar sendiri,
        // atau gradasi 2 warna. Default sama seperti tampilan saat ini (primary -> gelap)
        // supaya tidak berubah sampai admin mengubahnya.
        // Guard exists(): properti ini sempat kepakai di form sebelum migration ini
        // sempat jalan, jadi zi_bg_image sudah ada duluan di beberapa environment.
        if (! $this->migrator->exists('general.zi_bg_image')) {
            $this->migrator->add('general.zi_bg_image', null);
        }
        if (! $this->migrator->exists('general.zi_bg_from')) {
            $this->migrator->add('general.zi_bg_from', '#1078f2');
        }
        if (! $this->migrator->exists('general.zi_bg_to')) {
            $this->migrator->add('general.zi_bg_to', '#0b1220');
        }
    }

    public function down(): void
    {
        $this->migrator->deleteIfExists('general.zi_bg_image');
        $this->migrator->deleteIfExists('general.zi_bg_from');
        $this->migrator->deleteIfExists('general.zi_bg_to');
    }
};

<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Warna latar header (atas) sekaligus footer (bawah) situs publik.
        // Default putih agar tampilan tidak berubah sampai admin mengaturnya.
        $this->migrator->add('general.header_color', '#ffffff');
    }
};

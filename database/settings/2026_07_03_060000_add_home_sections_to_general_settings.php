<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Urutan + visibility section beranda. Default = urutan lama, semua
        // tampil, agar tampilan publik tidak berubah sampai admin mengubahnya.
        $this->migrator->add('general.home_sections', [
            ['key' => 'hero', 'visible' => true],
            ['key' => 'stats', 'visible' => true],
            ['key' => 'leader', 'visible' => true],
            ['key' => 'pendamping', 'visible' => true],
            ['key' => 'posts', 'visible' => true],
        ]);
    }

    public function down(): void
    {
        $this->migrator->deleteIfExists('general.home_sections');
    }
};

<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Section "Zona Integritas" di beranda: logo + tulisan bisa diedit admin,
        // tampil/urutannya diatur lewat general.home_sections (key: zona_integritas).
        $this->migrator->add('general.zi_logo', null);
        $this->migrator->add('general.zi_eyebrow', 'Layanan Zona Integritas');
        $this->migrator->add('general.zi_heading', 'Transparan, Akuntabel, dan Melayani');
        $this->migrator->add(
            'general.zi_description',
            'Zona Integritas adalah komitmen sekolah menghadirkan pelayanan publik yang bersih, transparan, dan akuntabel bagi siswa, orang tua, dan masyarakat.'
        );
        $this->migrator->add('general.zi_points', [
            'Standar pelayanan dan alur permohonan informasi',
            'Kanal pengaduan serta tindak lanjut aspirasi masyarakat',
            'Survei kepuasan layanan dan laporan berkala',
            'Komitmen anti-gratifikasi dan anti-korupsi',
        ]);
    }

    public function down(): void
    {
        $this->migrator->deleteIfExists('general.zi_logo');
        $this->migrator->deleteIfExists('general.zi_eyebrow');
        $this->migrator->deleteIfExists('general.zi_heading');
        $this->migrator->deleteIfExists('general.zi_description');
        $this->migrator->deleteIfExists('general.zi_points');
    }
};

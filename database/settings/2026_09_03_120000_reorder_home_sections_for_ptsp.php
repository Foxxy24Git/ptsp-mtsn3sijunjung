<?php

use App\Settings\GeneralSettings;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    /**
     * Migrasi settings sebelumnya (2026_07_03_060000) menyimpan snapshot
     * literal home_sections berisi 5 kunci lama. Kunci baru yang belakangan
     * ditambahkan ke GeneralSettings::HOME_SECTIONS (galeri, zona_integritas,
     * layanan) hanya di-append di belakang urutan tersimpan itu — bukan sesuai
     * posisinya di konstanta — karena normalizeSections() mempertahankan
     * urutan tersimpan dan hanya menambah kunci yang belum ada.
     *
     * Untuk instance PTSP Online yang baru pertama kali di-deploy, section
     * "Layanan PTSP" adalah konten paling penting di beranda dan harus
     * langsung terlihat setelah hero, bukan tersembunyi di posisi acak hasil
     * riwayat migrasi CMS-web. Migrasi ini menata ulang urutan tersimpan agar
     * sesuai urutan di HOME_SECTIONS.
     */
    public function up(): void
    {
        $this->migrator->update('general.home_sections', fn (): array => array_map(
            fn (string $key): array => ['key' => $key, 'visible' => true],
            array_keys(GeneralSettings::HOME_SECTIONS),
        ));
    }

    public function down(): void
    {
        $this->migrator->update('general.home_sections', fn (): array => [
            ['key' => 'hero', 'visible' => true],
            ['key' => 'stats', 'visible' => true],
            ['key' => 'leader', 'visible' => true],
            ['key' => 'pendamping', 'visible' => true],
            ['key' => 'posts', 'visible' => true],
        ]);
    }
};

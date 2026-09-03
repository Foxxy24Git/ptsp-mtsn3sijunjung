<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    /**
     * Kotak pencarian kode resi di beranda sebelumnya selalu tampil di atas
     * (di luar sistem home_sections). Sekarang dijadikan section biasa
     * ('lacak') supaya admin bisa memindah urutannya atau menyembunyikannya,
     * sama seperti Hero dan Layanan.
     *
     * Disisipkan eksplisit di urutan tersimpan (bukan diserahkan ke
     * normalizeSections() untuk di-append di belakang) supaya posisinya
     * tetap sama seperti tampilan sebelumnya: paling atas, sebelum hero.
     */
    public function up(): void
    {
        $this->migrator->update('general.home_sections', function ($sections): array {
            // Nilai lama bisa terbaca sebagai array of stdClass (bukan array
            // asosiatif) tergantung bagaimana Spatie men-decode JSON yang
            // tersimpan -- json roundtrip menormalkannya jadi array biasa
            // supaya akses ['key'] di bawah selalu aman.
            $normalized = json_decode(json_encode($sections), true);

            $sudahAda = collect($normalized)->contains(fn (array $s): bool => ($s['key'] ?? null) === 'lacak');

            if ($sudahAda) {
                return $normalized;
            }

            return [['key' => 'lacak', 'visible' => true], ...$normalized];
        });
    }

    public function down(): void
    {
        $this->migrator->update('general.home_sections', function ($sections): array {
            $normalized = json_decode(json_encode($sections), true);

            return array_values(array_filter($normalized, fn (array $s): bool => ($s['key'] ?? null) !== 'lacak'));
        });
    }
};

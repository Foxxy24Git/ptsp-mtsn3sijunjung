<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public string $site_name;

    public ?string $logo;

    public ?string $address;

    public ?string $phone;

    public ?string $email;

    public string $primary_color;

    public string $header_color;

    public ?string $whatsapp;

    public ?string $instagram;

    public ?string $facebook;

    public ?string $youtube;

    public array $home_sections;

    public ?string $zi_logo;

    public ?string $zi_eyebrow;

    public ?string $zi_heading;

    public ?string $zi_description;

    public array $zi_points;

    public ?string $zi_bg_image;

    public string $zi_bg_from;

    public string $zi_bg_to;

    /**
     * Master section beranda: key => label. Urutan di sini = urutan default
     * dan menjadi satu-satunya sumber kebenaran daftar section yang valid.
     *
     * Hanya tiga section yang relevan untuk PTSP Online. Enam section
     * CMS-web lainnya (Statistik, Pimpinan, Pendamping/Waka, Zona
     * Integritas, Galeri, Berita) sengaja dikeluarkan dari daftar ini --
     * normalizeSections() otomatis membuang key yang sudah tidak dikenal
     * dari data lama, jadi pengaturan tersimpan sebelumnya tidak perlu
     * dibersihkan manual.
     */
    public const HOME_SECTIONS = [
        'lacak' => 'Lacak Permohonan',
        'hero' => 'Hero / Slider',
        'layanan' => 'Layanan PTSP',
    ];

    /**
     * Bersihkan data tersimpan: buang key tak dikenal & duplikat (pakai entri
     * pertama), pertahankan urutan tersimpan, lalu append key master yang belum
     * ada (visible=true) di belakang. Menjamin selalu 5 section lengkap.
     *
     * @param  array<int,array{key?:string,visible?:bool}>  $stored
     * @return array<int,array{key:string,visible:bool}>
     */
    public static function normalizeSections(array $stored): array
    {
        $known = array_keys(self::HOME_SECTIONS);
        $result = [];
        $seen = [];

        foreach ($stored as $section) {
            $key = $section['key'] ?? null;

            if ($key === null || ! in_array($key, $known, true) || isset($seen[$key])) {
                continue;
            }

            $result[] = ['key' => $key, 'visible' => (bool) ($section['visible'] ?? true)];
            $seen[$key] = true;
        }

        foreach ($known as $key) {
            if (! isset($seen[$key])) {
                $result[] = ['key' => $key, 'visible' => true];
            }
        }

        return $result;
    }

    /**
     * home_sections yang sudah dinormalisasi, siap dirender di beranda.
     *
     * @return array<int,array{key:string,visible:bool}>
     */
    public function orderedSections(): array
    {
        return self::normalizeSections($this->home_sections);
    }

    public static function group(): string
    {
        return 'general';
    }
}

<?php

namespace App\Support;

/**
 * Skala kepuasan 1–5 beserta label & warnanya. Dipakai bersama oleh halaman
 * publik (slider emoji), rekap admin, dan validasi controller supaya rentang
 * nilai hanya didefinisikan di satu tempat.
 */
final class SatisfactionScale
{
    public const MIN = 1;

    public const MAX = 5;

    /** Nilai awal slider: netral, supaya tidak menggiring jawaban pengaju. */
    public const DEFAULT = 3;

    /**
     * color = warna dasar wajah/track (grafis besar, kontras >= 3:1)
     * light = varian terang untuk gradasi wajah (bagian atas lingkaran)
     * text  = varian gelap untuk teks label (kontras >= 4.5:1 di latar putih)
     *
     * @var array<int, array{label: string, short: string, color: string, light: string, text: string}>
     */
    public const LEVELS = [
        1 => ['label' => 'Sangat Tidak Puas', 'short' => 'Sangat Buruk', 'color' => '#DC2626', 'light' => '#F87171', 'text' => '#B91C1C'],
        2 => ['label' => 'Tidak Puas', 'short' => 'Buruk', 'color' => '#EA580C', 'light' => '#FB923C', 'text' => '#C2410C'],
        3 => ['label' => 'Cukup', 'short' => 'Cukup', 'color' => '#EAB308', 'light' => '#FDE047', 'text' => '#A16207'],
        4 => ['label' => 'Puas', 'short' => 'Baik', 'color' => '#84CC16', 'light' => '#BEF264', 'text' => '#4D7C0F'],
        5 => ['label' => 'Sangat Puas', 'short' => 'Sangat Baik', 'color' => '#16A34A', 'light' => '#4ADE80', 'text' => '#15803D'],
    ];

    /** @return array<int, int> */
    public static function scores(): array
    {
        return array_keys(self::LEVELS);
    }

    public static function label(?int $score): string
    {
        return self::LEVELS[$score]['label'] ?? '—';
    }

    public static function short(?int $score): string
    {
        return self::LEVELS[$score]['short'] ?? '—';
    }

    public static function color(?int $score): string
    {
        return self::LEVELS[$score]['color'] ?? '#94A3B8';
    }

    public static function light(?int $score): string
    {
        return self::LEVELS[$score]['light'] ?? '#CBD5E1';
    }

    public static function textColor(?int $score): string
    {
        return self::LEVELS[$score]['text'] ?? '#475569';
    }

    public static function isValid(mixed $score): bool
    {
        return is_int($score) && array_key_exists($score, self::LEVELS);
    }

    /** Label mutu dari rata-rata: dibulatkan ke tingkat terdekat. */
    public static function grade(?float $average): string
    {
        if ($average === null) {
            return '—';
        }

        return self::label(self::round($average));
    }

    /** Tingkat terdekat dari sebuah rata-rata, dijepit ke rentang 1–5. */
    public static function round(float $average): int
    {
        return max(self::MIN, min(self::MAX, (int) round($average)));
    }

    /** Rata-rata sebagai persen (untuk lebar bar rekap). */
    public static function percentage(?float $average): float
    {
        if ($average === null) {
            return 0.0;
        }

        return round(($average / self::MAX) * 100, 1);
    }
}

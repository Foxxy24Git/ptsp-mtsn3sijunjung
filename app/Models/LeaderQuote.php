<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class LeaderQuote extends Model implements HasMedia
{
    use InteractsWithMedia;

    public const PLACEMENT_PIMPINAN = 'pimpinan';

    public const PLACEMENT_PENDAMPING = 'pendamping';

    protected $fillable = [
        'placement',
        'name',
        'title',
        'position',
        'quote',
        'background_style',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Pilihan peletakan/layout di beranda.
     * pimpinan  = sambutan besar (satu orang, dengan quote).
     * pendamping = barisan foto di bawah pimpinan (waka), nama + jabatan saja.
     */
    public static function placementOptions(): array
    {
        return [
            self::PLACEMENT_PIMPINAN => 'Pimpinan (sambutan besar)',
            self::PLACEMENT_PENDAMPING => 'Di Bawah Pimpinan (barisan foto)',
        ];
    }

    /**
     * Pilihan elemen dekoratif di belakang foto pimpinan.
     */
    public static function backgroundStyleOptions(): array
    {
        return [
            'arch' => 'Lengkung + Lingkaran',
            'circles' => 'Lingkaran Bertebaran',
            'blob' => 'Blob Lembut',
            'pill' => 'Kapsul Tegak',
            'diagonal' => 'Panel Miring',
            'rings' => 'Cincin Garis',
            'none' => 'Tanpa Elemen',
        ];
    }

    /**
     * Gaya "foto masuk ke dalam bingkai" — hanya untuk barisan di bawah pimpinan (waka).
     * Foto di-clip mengikuti bentuk bingkai, bukan cutout yang menonjol keluar.
     */
    public static function framedStyleOptions(): array
    {
        return [
            'framed_card' => 'Bingkai Kartu — foto masuk (seperti contoh)',
            'framed_arch' => 'Bingkai Lengkung — foto masuk',
            'framed_circle' => 'Bingkai Lingkaran — foto masuk',
            'framed_diagonal' => 'Bingkai Sudut Miring — foto masuk',
            'framed_blob' => 'Bingkai Blob — foto masuk',
        ];
    }

    /**
     * Pilihan elemen untuk barisan di bawah pimpinan: dekoratif (cutout) + bingkai (foto masuk).
     */
    public static function pendampingStyleOptions(): array
    {
        return self::backgroundStyleOptions() + self::framedStyleOptions();
    }

    /**
     * Apakah gaya ini membingkai foto (foto masuk ke dalam elemen)?
     */
    public static function isFramedStyle(?string $style): bool
    {
        return array_key_exists((string) $style, self::framedStyleOptions());
    }

    /**
     * Foto pimpinan (PNG transparan). Disk public agar bisa diakses web.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photo')
            ->singleFile()
            ->useDisk('public');
    }

    /**
     * URL foto pimpinan, atau null bila belum ada.
     */
    public function photoUrl(): ?string
    {
        return $this->getFirstMediaUrl('photo') ?: null;
    }
}

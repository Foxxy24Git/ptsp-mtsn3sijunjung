<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Survei "Kepuasan Layanan": satu judul + beberapa sub kepuasan layanan
 * (aspek) yang masing-masing dinilai pengaju lewat slider emoji 1–5.
 */
class SatisfactionSurvey extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'description',
        'success_message',
        'status',
        'collect_suggestion',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'collect_suggestion' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** Sub kepuasan layanan, urut sesuai susunan yang diatur admin. */
    public function aspects(): HasMany
    {
        return $this->hasMany(SatisfactionAspect::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(SatisfactionResponse::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Penjaga akses publik. Survei tanpa aspek tidak bisa dinilai sama sekali,
     * jadi diperlakukan sama seperti draft — daripada menampilkan halaman
     * kosong yang tombol kirimnya selalu gagal validasi.
     */
    public function isPublicallyOpen(): bool
    {
        return $this->status === 'published' && $this->aspects()->exists();
    }

    public function url(): string
    {
        return route('kepuasan.show', $this->slug);
    }
}

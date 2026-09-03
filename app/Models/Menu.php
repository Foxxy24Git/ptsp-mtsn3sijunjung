<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
{
    protected $fillable = [
        'label',
        'sort_order',
        'type',
        'target',
        'parent_id',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    /**
     * URL publik untuk item menu ini, berdasarkan type + target.
     *
     * - page: target = slug halaman  → /{slug}
     * - post: target = slug berita   → /berita/{slug}
     * - form: target = slug form     → /form/{slug}
     * - url : target = URL/path mentah (dipakai apa adanya)
     */
    public function url(): string
    {
        $target = trim((string) $this->target);

        if ($target === '') {
            return '#';
        }

        return match ($this->type) {
            'page' => url('/'.ltrim($target, '/')),
            'post' => route('posts.show', $target),
            'form' => route('forms.show', $target),
            default => $target,
        };
    }

    /**
     * Menu induk (untuk submenu).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'parent_id');
    }

    /**
     * Submenu di bawah menu ini.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Menu::class, 'parent_id')->orderBy('sort_order');
    }
}

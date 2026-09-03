<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Form extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'description',
        'success_message',
        'status',
        'is_service',
        'organizer',
        'work_unit_id',
        'duration_text',
        'fee_text',
        'sort_order',
        'requirements',
        'legal_basis',
    ];

    protected function casts(): array
    {
        return [
            'is_service' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function fields(): HasMany
    {
        return $this->hasMany(FormField::class)->orderBy('sort_order');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class);
    }

    public function workUnit(): BelongsTo
    {
        return $this->belongsTo(WorkUnit::class);
    }

    public function scopeServices(Builder $query): Builder
    {
        return $query->where('is_service', true);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    /** Urutan katalog: sort_order menaik, id sebagai pemecah seri. */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Penjaga akses publik. Diletakkan di model, bukan di controller, karena
     * tiga controller berbeda (katalog, rincian, pengajuan) memakai aturan yang
     * sama persis.
     */
    public function isPublishedService(): bool
    {
        return $this->is_service && $this->status === 'published';
    }
}

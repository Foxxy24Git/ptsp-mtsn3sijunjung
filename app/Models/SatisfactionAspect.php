<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Satu sub kepuasan layanan (aspek) yang dinilai, mis. "Keramahan Petugas". */
class SatisfactionAspect extends Model
{
    protected $fillable = [
        'satisfaction_survey_id',
        'label',
        'help_text',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function survey(): BelongsTo
    {
        return $this->belongsTo(SatisfactionSurvey::class, 'satisfaction_survey_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(SatisfactionAnswer::class);
    }

    /** Nama field pada form publik: aspek[<id>]. */
    public function inputName(): string
    {
        return "aspek[{$this->id}]";
    }
}

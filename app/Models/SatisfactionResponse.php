<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Satu kiriman survei kepuasan (anonim) beserta saran opsionalnya. */
class SatisfactionResponse extends Model
{
    protected $fillable = [
        'satisfaction_survey_id',
        'suggestion',
    ];

    public function survey(): BelongsTo
    {
        return $this->belongsTo(SatisfactionSurvey::class, 'satisfaction_survey_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(SatisfactionAnswer::class);
    }

    /** Nilai untuk satu aspek, atau null bila aspek itu belum pernah dijawab. */
    public function scoreFor(int $aspectId): ?int
    {
        $answer = $this->answers->firstWhere('satisfaction_aspect_id', $aspectId);

        return $answer?->score;
    }

    /** Rata-rata nilai kiriman ini (dipakai di kolom tabel rekap). */
    public function averageScore(): ?float
    {
        $scores = $this->answers->pluck('score');

        return $scores->isEmpty() ? null : round($scores->avg(), 2);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Nilai 1–5 untuk satu aspek pada satu kiriman survei. */
class SatisfactionAnswer extends Model
{
    protected $fillable = [
        'satisfaction_response_id',
        'satisfaction_aspect_id',
        'score',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'integer',
        ];
    }

    public function response(): BelongsTo
    {
        return $this->belongsTo(SatisfactionResponse::class, 'satisfaction_response_id');
    }

    public function aspect(): BelongsTo
    {
        return $this->belongsTo(SatisfactionAspect::class, 'satisfaction_aspect_id');
    }
}

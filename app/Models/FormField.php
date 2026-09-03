<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormField extends Model
{
    /** Tipe field yang didukung: kunci = nilai kolom `type`, nilai = label operator. */
    public const TYPES = [
        'text' => 'Teks singkat',
        'textarea' => 'Teks panjang',
        'date' => 'Tanggal',
        'number' => 'Angka',
        'select' => 'Pilihan (dropdown)',
        'checkbox' => 'Checkbox (pilih banyak)',
        'file' => 'Upload berkas',
    ];

    protected $fillable = [
        'form_id',
        'label',
        'type',
        'options',
        'required',
        'is_unique',
        'sort_order',
        'help_text',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'required' => 'boolean',
            'is_unique' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }
}

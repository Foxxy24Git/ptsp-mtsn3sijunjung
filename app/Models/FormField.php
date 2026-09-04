<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
        'document' => 'Dokumen untuk diunduh pengaju',
        'kelas' => 'Kelas (dari menu Data Kelas)',
    ];

    protected $fillable = [
        'form_id',
        'label',
        'type',
        'options',
        'document_path',
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

    /** URL unduhan dokumen tipe 'document' (disk public), atau null bila belum diunggah admin. */
    public function documentUrl(): ?string
    {
        return $this->document_path ? Storage::disk('public')->url($this->document_path) : null;
    }

    /** Nama berkas untuk dialog "Simpan Sebagai" pengaju: label field + ekstensi asli. */
    public function documentDownloadName(): ?string
    {
        if (! $this->document_path) {
            return null;
        }

        $ext = pathinfo($this->document_path, PATHINFO_EXTENSION);

        return Str::slug($this->label).($ext !== '' ? ".{$ext}" : '');
    }
}

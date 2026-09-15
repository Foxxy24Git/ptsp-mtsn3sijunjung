<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class FormSubmission extends Model
{
    /** Empat status permohonan. Kunci = nilai kolom, nilai = label operator/publik. */
    public const STATUSES = [
        'diajukan' => 'Diajukan',
        'diproses' => 'Diproses',
        'selesai' => 'Selesai',
        'ditolak' => 'Ditolak',
    ];

    protected $fillable = [
        'form_id',
        'data',
        'receipt_code',
        'applicant_name',
        'applicant_whatsapp',
        'applicant_email',
        'status',
        'admin_note',
        'result_document_path',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(SubmissionStatusLog::class)->orderBy('id');
    }

    /** Catat satu langkah riwayat status. Tidak mengubah kolom `status` di permohonan. */
    public function recordStatus(string $status, ?string $note = null, ?int $userId = null): SubmissionStatusLog
    {
        return $this->statusLogs()->create([
            'status' => $status,
            'note' => $note,
            'user_id' => $userId,
        ]);
    }

    /** Nama file unduhan yang rapi untuk pengaju: judul layanan + kode resi, bukan nama acak dari disk. */
    public function resultDocumentDownloadName(): ?string
    {
        if (! $this->result_document_path) {
            return null;
        }

        $ext = pathinfo($this->result_document_path, PATHINFO_EXTENSION);
        $slug = Str::slug($this->form->title.'-'.$this->receipt_code);

        return $slug.($ext !== '' ? ".{$ext}" : '');
    }
}

<?php

namespace App\Actions;

use App\Models\FormSubmission;
use Illuminate\Support\Facades\DB;

/**
 * Satu-satunya jalan mengubah status permohonan. Dipisahkan dari Filament
 * supaya perubahan status selalu diiringi pencatatan riwayat — riwayat itulah
 * yang dibaca pemohon di halaman lacak.
 */
class UpdateSubmissionStatus
{
    public function handle(FormSubmission $submission, string $status, ?string $note = null, ?int $userId = null): void
    {
        if (! array_key_exists($status, FormSubmission::STATUSES)) {
            throw new \InvalidArgumentException("Status tidak dikenal: {$status}");
        }

        DB::transaction(function () use ($submission, $status, $note, $userId): void {
            $submission->update([
                'status' => $status,
                'admin_note' => $note,
            ]);

            $submission->recordStatus($status, $note, $userId);
        });
    }
}

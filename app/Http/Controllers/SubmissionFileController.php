<?php

namespace App\Http\Controllers;

use App\Models\FormField;
use App\Models\FormSubmission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SubmissionFileController extends Controller
{
    /**
     * Berkas permohonan berisi ijazah, KTP, dan surat kehilangan sehingga
     * disimpan di disk privat. Penjagaan ditulis di controller (bukan middleware
     * `auth`) agar responsnya pasti 403 dan tidak bergantung pada keberadaan
     * rute login bernama.
     */
    public function download(FormSubmission $submission, FormField $field)
    {
        abort_unless(Auth::check(), 403);
        abort_unless($field->form_id === $submission->form_id, 404);

        $path = $submission->data[$field->id] ?? null;
        abort_if(! is_string($path) || $path === '', 404);
        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path);
    }
}

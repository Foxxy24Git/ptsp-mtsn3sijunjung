<?php

namespace App\Http\Controllers;

use App\Models\FormSubmission;
use App\Support\WhatsappNumber;
use Illuminate\Http\Request;

class TrackingController extends Controller
{
    public function index(Request $request)
    {
        // Kotak pencarian di beranda hanya minta kode resi, lalu mengarah
        // ke sini lewat query string supaya field-nya sudah terisi — pemohon
        // tinggal mengetik 4 digit WA untuk melengkapi verifikasi.
        return view('lacak.index', [
            'permohonan' => null,
            'kodeResiAwal' => $request->query('receipt_code', ''),
        ]);
    }

    public function cari(Request $request)
    {
        $validated = $request->validate([
            'receipt_code' => ['required', 'string', 'max:20'],
            'whatsapp_last4' => ['required', 'digits:4'],
        ], [], [
            'receipt_code' => 'Kode Resi',
            'whatsapp_last4' => '4 Digit Terakhir WhatsApp',
        ]);

        $permohonan = FormSubmission::query()
            ->with(['form', 'statusLogs'])
            ->where('receipt_code', strtoupper(trim($validated['receipt_code'])))
            ->first();

        $cocok = $permohonan
            && WhatsappNumber::lastFour((string) $permohonan->applicant_whatsapp) === $validated['whatsapp_last4'];

        if (! $cocok) {
            // Satu pesan untuk kedua kemungkinan kegagalan: memberi tahu bagian
            // mana yang salah akan membantu penebakan.
            return back()
                ->withInput()
                ->withErrors(['receipt_code' => 'Kode resi atau nomor tidak cocok.']);
        }

        return view('lacak.index', ['permohonan' => $permohonan]);
    }
}

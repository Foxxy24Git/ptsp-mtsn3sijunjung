<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Models\FormSubmission;
use App\Support\DynamicFieldRules;
use App\Support\ReceiptCode;
use App\Support\WhatsappNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApplicationController extends Controller
{
    /** Halaman bukti pengajuan. Kode resi hanya datang dari session sekali pakai. */
    public function selesai()
    {
        $kode = session('receipt_code');

        if (! $kode) {
            return redirect()->route('lacak.index');
        }

        return view('permohonan.selesai', [
            'kode' => $kode,
            'layanan' => session('service_title'),
        ]);
    }

    /** Formulir pengajuan satu layanan. */
    public function create(Form $form)
    {
        abort_unless($form->isPublishedService(), 404);

        $form->load('fields');

        return view('layanan.ajukan', ['layanan' => $form]);
    }

    /** Terima pengajuan, simpan berkas ke disk privat, terbitkan kode resi. */
    public function store(Request $request, Form $form)
    {
        abort_unless($form->isPublishedService(), 404);

        // Honeypot: manusia tidak pernah melihat kolom ini, bot mengisinya.
        // Ditolak diam-diam supaya bot tidak belajar dari pesan error.
        if (filled($request->input('website'))) {
            return redirect()->route('layanan.index');
        }

        $form->load('fields');

        // Sebagian layanan dikonfigurasi admin agar tidak meminta identitas
        // pemohon sama sekali -- lihat toggle "Wajib Isi Identitas & Kontak
        // Pemohon" di tab Pengaturan.
        $wajibIdentitas = $form->requires_applicant_identity ? 'required' : 'nullable';

        $aturan = array_merge([
            'applicant_name' => [$wajibIdentitas, 'string', 'max:150'],
            'applicant_whatsapp' => [$wajibIdentitas, 'string', 'max:25'],
            'applicant_email' => [$wajibIdentitas, 'email', 'max:150'],
        ], DynamicFieldRules::rules($form));

        $atribut = array_merge([
            'applicant_name' => 'Nama Lengkap Pemohon',
            'applicant_whatsapp' => 'Nomor WhatsApp',
            'applicant_email' => 'Alamat Email',
        ], DynamicFieldRules::attributes($form));

        $validated = $request->validate($aturan, [], $atribut);

        $nomor = null;
        if (filled($validated['applicant_whatsapp'] ?? null)) {
            $nomor = WhatsappNumber::normalize($validated['applicant_whatsapp']);
            if (strlen($nomor) < 10 || strlen($nomor) > 15) {
                throw ValidationException::withMessages([
                    'applicant_whatsapp' => 'Nomor WhatsApp tidak valid. Contoh penulisan: 081234567890.',
                ]);
            }
        }

        $this->tolakNilaiDuplikat($form, $validated);

        $permohonan = DB::transaction(function () use ($form, $request, $validated, $nomor): FormSubmission {
            $data = [];

            foreach ($form->fields as $field) {
                // Dokumen unduhan admin, bukan isian pemohon -- tidak ada apa pun
                // untuk disimpan per pengajuan.
                if ($field->type === 'document') {
                    continue;
                }

                $key = DynamicFieldRules::key($field);

                if ($field->type === 'file') {
                    if ($request->hasFile($key)) {
                        // Disk 'local' berada di luar public/: berkas ijazah & KTP
                        // tidak boleh bisa diunduh siapa pun yang menebak URL.
                        $data[$field->id] = $request->file($key)->store('permohonan/'.$form->id, 'local');
                    }

                    continue;
                }

                $data[$field->id] = $validated[$key] ?? ($field->type === 'checkbox' ? [] : null);
            }

            $permohonan = $form->submissions()->create([
                'receipt_code' => ReceiptCode::generateUnique(),
                'applicant_name' => $validated['applicant_name'] ?? null,
                'applicant_whatsapp' => $nomor,
                'applicant_email' => $validated['applicant_email'] ?? null,
                'status' => 'diajukan',
                'data' => $data,
            ]);

            $permohonan->recordStatus('diajukan', 'Permohonan diterima sistem.');

            return $permohonan;
        });

        return redirect()
            ->route('permohonan.selesai')
            ->with('receipt_code', $permohonan->receipt_code)
            ->with('service_title', $form->title);
    }

    /**
     * Field bertanda "tidak boleh duplikat" (mis. NIS) menolak nilai yang sudah
     * pernah dipakai pada layanan yang sama.
     */
    private function tolakNilaiDuplikat(Form $form, array $validated): void
    {
        $errors = [];

        foreach ($form->fields as $field) {
            if (! $field->is_unique) {
                continue;
            }

            $key = DynamicFieldRules::key($field);
            $nilai = $validated[$key] ?? null;

            if ($nilai === null || $nilai === '' || $nilai === []) {
                continue;
            }

            $terpakai = $form->submissions()
                ->get(['data'])
                ->contains(fn (FormSubmission $s): bool => ($s->data[$field->id] ?? null) === $nilai);

            if ($terpakai) {
                $errors[$key] = "Nilai untuk \"{$field->label}\" sudah pernah digunakan dan tidak boleh sama.";
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}

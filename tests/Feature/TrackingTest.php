<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\FormSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackingTest extends TestCase
{
    use RefreshDatabase;

    private function permohonan(): FormSubmission
    {
        $layanan = Form::create(['title' => 'SPP Pengambilan Ijazah', 'slug' => 'pengambilan-ijazah', 'status' => 'published']);
        $layanan->fields()->create(['label' => 'Nama Murid', 'type' => 'text', 'required' => true, 'sort_order' => 1]);

        $permohonan = $layanan->submissions()->create([
            'receipt_code' => 'PTSP-2609-A7K3QX',
            'applicant_name' => 'Budi Santoso',
            'applicant_whatsapp' => '6281234567890',
            'applicant_email' => 'budi@example.com',
            'status' => 'diproses',
            'admin_note' => 'Berkas sedang diverifikasi petugas.',
            'data' => [$layanan->fields->first()->id => 'Rahasia Isian Formulir'],
        ]);
        $permohonan->recordStatus('diajukan', 'Permohonan diterima sistem.');
        $permohonan->recordStatus('diproses', 'Berkas sedang diverifikasi petugas.');

        return $permohonan;
    }

    public function test_halaman_lacak_menampilkan_formulir(): void
    {
        $this->get('/lacak')
            ->assertOk()
            ->assertSee('Lacak Permohonan')
            ->assertSee('Kode Resi');
    }

    public function test_kombinasi_benar_menampilkan_status_dan_timeline(): void
    {
        $this->permohonan();

        $this->post('/lacak', [
            'receipt_code' => 'PTSP-2609-A7K3QX',
            'whatsapp_last4' => '7890',
        ])
            ->assertOk()
            ->assertSee('SPP Pengambilan Ijazah')
            ->assertSee('Diproses')
            ->assertSee('Berkas sedang diverifikasi petugas.')
            ->assertSee('Permohonan diterima sistem.');
    }

    public function test_isian_formulir_tidak_ditampilkan_di_halaman_lacak(): void
    {
        // Verifikasi 4 digit terlalu lemah untuk membuka data pribadi lengkap.
        $this->permohonan();

        $this->post('/lacak', [
            'receipt_code' => 'PTSP-2609-A7K3QX',
            'whatsapp_last4' => '7890',
        ])->assertOk()->assertDontSee('Rahasia Isian Formulir');
    }

    public function test_kode_resi_huruf_kecil_tetap_diterima(): void
    {
        $this->permohonan();

        $this->post('/lacak', [
            'receipt_code' => 'ptsp-2609-a7k3qx',
            'whatsapp_last4' => '7890',
        ])->assertOk()->assertSee('SPP Pengambilan Ijazah');
    }

    public function test_digit_salah_ditolak_dengan_pesan_umum(): void
    {
        $this->permohonan();

        $this->post('/lacak', [
            'receipt_code' => 'PTSP-2609-A7K3QX',
            'whatsapp_last4' => '0000',
        ])->assertSessionHasErrors(['receipt_code' => 'Kode resi atau nomor tidak cocok.']);
    }

    public function test_kode_resi_tidak_dikenal_ditolak_dengan_pesan_yang_sama(): void
    {
        $this->permohonan();

        $this->post('/lacak', [
            'receipt_code' => 'PTSP-2609-ZZZZZZ',
            'whatsapp_last4' => '7890',
        ])->assertSessionHasErrors(['receipt_code' => 'Kode resi atau nomor tidak cocok.']);
    }

    public function test_digit_bukan_empat_angka_ditolak(): void
    {
        $this->post('/lacak', [
            'receipt_code' => 'PTSP-2609-A7K3QX',
            'whatsapp_last4' => 'abcd',
        ])->assertSessionHasErrors('whatsapp_last4');
    }
}

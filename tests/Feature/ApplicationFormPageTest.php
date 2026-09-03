<?php

namespace Tests\Feature;

use App\Models\Form;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationFormPageTest extends TestCase
{
    use RefreshDatabase;

    private function layanan(): Form
    {
        $layanan = Form::create([
            'title' => 'SPP Legalisasi Ijazah Online',
            'slug' => 'legalisasi-online',
            'status' => 'published',
        ]);
        $layanan->fields()->create(['label' => 'Tanda terima pengambilan ijazah/STTB', 'type' => 'text', 'required' => true, 'sort_order' => 1]);
        $layanan->fields()->create(['label' => 'Nama', 'type' => 'text', 'required' => true, 'sort_order' => 2]);
        $layanan->fields()->create(['label' => 'Upload Ijazah', 'type' => 'file', 'required' => false, 'sort_order' => 3]);

        return $layanan;
    }

    public function test_halaman_menampilkan_blok_identitas_bawaan(): void
    {
        $this->layanan();

        $this->get('/layanan/legalisasi-online/ajukan')
            ->assertOk()
            ->assertSee('Identitas &amp; Kontak Pemohon', false)
            ->assertSee('Nama Lengkap Pemohon')
            ->assertSee('Layanan yang Dituju')
            ->assertSee('Nomor WhatsApp')
            ->assertSee('Alamat Email')
            ->assertSee('SPP Legalisasi Ijazah Online');
    }

    public function test_field_dinamis_ditampilkan_bernomor_urut(): void
    {
        $this->layanan();

        $this->get('/layanan/legalisasi-online/ajukan')
            ->assertOk()
            ->assertSee('Kelengkapan Berkas &amp; Data Isian', false)
            ->assertSee('1. Tanda terima pengambilan ijazah/STTB')
            ->assertSee('2. Nama')
            ->assertSee('3. Upload Ijazah');
    }

    public function test_field_opsional_ditandai_eksplisit(): void
    {
        $this->layanan();

        $this->get('/layanan/legalisasi-online/ajukan')
            ->assertOk()
            ->assertSee('(Opsional)');
    }

    public function test_area_unggah_menampilkan_batasan_berkas(): void
    {
        $this->layanan();

        $this->get('/layanan/legalisasi-online/ajukan')
            ->assertOk()
            ->assertSee('Klik atau Tarik File ke Area Ini')
            ->assertSee('Format: PDF, JPG, PNG (Maksimal 5MB)');
    }

    public function test_tombol_kirim_memakai_teks_yang_disepakati(): void
    {
        $this->layanan();

        $this->get('/layanan/legalisasi-online/ajukan')
            ->assertOk()
            ->assertSee('Kirim Permohonan Sekarang');
    }

    public function test_layanan_draft_tidak_bisa_diajukan(): void
    {
        Form::create(['title' => 'Rahasia', 'slug' => 'rahasia', 'status' => 'draft']);

        $this->get('/layanan/rahasia/ajukan')->assertNotFound();
    }
}

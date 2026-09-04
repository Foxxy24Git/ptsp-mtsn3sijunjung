<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\FormSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Fitur "dokumen unduhan": admin mengunggah berkas (mis. blanko keluar/masuk
 * siswa) lewat field bertipe 'document' pada tab Field Formulir, pengaju bisa
 * mengunduhnya baik di modal Rincian, halaman detail penuh, maupun saat mengisi
 * formulir Ajukan -- tanpa pernah dianggap isian yang perlu divalidasi/disimpan.
 */
class ServiceDocumentFieldTest extends TestCase
{
    use RefreshDatabase;

    private function layanan(): Form
    {
        return Form::create([
            'title' => 'Pengajuan Keluar Masuk Siswa',
            'slug' => 'keluar-masuk-siswa',
            'status' => 'published',
        ]);
    }

    public function test_halaman_ajukan_menampilkan_tautan_unduh_untuk_field_dokumen(): void
    {
        Storage::fake('public');
        $layanan = $this->layanan();
        $layanan->fields()->create([
            'label' => 'Blanko Surat Keluar Masuk', 'type' => 'document',
            'document_path' => 'layanan-dokumen/blanko.pdf', 'sort_order' => 1,
        ]);

        $this->get('/layanan/keluar-masuk-siswa/ajukan')
            ->assertOk()
            ->assertSee('Unduh Blanko Surat Keluar Masuk')
            ->assertDontSee('name="field_', false);
    }

    public function test_halaman_ajukan_menampilkan_placeholder_bila_admin_belum_unggah(): void
    {
        $layanan = $this->layanan();
        $layanan->fields()->create(['label' => 'Blanko Belum Ada', 'type' => 'document', 'sort_order' => 1]);

        $this->get('/layanan/keluar-masuk-siswa/ajukan')
            ->assertOk()
            ->assertSee('Dokumen belum diunggah operator.');
    }

    public function test_pengajuan_berhasil_tanpa_data_untuk_field_dokumen(): void
    {
        Storage::fake('public');
        $layanan = $this->layanan();
        $dok = $layanan->fields()->create([
            'label' => 'Blanko', 'type' => 'document',
            'document_path' => 'layanan-dokumen/blanko.pdf', 'sort_order' => 1,
        ]);
        $layanan->fields()->create(['label' => 'Nama Murid', 'type' => 'text', 'required' => true, 'sort_order' => 2]);

        $response = $this->post('/layanan/keluar-masuk-siswa/ajukan', [
            'applicant_name' => 'Budi Santoso',
            'applicant_whatsapp' => '081234567890',
            'applicant_email' => 'budi@example.com',
            'field_'.$layanan->fields()->where('label', 'Nama Murid')->first()->id => 'Budi',
        ]);

        $response->assertRedirect(route('permohonan.selesai'));
        $data = FormSubmission::first()->data;
        $this->assertArrayNotHasKey($dok->id, $data);
    }

    public function test_modal_rincian_di_katalog_menampilkan_tautan_unduh_dokumen(): void
    {
        Storage::fake('public');
        $layanan = $this->layanan();
        $layanan->fields()->create([
            'label' => 'Blanko Surat Keluar Masuk', 'type' => 'document',
            'document_path' => 'layanan-dokumen/blanko.pdf', 'sort_order' => 1,
        ]);

        $this->get('/layanan')
            ->assertOk()
            ->assertSee('Dokumen Terkait')
            ->assertSee('Blanko Surat Keluar Masuk')
            ->assertSee(Storage::disk('public')->url('layanan-dokumen/blanko.pdf'), false);
    }

    public function test_halaman_detail_penuh_menampilkan_dokumen_terkait(): void
    {
        Storage::fake('public');
        $layanan = $this->layanan();
        $layanan->fields()->create([
            'label' => 'Blanko Surat Keluar Masuk', 'type' => 'document',
            'document_path' => 'layanan-dokumen/blanko.pdf', 'sort_order' => 1,
        ]);

        $this->get('/layanan/keluar-masuk-siswa')
            ->assertOk()
            ->assertSee('Dokumen Terkait')
            ->assertSee('Blanko Surat Keluar Masuk');
    }

    public function test_dokumen_terkait_tidak_tampil_bila_tidak_ada_field_dokumen(): void
    {
        $this->layanan();

        $this->get('/layanan/keluar-masuk-siswa')
            ->assertOk()
            ->assertDontSee('Dokumen Terkait');

        $this->get('/layanan')->assertOk()->assertDontSee('Dokumen Terkait');
    }

    public function test_document_url_dan_download_name_helper(): void
    {
        $layanan = $this->layanan();
        $dok = $layanan->fields()->create([
            'label' => 'Blanko Surat Keluar/Masuk', 'type' => 'document',
            'document_path' => 'layanan-dokumen/xyz123.pdf', 'sort_order' => 1,
        ]);
        $kosong = $layanan->fields()->create(['label' => 'Belum Ada', 'type' => 'document', 'sort_order' => 2]);

        $this->assertSame(
            Storage::disk('public')->url('layanan-dokumen/xyz123.pdf'),
            $dok->documentUrl()
        );
        $this->assertSame('blanko-surat-keluarmasuk.pdf', $dok->documentDownloadName());
        $this->assertNull($kosong->documentUrl());
        $this->assertNull($kosong->documentDownloadName());
    }
}

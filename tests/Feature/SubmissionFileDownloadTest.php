<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class SubmissionFileDownloadTest extends TestCase
{
    use RefreshDatabase;

    private function siapkan(): array
    {
        Storage::fake('local');

        $layanan = Form::create(['title' => 'SPP Ijazah Hilang', 'slug' => 'ijazah-hilang', 'status' => 'published']);
        $field = $layanan->fields()->create(['label' => 'Surat Kehilangan', 'type' => 'file', 'required' => true, 'sort_order' => 1]);

        $path = UploadedFile::fake()->create('surat.pdf', 50, 'application/pdf')->store('permohonan/'.$layanan->id, 'local');

        $permohonan = $layanan->submissions()->create([
            'receipt_code' => 'PTSP-2609-A7K3QX',
            'applicant_name' => 'Budi',
            'applicant_whatsapp' => '6281234567890',
            'applicant_email' => 'budi@example.com',
            'data' => [$field->id => $path],
        ]);

        return [$permohonan, $field];
    }

    public function test_tamu_tidak_bisa_mengunduh_berkas(): void
    {
        [$permohonan, $field] = $this->siapkan();

        $this->get("/permohonan/{$permohonan->id}/berkas/{$field->id}")->assertForbidden();
    }

    public function test_operator_yang_login_bisa_mengunduh_berkas(): void
    {
        [$permohonan, $field] = $this->siapkan();

        $this->actingAs(User::factory()->create())
            ->get("/permohonan/{$permohonan->id}/berkas/{$field->id}")
            ->assertOk()
            ->assertDownload();
    }

    public function test_field_dari_layanan_lain_ditolak(): void
    {
        [$permohonan] = $this->siapkan();

        $lain = Form::create(['title' => 'Lain', 'slug' => 'lain', 'status' => 'published']);
        $fieldLain = $lain->fields()->create(['label' => 'X', 'type' => 'file', 'sort_order' => 1]);

        $this->actingAs(User::factory()->create())
            ->get("/permohonan/{$permohonan->id}/berkas/{$fieldLain->id}")
            ->assertNotFound();
    }

    public function test_pengaju_bisa_mengunduh_dokumen_hasil_lewat_link_bertanda_tangan(): void
    {
        Storage::fake('local');

        $layanan = Form::create(['title' => 'SPP Ijazah Rusak', 'slug' => 'ijazah-rusak', 'status' => 'published']);
        $path = UploadedFile::fake()->create('ijazah-baru.pdf', 100, 'application/pdf')->store('permohonan/'.$layanan->id.'/hasil', 'local');

        $permohonan = $layanan->submissions()->create([
            'receipt_code' => 'PTSP-2609-A7K3QX',
            'applicant_name' => 'Budi',
            'applicant_whatsapp' => '6281234567890',
            'applicant_email' => 'budi@example.com',
            'status' => 'selesai',
            'result_document_path' => $path,
            'data' => [],
        ]);

        $url = URL::temporarySignedRoute('permohonan.hasil', now()->addMinutes(30), ['submission' => $permohonan->id]);

        $this->get($url)->assertOk()->assertDownload();
    }

    public function test_dokumen_hasil_diunduh_dengan_nama_file_yang_rapi(): void
    {
        Storage::fake('local');

        $layanan = Form::create(['title' => 'SPP Ijazah Rusak', 'slug' => 'ijazah-rusak', 'status' => 'published']);
        $path = UploadedFile::fake()->create('scan-acak-xyz123.pdf', 100, 'application/pdf')->store('permohonan/'.$layanan->id.'/hasil', 'local');

        $permohonan = $layanan->submissions()->create([
            'receipt_code' => 'PTSP-2609-A7K3QX',
            'applicant_name' => 'Budi',
            'applicant_whatsapp' => '6281234567890',
            'applicant_email' => 'budi@example.com',
            'status' => 'selesai',
            'result_document_path' => $path,
            'data' => [],
        ]);

        $url = URL::temporarySignedRoute('permohonan.hasil', now()->addMinutes(30), ['submission' => $permohonan->id]);

        $this->get($url)->assertDownload('spp-ijazah-rusak-ptsp-2609-a7k3qx.pdf');
    }

    public function test_link_tanpa_tanda_tangan_valid_ditolak(): void
    {
        $layanan = Form::create(['title' => 'SPP Ijazah Rusak', 'slug' => 'ijazah-rusak', 'status' => 'published']);
        $permohonan = $layanan->submissions()->create([
            'receipt_code' => 'PTSP-2609-A7K3QX',
            'applicant_name' => 'Budi',
            'applicant_whatsapp' => '6281234567890',
            'applicant_email' => 'budi@example.com',
            'status' => 'selesai',
            'result_document_path' => 'permohonan/1/hasil/ijazah.pdf',
            'data' => [],
        ]);

        $this->get("/permohonan/{$permohonan->id}/hasil")->assertForbidden();
    }

    public function test_dokumen_hasil_tidak_bisa_diunduh_jika_status_bukan_selesai(): void
    {
        Storage::fake('local');

        $layanan = Form::create(['title' => 'SPP Ijazah Rusak', 'slug' => 'ijazah-rusak', 'status' => 'published']);
        $path = UploadedFile::fake()->create('ijazah-baru.pdf', 100, 'application/pdf')->store('permohonan/'.$layanan->id.'/hasil', 'local');

        $permohonan = $layanan->submissions()->create([
            'receipt_code' => 'PTSP-2609-A7K3QX',
            'applicant_name' => 'Budi',
            'applicant_whatsapp' => '6281234567890',
            'applicant_email' => 'budi@example.com',
            'status' => 'diproses',
            'result_document_path' => $path,
            'data' => [],
        ]);

        $url = URL::temporarySignedRoute('permohonan.hasil', now()->addMinutes(30), ['submission' => $permohonan->id]);

        $this->get($url)->assertNotFound();
    }

    public function test_dokumen_hasil_yang_belum_diunggah_mengembalikan_404(): void
    {
        $layanan = Form::create(['title' => 'SPP Ijazah Rusak', 'slug' => 'ijazah-rusak', 'status' => 'published']);

        $permohonan = $layanan->submissions()->create([
            'receipt_code' => 'PTSP-2609-A7K3QX',
            'applicant_name' => 'Budi',
            'applicant_whatsapp' => '6281234567890',
            'applicant_email' => 'budi@example.com',
            'status' => 'selesai',
            'data' => [],
        ]);

        $url = URL::temporarySignedRoute('permohonan.hasil', now()->addMinutes(30), ['submission' => $permohonan->id]);

        $this->get($url)->assertNotFound();
    }
}

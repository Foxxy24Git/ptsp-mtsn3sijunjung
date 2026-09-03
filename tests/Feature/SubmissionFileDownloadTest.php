<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
}

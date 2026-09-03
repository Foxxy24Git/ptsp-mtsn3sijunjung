<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\FormSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApplicationSubmitTest extends TestCase
{
    use RefreshDatabase;

    private Form $layanan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->layanan = Form::create([
            'title' => 'SPP Surat Pengganti Ijazah Hilang',
            'slug' => 'ijazah-hilang',
            'status' => 'published',
        ]);
        $this->layanan->fields()->create(['label' => 'Nama', 'type' => 'text', 'required' => true, 'sort_order' => 1]);
        $this->layanan->fields()->create(['label' => 'Upload Surat Kehilangan', 'type' => 'file', 'required' => true, 'sort_order' => 2]);
    }

    private function isian(array $ganti = []): array
    {
        $fields = $this->layanan->fields;

        return array_merge([
            'applicant_name' => 'Budi Santoso',
            'applicant_whatsapp' => '0812-3456-7890',
            'applicant_email' => 'budi@example.com',
            'field_'.$fields[0]->id => 'Budi Santoso',
            'field_'.$fields[1]->id => UploadedFile::fake()->create('surat.pdf', 100, 'application/pdf'),
        ], $ganti);
    }

    public function test_pengajuan_valid_membuat_permohonan_dengan_resi_dan_riwayat(): void
    {
        Storage::fake('local');

        $response = $this->post('/layanan/ijazah-hilang/ajukan', $this->isian());

        $response->assertRedirect(route('permohonan.selesai'));

        $permohonan = FormSubmission::sole();
        $this->assertSame('diajukan', $permohonan->status);
        $this->assertSame('Budi Santoso', $permohonan->applicant_name);
        $this->assertMatchesRegularExpression('/^PTSP-\d{4}-[A-Z2-9]{6}$/', $permohonan->receipt_code);
        $this->assertCount(1, $permohonan->statusLogs);
        $this->assertSame('diajukan', $permohonan->statusLogs->first()->status);
    }

    public function test_nomor_whatsapp_disimpan_ternormalisasi(): void
    {
        Storage::fake('local');

        $this->post('/layanan/ijazah-hilang/ajukan', $this->isian());

        $this->assertSame('6281234567890', FormSubmission::sole()->applicant_whatsapp);
    }

    public function test_berkas_tersimpan_di_disk_privat_bukan_publik(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $this->post('/layanan/ijazah-hilang/ajukan', $this->isian());

        $path = FormSubmission::sole()->data[$this->layanan->fields[1]->id];

        $this->assertNotNull($path);
        Storage::disk('local')->assertExists($path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_field_wajib_yang_kosong_ditolak(): void
    {
        Storage::fake('local');
        $fields = $this->layanan->fields;

        $this->post('/layanan/ijazah-hilang/ajukan', $this->isian([
            'field_'.$fields[0]->id => '',
        ]))->assertSessionHasErrors('field_'.$fields[0]->id);

        $this->assertDatabaseCount('form_submissions', 0);
    }

    public function test_identitas_yang_kosong_ditolak(): void
    {
        Storage::fake('local');

        $this->post('/layanan/ijazah-hilang/ajukan', $this->isian([
            'applicant_email' => '',
        ]))->assertSessionHasErrors('applicant_email');

        $this->assertDatabaseCount('form_submissions', 0);
    }

    public function test_nomor_whatsapp_terlalu_pendek_ditolak(): void
    {
        Storage::fake('local');

        $this->post('/layanan/ijazah-hilang/ajukan', $this->isian([
            'applicant_whatsapp' => '0812',
        ]))->assertSessionHasErrors('applicant_whatsapp');

        $this->assertDatabaseCount('form_submissions', 0);
    }

    public function test_honeypot_terisi_tidak_membuat_permohonan(): void
    {
        Storage::fake('local');

        $this->post('/layanan/ijazah-hilang/ajukan', $this->isian([
            'website' => 'http://spam.example.com',
        ]))->assertRedirect(route('layanan.index'));

        $this->assertDatabaseCount('form_submissions', 0);
    }

    public function test_layanan_draft_tidak_menerima_pengajuan(): void
    {
        Storage::fake('local');
        Form::create(['title' => 'Rahasia', 'slug' => 'rahasia', 'status' => 'draft']);

        $this->post('/layanan/rahasia/ajukan', [
            'applicant_name' => 'X',
            'applicant_whatsapp' => '081234567890',
            'applicant_email' => 'x@example.com',
        ])->assertNotFound();
    }

    public function test_field_unik_menolak_nilai_yang_sudah_dipakai(): void
    {
        Storage::fake('local');
        $this->layanan->fields[0]->update(['is_unique' => true]);

        $this->post('/layanan/ijazah-hilang/ajukan', $this->isian());
        $this->post('/layanan/ijazah-hilang/ajukan', $this->isian())
            ->assertSessionHasErrors('field_'.$this->layanan->fields[0]->id);

        $this->assertDatabaseCount('form_submissions', 1);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationModelTest extends TestCase
{
    use RefreshDatabase;

    private function layanan(): Form
    {
        return Form::create(['title' => 'Pengambilan Ijazah', 'slug' => 'pengambilan-ijazah', 'status' => 'published']);
    }

    public function test_permohonan_menyimpan_identitas_dan_default_status_diajukan(): void
    {
        $permohonan = $this->layanan()->submissions()->create([
            'receipt_code' => 'PTSP-2609-A7K3QX',
            'applicant_name' => 'Budi Santoso',
            'applicant_whatsapp' => '6281234567890',
            'applicant_email' => 'budi@example.com',
            'data' => [],
        ]);

        $this->assertSame('diajukan', $permohonan->fresh()->status);
        $this->assertSame('Budi Santoso', $permohonan->fresh()->applicant_name);
    }

    public function test_record_status_menulis_baris_riwayat(): void
    {
        $operator = User::factory()->create();
        $permohonan = $this->layanan()->submissions()->create([
            'receipt_code' => 'PTSP-2609-B8L4RY',
            'applicant_name' => 'Siti',
            'applicant_whatsapp' => '6281200000000',
            'applicant_email' => 'siti@example.com',
            'data' => [],
        ]);

        $permohonan->recordStatus('diproses', 'Berkas sedang diverifikasi.', $operator->id);

        $log = $permohonan->statusLogs()->latest('id')->first();
        $this->assertSame('diproses', $log->status);
        $this->assertSame('Berkas sedang diverifikasi.', $log->note);
        $this->assertSame($operator->id, $log->user_id);
    }

    public function test_menghapus_permohonan_ikut_menghapus_riwayatnya(): void
    {
        $permohonan = $this->layanan()->submissions()->create([
            'receipt_code' => 'PTSP-2609-C9M5SZ',
            'applicant_name' => 'Andi',
            'applicant_whatsapp' => '6281300000000',
            'applicant_email' => 'andi@example.com',
            'data' => [],
        ]);
        $permohonan->recordStatus('diajukan');

        $permohonan->delete();

        $this->assertDatabaseCount('submission_status_logs', 0);
    }

    public function test_daftar_status_berisi_empat_nilai(): void
    {
        $this->assertSame(
            ['diajukan', 'diproses', 'selesai', 'ditolak'],
            array_keys(FormSubmission::STATUSES),
        );
    }
}

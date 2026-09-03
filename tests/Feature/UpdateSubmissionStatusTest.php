<?php

namespace Tests\Feature;

use App\Actions\UpdateSubmissionStatus;
use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateSubmissionStatusTest extends TestCase
{
    use RefreshDatabase;

    private function permohonan(): FormSubmission
    {
        $layanan = Form::create(['title' => 'SPP Pengambilan Ijazah', 'slug' => 'pengambilan-ijazah', 'status' => 'published']);

        return $layanan->submissions()->create([
            'receipt_code' => 'PTSP-2609-A7K3QX',
            'applicant_name' => 'Budi',
            'applicant_whatsapp' => '6281234567890',
            'applicant_email' => 'budi@example.com',
            'data' => [],
        ]);
    }

    public function test_mengubah_status_memperbarui_kolom_dan_menulis_riwayat(): void
    {
        $permohonan = $this->permohonan();
        $operator = User::factory()->create();

        app(UpdateSubmissionStatus::class)->handle($permohonan, 'selesai', 'Ijazah siap diambil di TU.', $operator->id);

        $permohonan->refresh();
        $this->assertSame('selesai', $permohonan->status);
        $this->assertSame('Ijazah siap diambil di TU.', $permohonan->admin_note);

        $log = $permohonan->statusLogs()->latest('id')->first();
        $this->assertSame('selesai', $log->status);
        $this->assertSame($operator->id, $log->user_id);
    }

    public function test_status_tidak_dikenal_ditolak(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        app(UpdateSubmissionStatus::class)->handle($this->permohonan(), 'entah-apa');
    }
}

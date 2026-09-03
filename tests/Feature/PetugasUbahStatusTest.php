<?php

namespace Tests\Feature;

use App\Filament\Petugas\Resources\Permohonan\Pages\ListPermohonan;
use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PetugasUbahStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Livewire::test() memanggil komponen langsung tanpa melalui rute
        // HTTP /petugas/..., jadi Filament tidak tahu panel mana yang aktif
        // kecuali diberi tahu eksplisit — tanpa ini, resource mencoba
        // membangun URL ke panel default (admin) dan gagal.
        Filament::setCurrentPanel('petugas');
    }

    private function permohonan(): FormSubmission
    {
        $layanan = Form::create(['title' => 'SPP Pengambilan Ijazah', 'slug' => 'pengambilan-ijazah', 'status' => 'published']);

        return $layanan->submissions()->create([
            'receipt_code' => 'PTSP-2609-A7K3QX',
            'applicant_name' => 'Budi Santoso',
            'applicant_whatsapp' => '6281234567890',
            'applicant_email' => 'budi@example.com',
            'data' => [],
        ]);
    }

    public function test_petugas_bisa_memproses_permohonan_lewat_ubah_status(): void
    {
        $permohonan = $this->permohonan();
        $petugas = User::factory()->create(['role' => User::ROLE_PETUGAS]);

        $this->actingAs($petugas);

        Livewire::test(ListPermohonan::class)
            ->callTableAction('ubahStatus', $permohonan, data: [
                'status' => 'diproses',
                'admin_note' => 'Sedang diverifikasi.',
            ]);

        $permohonan->refresh();
        $this->assertSame('diproses', $permohonan->status);
        $this->assertSame('Sedang diverifikasi.', $permohonan->admin_note);
        $this->assertSame($petugas->id, $permohonan->statusLogs()->latest('id')->first()->user_id);
    }

    public function test_petugas_bisa_menolak_permohonan(): void
    {
        $permohonan = $this->permohonan();
        $petugas = User::factory()->create(['role' => User::ROLE_PETUGAS]);

        $this->actingAs($petugas);

        Livewire::test(ListPermohonan::class)
            ->callTableAction('ubahStatus', $permohonan, data: [
                'status' => 'ditolak',
                'admin_note' => 'Berkas tidak lengkap.',
            ]);

        $this->assertSame('ditolak', $permohonan->fresh()->status);
        $this->assertSame('ditolak', $permohonan->statusLogs()->latest('id')->first()->status);
    }
}

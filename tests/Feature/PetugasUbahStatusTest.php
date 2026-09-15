<?php

namespace Tests\Feature;

use App\Filament\Petugas\Resources\Permohonan\Pages\ListPermohonan;
use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    public function test_petugas_bisa_upload_dokumen_hasil_saat_selesai(): void
    {
        Storage::fake('local');

        $permohonan = $this->permohonan();
        $petugas = User::factory()->create(['role' => User::ROLE_PETUGAS]);

        $this->actingAs($petugas);

        Livewire::test(ListPermohonan::class)
            ->callTableAction('ubahStatus', $permohonan, data: [
                'status' => 'selesai',
                'admin_note' => 'Ijazah siap diambil di TU.',
                'result_document' => [UploadedFile::fake()->create('ijazah.pdf', 200, 'application/pdf')],
            ]);

        $permohonan->refresh();
        $this->assertSame('selesai', $permohonan->status);
        $this->assertNotNull($permohonan->result_document_path);
        Storage::disk('local')->assertExists($permohonan->result_document_path);
    }

    public function test_dokumen_hasil_tetap_tersimpan_saat_catatan_diedit_ulang(): void
    {
        Storage::fake('local');

        $permohonan = $this->permohonan();
        $petugas = User::factory()->create(['role' => User::ROLE_PETUGAS]);
        $this->actingAs($petugas);

        Livewire::test(ListPermohonan::class)
            ->callTableAction('ubahStatus', $permohonan, data: [
                'status' => 'selesai',
                'admin_note' => 'Ijazah siap diambil.',
                'result_document' => [UploadedFile::fake()->create('ijazah.pdf', 200, 'application/pdf')],
            ]);

        $pathAwal = $permohonan->refresh()->result_document_path;
        $this->assertNotNull($pathAwal);

        Livewire::test(ListPermohonan::class)
            ->callTableAction('ubahStatus', $permohonan, data: [
                'status' => 'selesai',
                'admin_note' => 'Catatan diperbarui, dokumen sama.',
            ]);

        $this->assertSame($pathAwal, $permohonan->refresh()->result_document_path);
    }
}

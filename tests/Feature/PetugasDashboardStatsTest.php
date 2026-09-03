<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PetugasDashboardStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_menampilkan_kelima_label_statistik(): void
    {
        $layanan = Form::create(['title' => 'SPP Pengambilan Ijazah', 'slug' => 'pengambilan-ijazah', 'status' => 'published']);

        $data = [
            ['PTSP-2609-STAT1', 'diajukan'],
            ['PTSP-2609-STAT2', 'diproses'],
            ['PTSP-2609-STAT3', 'diproses'],
            ['PTSP-2609-STAT4', 'selesai'],
            ['PTSP-2609-STAT5', 'ditolak'],
        ];
        foreach ($data as [$resi, $status]) {
            $layanan->submissions()->create([
                'receipt_code' => $resi,
                'applicant_name' => 'Pemohon',
                'applicant_whatsapp' => '6281234567890',
                'applicant_email' => 'pemohon@example.com',
                'status' => $status,
                'data' => [],
            ]);
        }

        // Fixture: 1 diajukan, 2 diproses, 1 selesai, 1 ditolak — total 5.
        // Angka dipilih berbeda-beda supaya tiap assertSee membuktikan
        // hitungan yang benar, bukan cuma label yang tampil.
        $this->actingAs(User::factory()->create(['role' => User::ROLE_PETUGAS]))
            ->get('/petugas')
            ->assertOk()
            ->assertSeeInOrder(['Total Permohonan', 'Diajukan', 'Diproses', 'Selesai', 'Ditolak'])
            ->assertSeeInOrder(['Total Permohonan', '5'])
            ->assertSeeInOrder(['Diproses', '2']);
    }
}

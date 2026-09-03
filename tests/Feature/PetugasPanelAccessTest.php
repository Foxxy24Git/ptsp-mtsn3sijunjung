<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PetugasPanelAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_tamu_diarahkan_pergi_saat_akses_petugas(): void
    {
        $this->get('/petugas')->assertRedirect();
    }

    public function test_petugas_bisa_membuka_dashboard_petugas(): void
    {
        $petugas = User::factory()->create(['role' => User::ROLE_PETUGAS]);

        $this->actingAs($petugas)->get('/petugas')->assertOk();
    }

    public function test_petugas_tidak_bisa_membuka_panel_admin(): void
    {
        $petugas = User::factory()->create(['role' => User::ROLE_PETUGAS]);

        $this->actingAs($petugas)->get('/admin')->assertForbidden();
    }

    public function test_administrator_tetap_bisa_membuka_kedua_panel(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRATOR]);

        $this->actingAs($admin)->get('/admin')->assertOk();
        $this->actingAs($admin)->get('/petugas')->assertOk();
    }

    public function test_petugas_melihat_antrian_permohonan(): void
    {
        $layanan = Form::create(['title' => 'SPP Pengambilan Ijazah', 'slug' => 'pengambilan-ijazah', 'status' => 'published']);
        $layanan->submissions()->create([
            'receipt_code' => 'PTSP-2609-A7K3QX',
            'applicant_name' => 'Budi Santoso',
            'applicant_whatsapp' => '6281234567890',
            'applicant_email' => 'budi@example.com',
            'data' => [],
        ]);

        $this->actingAs(User::factory()->create(['role' => User::ROLE_PETUGAS]))
            ->get('/petugas/permohonan')
            ->assertOk()
            ->assertSee('PTSP-2609-A7K3QX')
            ->assertSee('Budi Santoso');
    }
}

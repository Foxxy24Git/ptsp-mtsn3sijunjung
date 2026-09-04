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

    public function test_admin_tidak_bisa_membuka_panel_petugas(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRATOR]);

        $this->actingAs($admin)->get('/admin')->assertOk();
        $this->actingAs($admin)->get('/petugas')->assertForbidden();
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

    public function test_petugas_tidak_melihat_tombol_export_excel(): void
    {
        // FormSubmissionsTable (dipakai ulang dari admin) punya toolbar
        // Export Excel bawaan — resource petugas sengaja menghapusnya
        // (spec §2.4), jadi ini pengaman supaya perubahan tak sengaja pada
        // PermohonanResource tidak mengembalikannya diam-diam.
        $this->actingAs(User::factory()->create(['role' => User::ROLE_PETUGAS]))
            ->get('/petugas/permohonan')
            ->assertOk()
            ->assertDontSee('Export Excel');
    }

    public function test_admin_tetap_melihat_tombol_export_excel(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMINISTRATOR]))
            ->get('/admin/form-submissions')
            ->assertOk()
            ->assertSee('Export Excel');
    }
}

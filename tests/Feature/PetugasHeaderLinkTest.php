<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PetugasHeaderLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_tamu_melihat_tautan_masuk_petugas(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Masuk Petugas');
    }

    public function test_petugas_yang_sudah_login_melihat_tautan_dashboard_dengan_nama(): void
    {
        $petugas = User::factory()->create(['role' => User::ROLE_PETUGAS, 'name' => 'Rina']);

        $this->actingAs($petugas)
            ->get('/')
            ->assertOk()
            ->assertSee('Dashboard Petugas — Rina');
    }

    public function test_administrator_yang_login_tetap_melihat_tautan_masuk_petugas(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRATOR]);

        $this->actingAs($admin)
            ->get('/')
            ->assertOk()
            ->assertSee('Masuk Petugas');
    }

    public function test_tautan_masuk_petugas_mengarah_ke_halaman_login_bukan_dashboard(): void
    {
        // Kalau tautan ini mengarah ke /petugas (dashboard) alih-alih
        // /petugas/login, admin yang mengklik tombol ini langsung kena 403
        // dari middleware Authenticate — Login::mount() (yang menangani
        // sesi salah-panel) tak pernah sempat jalan sama sekali.
        $this->get('/')
            ->assertOk()
            ->assertSee('href="' . url('/petugas/login') . '"', false);
    }
}

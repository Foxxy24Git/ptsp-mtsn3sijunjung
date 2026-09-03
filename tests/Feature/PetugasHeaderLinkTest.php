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
}

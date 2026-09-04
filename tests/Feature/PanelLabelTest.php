<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanelLabelTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_login_admin_menampilkan_tulisan_khusus_admin(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('Masuk sebagai Administrator')
            ->assertDontSee('Masuk sebagai Petugas');
    }

    public function test_halaman_login_petugas_menampilkan_tulisan_khusus_petugas(): void
    {
        $this->get('/petugas/login')
            ->assertOk()
            ->assertSee('Masuk sebagai Petugas')
            ->assertDontSee('Masuk sebagai Administrator');
    }

    public function test_dashboard_admin_menampilkan_tulisan_khusus_admin(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRATOR]);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Dashboard Administrator');
    }

    public function test_dashboard_petugas_menampilkan_tulisan_khusus_petugas(): void
    {
        $petugas = User::factory()->create(['role' => User::ROLE_PETUGAS]);

        $this->actingAs($petugas)
            ->get('/petugas')
            ->assertOk()
            ->assertSee('Dashboard Petugas');
    }
}

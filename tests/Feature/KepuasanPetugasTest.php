<?php

namespace Tests\Feature;

use App\Models\SatisfactionSurvey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KepuasanPetugasTest extends TestCase
{
    use RefreshDatabase;

    private function survei(): SatisfactionSurvey
    {
        $survei = SatisfactionSurvey::create([
            'title' => 'Kepuasan Layanan PTSP',
            'slug' => 'kepuasan-layanan-ptsp',
            'status' => 'published',
        ]);

        $survei->aspects()->create(['label' => 'Kecepatan Layanan', 'sort_order' => 0]);

        return $survei->refresh();
    }

    public function test_petugas_melihat_daftar_kepuasan_layanan(): void
    {
        $this->survei();

        $this->actingAs(User::factory()->create(['role' => User::ROLE_PETUGAS]))
            ->get('/petugas/satisfaction-surveys')
            ->assertOk()
            ->assertSee('Kepuasan Layanan PTSP');
    }

    public function test_tamu_tidak_bisa_membuka_daftar_kepuasan_layanan_petugas(): void
    {
        $this->get('/petugas/satisfaction-surveys')->assertRedirect();
    }

    public function test_admin_diarahkan_ke_login_petugas_saat_membuka_menu_petugas(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMINISTRATOR]))
            ->get('/petugas/satisfaction-surveys')
            ->assertRedirect(url('/petugas/login'));
    }

    /**
     * Tombol "Hasil" di tabel petugas harus menunjuk ke URL panel petugas
     * sendiri — bukan URL admin (resource-nya beda instance meski
     * modelnya sama), supaya petugas tidak dilempar ke form login admin.
     */
    public function test_tombol_hasil_menuju_panel_petugas_bukan_admin(): void
    {
        $survei = $this->survei();

        $this->actingAs(User::factory()->create(['role' => User::ROLE_PETUGAS]))
            ->get('/petugas/satisfaction-surveys')
            ->assertOk()
            ->assertSee('/petugas/satisfaction-surveys/'.$survei->slug.'/hasil', false)
            ->assertDontSee('/admin/satisfaction-surveys/'.$survei->slug.'/hasil', false);
    }

    public function test_petugas_bisa_membuka_halaman_hasil_dan_melihat_rekap(): void
    {
        $survei = $this->survei();
        $aspek = $survei->aspects->first();

        $this->post('/kepuasan/'.$survei->slug, [
            'aspek' => [$aspek->id => 5],
        ])->assertSessionHasNoErrors();

        $this->actingAs(User::factory()->create(['role' => User::ROLE_PETUGAS]))
            ->get('/petugas/satisfaction-surveys/'.$survei->slug.'/hasil')
            ->assertOk()
            ->assertSee('Rata-rata per Sub Kepuasan Layanan')
            ->assertSee('Kecepatan Layanan')
            ->assertSee('Sangat Puas');
    }

    public function test_petugas_tidak_bisa_membuat_atau_mengubah_survei(): void
    {
        $survei = $this->survei();

        $this->actingAs(User::factory()->create(['role' => User::ROLE_PETUGAS]))
            ->get('/petugas/satisfaction-surveys/create')
            ->assertNotFound();

        $this->actingAs(User::factory()->create(['role' => User::ROLE_PETUGAS]))
            ->get('/petugas/satisfaction-surveys/'.$survei->slug.'/edit')
            ->assertNotFound();
    }

    public function test_petugas_tidak_melihat_tombol_ubah_di_daftar(): void
    {
        $this->survei();

        $this->actingAs(User::factory()->create(['role' => User::ROLE_PETUGAS]))
            ->get('/petugas/satisfaction-surveys')
            ->assertOk()
            ->assertDontSee('kepuasan-layanan-ptsp/edit', false);
    }
}

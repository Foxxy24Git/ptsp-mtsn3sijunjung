<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KelasResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_daftar_kelas_bisa_dibuka_admin(): void
    {
        Kelas::create(['name' => 'XI IPA 1']);

        $this->actingAs(User::factory()->create())
            ->get('/admin/kelas')
            ->assertOk()
            ->assertSee('XI IPA 1');
    }

    public function test_tamu_tidak_bisa_membuka_halaman_kelas(): void
    {
        $this->get('/admin/kelas')->assertRedirect();
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkUnitResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_daftar_satuan_kerja_bisa_dibuka_admin(): void
    {
        WorkUnit::create(['name' => 'Tata Usaha (TU)', 'slug' => 'tata-usaha']);

        $this->actingAs(User::factory()->create())
            ->get('/admin/work-units')
            ->assertOk()
            ->assertSee('Tata Usaha (TU)');
    }

    public function test_tamu_tidak_bisa_membuka_halaman_satuan_kerja(): void
    {
        $this->get('/admin/work-units')->assertRedirect();
    }
}

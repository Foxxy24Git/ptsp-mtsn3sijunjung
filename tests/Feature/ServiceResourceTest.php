<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_daftar_hanya_menampilkan_layanan_bukan_form_biasa(): void
    {
        Form::create(['title' => 'SPP Pengambilan Ijazah', 'slug' => 'pengambilan-ijazah', 'status' => 'published', 'is_service' => true]);
        Form::create(['title' => 'Form Kontak Biasa', 'slug' => 'kontak', 'status' => 'published', 'is_service' => false]);

        $this->actingAs(User::factory()->create())
            ->get('/admin/forms')
            ->assertOk()
            ->assertSee('SPP Pengambilan Ijazah')
            ->assertDontSee('Form Kontak Biasa');
    }

    public function test_halaman_edit_layanan_bisa_dibuka(): void
    {
        $layanan = Form::create(['title' => 'SPP Legalisasi', 'slug' => 'legalisasi', 'status' => 'published']);

        $this->actingAs(User::factory()->create())
            ->get("/admin/forms/{$layanan->id}/edit")
            ->assertOk()
            ->assertSee('Informasi Layanan')
            ->assertSee('Rincian &amp; Syarat', false)
            ->assertSee('Field Formulir');
    }

    public function test_tamu_tidak_bisa_membuka_daftar_layanan(): void
    {
        $this->get('/admin/forms')->assertRedirect();
    }
}

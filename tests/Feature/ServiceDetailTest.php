<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\WorkUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_rincian_menampilkan_syarat_dan_meta(): void
    {
        $unit = WorkUnit::create(['name' => 'Tata Usaha (TU)', 'slug' => 'tata-usaha']);
        Form::create([
            'title' => 'SPP Pengambilan Ijazah',
            'slug' => 'pengambilan-ijazah',
            'status' => 'published',
            'work_unit_id' => $unit->id,
            'duration_text' => '30 Menit',
            'fee_text' => 'Gratis',
            'requirements' => '<p>Membawa kartu identitas asli.</p>',
            'legal_basis' => '<p>Permendikbud Nomor 1 Tahun 2021.</p>',
        ]);

        $this->get('/layanan/pengambilan-ijazah')
            ->assertOk()
            ->assertSee('SPP Pengambilan Ijazah')
            ->assertSee('Membawa kartu identitas asli.')
            ->assertSee('Permendikbud Nomor 1 Tahun 2021.')
            ->assertSee('30 Menit')
            ->assertSee('Tata Usaha (TU)')
            ->assertSee('Ajukan Permohonan');
    }

    public function test_layanan_draft_mengembalikan_404(): void
    {
        Form::create(['title' => 'Rahasia', 'slug' => 'rahasia', 'status' => 'draft']);

        $this->get('/layanan/rahasia')->assertNotFound();
    }

    public function test_form_biasa_bukan_layanan_mengembalikan_404(): void
    {
        Form::create(['title' => 'Kontak', 'slug' => 'kontak', 'status' => 'published', 'is_service' => false]);

        $this->get('/layanan/kontak')->assertNotFound();
    }

    public function test_slug_tidak_dikenal_mengembalikan_404(): void
    {
        $this->get('/layanan/tidak-ada')->assertNotFound();
    }
}

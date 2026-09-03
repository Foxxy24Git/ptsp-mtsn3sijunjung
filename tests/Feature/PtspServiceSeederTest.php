<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\WorkUnit;
use Database\Seeders\PtspServiceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PtspServiceSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_membuat_satuan_kerja_dan_lima_layanan(): void
    {
        $this->seed(PtspServiceSeeder::class);

        $this->assertSame(3, WorkUnit::count());
        $this->assertSame(5, Form::query()->services()->count());

        $layanan = Form::where('slug', 'surat-pengganti-ijazah-hilang')->sole();
        $this->assertSame('SPP Surat Pengganti Ijazah Hilang', $layanan->title);
        $this->assertSame('1 Jam', $layanan->duration_text);
        $this->assertSame('Gratis', $layanan->fee_text);
        $this->assertSame('published', $layanan->status);
        $this->assertSame('Tata Usaha (TU)', $layanan->workUnit->name);
        $this->assertSame(
            ['Nama', 'Upload Surat Kehilangan', 'Fotokopi Ijazah', 'Foto 3x4'],
            $layanan->fields->pluck('label')->all(),
        );
    }

    public function test_seeder_aman_dijalankan_dua_kali(): void
    {
        $this->seed(PtspServiceSeeder::class);
        $this->seed(PtspServiceSeeder::class);

        $this->assertSame(3, WorkUnit::count());
        $this->assertSame(5, Form::query()->services()->count());
        $this->assertSame(4, Form::where('slug', 'surat-pengganti-ijazah-hilang')->sole()->fields()->count());
    }

    public function test_seeder_tidak_menimpa_perubahan_operator(): void
    {
        $this->seed(PtspServiceSeeder::class);

        $layanan = Form::where('slug', 'pengambilan-ijazah')->sole();
        $layanan->update(['duration_text' => '15 Menit', 'requirements' => '<p>Diubah operator.</p>']);

        $this->seed(PtspServiceSeeder::class);

        $layanan->refresh();
        $this->assertSame('15 Menit', $layanan->duration_text);
        $this->assertSame('<p>Diubah operator.</p>', $layanan->requirements);
    }
}

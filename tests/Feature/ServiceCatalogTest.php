<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\WorkUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceCatalogTest extends TestCase
{
    use RefreshDatabase;

    private WorkUnit $tu;

    private WorkUnit $kesiswaan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tu = WorkUnit::create(['name' => 'Tata Usaha (TU)', 'slug' => 'tata-usaha', 'sort_order' => 1]);
        $this->kesiswaan = WorkUnit::create(['name' => 'Kesiswaan', 'slug' => 'kesiswaan', 'sort_order' => 2]);

        Form::create(['title' => 'SPP Pengambilan Ijazah', 'slug' => 'pengambilan-ijazah', 'status' => 'published', 'work_unit_id' => $this->tu->id, 'duration_text' => '30 Menit', 'sort_order' => 1]);
        Form::create(['title' => 'SPP Legalisasi Ijazah Online', 'slug' => 'legalisasi-online', 'status' => 'published', 'work_unit_id' => $this->tu->id, 'duration_text' => '1-2 Hari', 'sort_order' => 2]);
        Form::create(['title' => 'SPP Mutasi Masuk', 'slug' => 'mutasi-masuk', 'status' => 'published', 'work_unit_id' => $this->kesiswaan->id, 'duration_text' => '3 Hari', 'sort_order' => 3]);
        Form::create(['title' => 'Layanan Belum Terbit', 'slug' => 'belum-terbit', 'status' => 'draft', 'work_unit_id' => $this->tu->id, 'sort_order' => 4]);
        Form::create(['title' => 'Form Kontak Biasa', 'slug' => 'kontak', 'status' => 'published', 'is_service' => false, 'sort_order' => 5]);
    }

    public function test_katalog_hanya_menampilkan_layanan_terbit(): void
    {
        $this->get('/layanan')
            ->assertOk()
            ->assertSee('SPP Pengambilan Ijazah')
            ->assertSee('SPP Mutasi Masuk')
            ->assertSee('30 Menit')
            ->assertSee('Gratis')
            ->assertDontSee('Layanan Belum Terbit')
            ->assertDontSee('Form Kontak Biasa');
    }

    public function test_filter_satuan_kerja_menyaring_hasil(): void
    {
        $this->get('/layanan?unit=kesiswaan')
            ->assertOk()
            ->assertSee('SPP Mutasi Masuk')
            ->assertDontSee('SPP Pengambilan Ijazah');
    }

    public function test_pencarian_judul_menyaring_hasil(): void
    {
        $this->get('/layanan?q=legalisasi')
            ->assertOk()
            ->assertSee('SPP Legalisasi Ijazah Online')
            ->assertDontSee('SPP Mutasi Masuk');
    }

    public function test_nomor_kartu_tetap_global_walau_hasil_tersaring(): void
    {
        // "SPP Mutasi Masuk" adalah layanan ke-3 pada urutan penuh. Saat katalog
        // disaring ke Kesiswaan ia menjadi satu-satunya hasil, tapi nomornya
        // harus tetap #3 — bukan #1 — supaya bisa dirujuk secara lisan.
        // Dicocokkan lewat markup badge persis (bukan '#1' polos), karena
        // '#1' juga muncul sebagai substring kode warna hex (mis. #111827)
        // pada <style> bawaan layout.
        $this->get('/layanan?unit=kesiswaan')
            ->assertOk()
            ->assertSee('>#3<', false)
            ->assertDontSee('>#1<', false);
    }

    public function test_chip_satuan_kerja_ditampilkan(): void
    {
        $this->get('/layanan')
            ->assertOk()
            ->assertSee('Seluruh Satuan Kerja')
            ->assertSee('Tata Usaha (TU)')
            ->assertSee('Kesiswaan');
    }
}

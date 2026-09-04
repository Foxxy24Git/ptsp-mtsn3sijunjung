<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\SatisfactionSurvey;
use App\Support\SatisfactionRecap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KepuasanLayananTest extends TestCase
{
    use RefreshDatabase;

    /** Survei terbit dengan dua sub kepuasan layanan. */
    private function survei(array $atribut = []): SatisfactionSurvey
    {
        $survei = SatisfactionSurvey::create(array_merge([
            'title' => 'Kepuasan Layanan PTSP',
            'slug' => 'kepuasan-layanan-ptsp',
            'description' => 'Bantu kami memperbaiki layanan.',
            'status' => 'published',
            'collect_suggestion' => true,
        ], $atribut));

        $survei->aspects()->create(['label' => 'Kecepatan Layanan', 'sort_order' => 0]);
        $survei->aspects()->create(['label' => 'Keramahan Petugas', 'sort_order' => 1]);

        return $survei->refresh();
    }

    public function test_url_menu_tipe_kepuasan_menunjuk_halaman_survei(): void
    {
        $this->survei();

        $menu = Menu::create([
            'label' => 'Kepuasan Layanan',
            'type' => 'kepuasan',
            'target' => 'kepuasan-layanan-ptsp',
            'sort_order' => 0,
        ]);

        $this->assertSame(url('/kepuasan/kepuasan-layanan-ptsp'), $menu->url());
    }

    public function test_menu_kepuasan_tampil_di_navigasi_publik(): void
    {
        $this->survei();

        Menu::create([
            'label' => 'Survei Kepuasan',
            'type' => 'kepuasan',
            'target' => 'kepuasan-layanan-ptsp',
            'sort_order' => 0,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Survei Kepuasan')
            ->assertSee(url('/kepuasan/kepuasan-layanan-ptsp'));
    }

    public function test_halaman_survei_menampilkan_sub_dan_slider(): void
    {
        $survei = $this->survei();

        $this->get('/kepuasan/'.$survei->slug)
            ->assertOk()
            ->assertSee('Kepuasan Layanan PTSP')
            ->assertSee('Kecepatan Layanan')
            ->assertSee('Keramahan Petugas')
            // Kontrolnya input range asli supaya tetap bisa digeser tanpa JS.
            ->assertSee('type="range"', false)
            ->assertSee('name="aspek['.$survei->aspects->first()->id.']"', false)
            ->assertSee('Saran &amp; Masukan', false);
    }

    public function test_survei_draft_menghasilkan_404(): void
    {
        $survei = $this->survei(['status' => 'draft']);

        $this->get('/kepuasan/'.$survei->slug)->assertNotFound();
        $this->post('/kepuasan/'.$survei->slug, [])->assertNotFound();
    }

    public function test_survei_tanpa_sub_menghasilkan_404(): void
    {
        $survei = SatisfactionSurvey::create([
            'title' => 'Belum Siap',
            'slug' => 'belum-siap',
            'status' => 'published',
        ]);

        $this->get('/kepuasan/'.$survei->slug)->assertNotFound();
    }

    public function test_pengaju_bisa_mengirim_penilaian(): void
    {
        $survei = $this->survei();
        [$cepat, $ramah] = $survei->aspects->all();

        $this->post('/kepuasan/'.$survei->slug, [
            'aspek' => [$cepat->id => 5, $ramah->id => 4],
            'saran' => 'Loket tambah satu saat jam sibuk.',
        ])
            ->assertRedirect('/kepuasan/'.$survei->slug)
            ->assertSessionHas('kepuasan_sukses');

        $this->assertDatabaseCount('satisfaction_responses', 1);
        $this->assertDatabaseHas('satisfaction_responses', [
            'satisfaction_survey_id' => $survei->id,
            'suggestion' => 'Loket tambah satu saat jam sibuk.',
        ]);
        $this->assertDatabaseHas('satisfaction_answers', [
            'satisfaction_aspect_id' => $cepat->id,
            'score' => 5,
        ]);
        $this->assertDatabaseHas('satisfaction_answers', [
            'satisfaction_aspect_id' => $ramah->id,
            'score' => 4,
        ]);
    }

    public function test_nilai_di_luar_rentang_ditolak(): void
    {
        $survei = $this->survei();
        [$cepat, $ramah] = $survei->aspects->all();

        $this->post('/kepuasan/'.$survei->slug, [
            'aspek' => [$cepat->id => 9, $ramah->id => 4],
        ])->assertSessionHasErrors('aspek.'.$cepat->id);

        $this->assertDatabaseCount('satisfaction_responses', 0);
    }

    public function test_sub_yang_tidak_dinilai_ditolak(): void
    {
        $survei = $this->survei();
        [$cepat, $ramah] = $survei->aspects->all();

        $this->post('/kepuasan/'.$survei->slug, [
            'aspek' => [$cepat->id => 3],
        ])->assertSessionHasErrors('aspek.'.$ramah->id);

        $this->assertDatabaseCount('satisfaction_responses', 0);
    }

    public function test_honeypot_membuang_kiriman_bot(): void
    {
        $survei = $this->survei();
        [$cepat, $ramah] = $survei->aspects->all();

        $this->post('/kepuasan/'.$survei->slug, [
            'website' => 'https://spam.example',
            'aspek' => [$cepat->id => 5, $ramah->id => 5],
        ])->assertRedirect('/kepuasan/'.$survei->slug);

        $this->assertDatabaseCount('satisfaction_responses', 0);
    }

    public function test_saran_diabaikan_bila_kolomnya_dimatikan(): void
    {
        $survei = $this->survei(['collect_suggestion' => false]);
        [$cepat, $ramah] = $survei->aspects->all();

        $this->get('/kepuasan/'.$survei->slug)
            ->assertOk()
            ->assertDontSee('Saran &amp; Masukan', false);

        $this->post('/kepuasan/'.$survei->slug, [
            'aspek' => [$cepat->id => 3, $ramah->id => 3],
            'saran' => 'Ditulis lewat request mentah.',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('satisfaction_responses', [
            'satisfaction_survey_id' => $survei->id,
            'suggestion' => null,
        ]);
    }

    public function test_rekap_menghitung_rata_rata_per_sub(): void
    {
        $survei = $this->survei();
        [$cepat, $ramah] = $survei->aspects->all();

        foreach ([[5, 4], [3, 2]] as [$nilaiCepat, $nilaiRamah]) {
            $this->post('/kepuasan/'.$survei->slug, [
                'aspek' => [$cepat->id => $nilaiCepat, $ramah->id => $nilaiRamah],
            ])->assertSessionHasNoErrors();
        }

        $rekap = SatisfactionRecap::for($survei->refresh());

        $this->assertSame(2, $rekap['responden']);
        $this->assertSame(4, $rekap['jawaban']);
        $this->assertSame(3.5, $rekap['average']);            // (5+4+3+2) / 4
        $this->assertSame('Puas', $rekap['grade']);           // 3.5 dibulatkan ke 4
        $this->assertSame(4.0, $rekap['aspects'][0]['average']);
        $this->assertSame(3.0, $rekap['aspects'][1]['average']);
        $this->assertSame(1, $rekap['aspects'][0]['distribution'][5]);
        $this->assertSame(1, $rekap['aspects'][0]['distribution'][3]);
        $this->assertSame(0, $rekap['aspects'][0]['distribution'][1]);
    }

    public function test_menghapus_survei_ikut_menghapus_sub_dan_jawabannya(): void
    {
        $survei = $this->survei();
        [$cepat, $ramah] = $survei->aspects->all();

        $this->post('/kepuasan/'.$survei->slug, [
            'aspek' => [$cepat->id => 4, $ramah->id => 4],
        ])->assertSessionHasNoErrors();

        $survei->delete();

        $this->assertDatabaseCount('satisfaction_aspects', 0);
        $this->assertDatabaseCount('satisfaction_responses', 0);
        $this->assertDatabaseCount('satisfaction_answers', 0);
    }
}

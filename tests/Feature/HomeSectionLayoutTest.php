<?php

namespace Tests\Feature;

use App\Models\Stat;
use App\Settings\GeneralSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeSectionLayoutTest extends TestCase
{
    use RefreshDatabase;

    private function setSections(array $sections): void
    {
        $settings = app(GeneralSettings::class);
        $settings->home_sections = $sections;
        $settings->save();
    }

    private function makeStat(): Stat
    {
        return Stat::create([
            'icon' => 'heroicon-o-users',
            'label' => 'Mahasiswa Aktif',
            'value' => 15307,
            'color' => '#7f1d1d',
            'is_active' => true,
            'sort_order' => 0,
        ]);
    }

    public function test_sections_render_in_configured_order(): void
    {
        $this->makeStat();
        $this->setSections([
            ['key' => 'posts', 'visible' => true],
            ['key' => 'stats', 'visible' => true],
            ['key' => 'hero', 'visible' => true],
        ]);

        $content = $this->get('/')->assertOk()->getContent();

        $posPosts = strpos($content, 'Berita Terbaru');
        $posStats = strpos($content, 'data-countup');
        $posHero = strpos($content, 'Selamat Datang di');

        $this->assertNotFalse($posPosts);
        $this->assertNotFalse($posStats);
        $this->assertNotFalse($posHero);
        $this->assertLessThan($posStats, $posPosts, 'posts harus sebelum stats');
        $this->assertLessThan($posHero, $posStats, 'stats harus sebelum hero');
    }

    public function test_section_hidden_when_visible_false(): void
    {
        $this->makeStat();
        $this->setSections([
            ['key' => 'hero', 'visible' => true],
            ['key' => 'stats', 'visible' => false],
            ['key' => 'posts', 'visible' => true],
        ]);

        $response = $this->get('/')->assertOk();
        // Section berita tetap tampil, statistik disembunyikan manual.
        $response->assertSee('Berita Terbaru');
        $response->assertDontSee('data-countup', false);
    }

    public function test_section_auto_hidden_when_data_empty(): void
    {
        // stats visible=true tapi tak ada Stat -> tetap tak tampil (regresi lama).
        $this->setSections([
            ['key' => 'stats', 'visible' => true],
            ['key' => 'posts', 'visible' => true],
        ]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('data-countup', false);
    }

    public function test_missing_key_is_still_rendered_via_normalization(): void
    {
        // Data lama tanpa 'posts' -> normalisasi meng-append, section tetap muncul.
        $this->setSections([
            ['key' => 'hero', 'visible' => true],
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Berita Terbaru');
    }

    public function test_default_settings_render_original_order(): void
    {
        // Tanpa mengubah apa pun (pakai default migration), urutan asli beranda
        // harus tetap: hero -> stats -> posts. Menjaga jaminan "tampilan default
        // tidak berubah sampai admin mengaturnya".
        $this->makeStat();

        $content = $this->get('/')->assertOk()->getContent();

        $posHero = strpos($content, 'Selamat Datang di');
        $posStats = strpos($content, 'data-countup');
        $posPosts = strpos($content, 'Berita Terbaru');

        $this->assertNotFalse($posHero);
        $this->assertNotFalse($posStats);
        $this->assertNotFalse($posPosts);
        $this->assertLessThan($posStats, $posHero, 'hero harus sebelum stats');
        $this->assertLessThan($posPosts, $posStats, 'stats harus sebelum posts');
    }
}

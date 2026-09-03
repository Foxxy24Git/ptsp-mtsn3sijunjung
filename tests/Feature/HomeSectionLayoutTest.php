<?php

namespace Tests\Feature;

use App\Models\Form;
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

    private function makeLayanan(): Form
    {
        return Form::create([
            'title' => 'SPP Pengambilan Ijazah',
            'slug' => 'pengambilan-ijazah',
            'status' => 'published',
            'is_service' => true,
        ]);
    }

    public function test_sections_render_in_configured_order(): void
    {
        $this->makeLayanan();
        $this->setSections([
            ['key' => 'layanan', 'visible' => true],
            ['key' => 'hero', 'visible' => true],
        ]);

        $content = $this->get('/')->assertOk()->getContent();

        $posLayanan = strpos($content, 'Layanan PTSP');
        $posHero = strpos($content, 'Selamat Datang di');

        $this->assertNotFalse($posLayanan);
        $this->assertNotFalse($posHero);
        $this->assertLessThan($posHero, $posLayanan, 'layanan harus sebelum hero');
    }

    public function test_section_hidden_when_visible_false(): void
    {
        $this->makeLayanan();
        $this->setSections([
            ['key' => 'hero', 'visible' => true],
            ['key' => 'layanan', 'visible' => false],
        ]);

        $response = $this->get('/')->assertOk();
        $response->assertSee('Selamat Datang di');
        $response->assertDontSee('Layanan PTSP');
    }

    public function test_section_auto_hidden_when_data_empty(): void
    {
        // layanan visible=true tapi tak ada Form berstatus published -> tetap
        // tak tampil (regresi lama: guard "data ada" tetap berlaku).
        $this->setSections([
            ['key' => 'hero', 'visible' => true],
            ['key' => 'layanan', 'visible' => true],
        ]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('Layanan PTSP');
    }

    public function test_missing_key_is_still_rendered_via_normalization(): void
    {
        // Data lama tanpa 'layanan' -> normalisasi meng-append, section tetap muncul.
        $this->makeLayanan();
        $this->setSections([
            ['key' => 'hero', 'visible' => true],
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Layanan PTSP');
    }

    public function test_default_settings_render_original_order(): void
    {
        // Tanpa mengubah apa pun (pakai default migration), urutan asli beranda
        // harus tetap: hero -> layanan. Menjaga jaminan "tampilan default
        // tidak berubah sampai admin mengaturnya".
        $this->makeLayanan();

        $content = $this->get('/')->assertOk()->getContent();

        $posHero = strpos($content, 'Selamat Datang di');
        $posLayanan = strpos($content, 'Layanan PTSP');

        $this->assertNotFalse($posHero);
        $this->assertNotFalse($posLayanan);
        $this->assertLessThan($posLayanan, $posHero, 'hero harus sebelum layanan');
    }
}

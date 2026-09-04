<?php

namespace Tests\Feature;

use App\Settings\GeneralSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HeroBackgroundTest extends TestCase
{
    use RefreshDatabase;

    private function setSettings(array $values): void
    {
        $settings = app(GeneralSettings::class);
        foreach ($values as $key => $value) {
            $settings->{$key} = $value;
        }
        $settings->save();
    }

    public function test_hero_falls_back_to_primary_color_gradient(): void
    {
        $this->setSettings([
            'primary_color' => '#166534',
            'hero_bg_image' => null,
            'hero_bg_from' => null,
            'hero_bg_to' => null,
        ]);

        // #166534 digelapkan 55% => #0a2d17 (dipakai sebagai ujung gradasi otomatis).
        $this->get('/')
            ->assertOk()
            ->assertSee('linear-gradient(135deg, #166534 0%, #0a2d17 100%)', false);
    }

    public function test_hero_uses_custom_gradient_colors(): void
    {
        $this->setSettings([
            'hero_bg_image' => null,
            'hero_bg_from' => '#0ea5e9',
            'hero_bg_to' => '#111827',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('linear-gradient(135deg, #0ea5e9 0%, #111827 100%)', false);
    }

    public function test_hero_uses_uploaded_background_image_with_dark_overlay(): void
    {
        $this->setSettings(['hero_bg_image' => 'hero/latar.jpg']);

        $this->get('/')
            ->assertOk()
            // Blade meng-escape kutip di atribut style jadi &#039; (tetap valid di browser).
            ->assertSee('background-image: url(&#039;'.Storage::disk('public')->url('hero/latar.jpg').'&#039;)', false)
            ->assertSee('from-black/70', false)
            ->assertDontSee('linear-gradient(135deg', false);
    }

    public function test_hero_uses_dark_text_on_light_gradient(): void
    {
        $this->setSettings([
            'hero_bg_image' => null,
            'hero_bg_from' => '#ffffff',
            'hero_bg_to' => '#f1f5f9',
        ]);

        $content = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('text-gray-900 sm:text-5xl', $content);
        $this->assertStringNotContainsString('text-white sm:text-5xl', $content);
    }
}

<?php

namespace Tests\Feature;

use App\Filament\Resources\Slides\Pages\CreateSlide;
use App\Filament\Resources\Slides\Pages\ListSlides;
use App\Models\Slide;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SliderTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Buat slide beserta gambar (media) agar lolos filter imageUrl() di HomeController.
     */
    private function makeSlide(array $attributes = []): Slide
    {
        $slide = Slide::create(array_merge([
            'is_active' => true,
            'sort_order' => 0,
        ], $attributes));

        $slide->addMedia(UploadedFile::fake()->image('slide.jpg'))
            ->toMediaCollection('image');

        return $slide;
    }

    public function test_home_shows_active_slide_with_title(): void
    {
        Storage::fake('public');
        $this->makeSlide(['title' => 'Sorotan Kegiatan']);

        $this->get('/')
            ->assertOk()
            ->assertSee('data-slider', false)
            ->assertSee('Sorotan Kegiatan');
    }

    public function test_home_hides_inactive_slide(): void
    {
        Storage::fake('public');
        $this->makeSlide(['title' => 'Slide Tampil', 'is_active' => true]);
        $this->makeSlide(['title' => 'Slide Tersembunyi', 'is_active' => false]);

        $response = $this->get('/')->assertOk();
        $response->assertSee('Slide Tampil');
        $response->assertDontSee('Slide Tersembunyi');
    }

    public function test_home_orders_slides_by_sort_order(): void
    {
        Storage::fake('public');
        $this->makeSlide(['title' => 'Kedua', 'sort_order' => 2]);
        $this->makeSlide(['title' => 'Pertama', 'sort_order' => 1]);

        $content = $this->get('/')->assertOk()->getContent();

        $this->assertLessThan(
            strpos($content, 'Kedua'),
            strpos($content, 'Pertama'),
            'Slide dengan sort_order lebih kecil harus dirender lebih dulu.'
        );
    }

    public function test_home_omits_carousel_when_no_slides(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertDontSee('data-slider', false);
    }

    public function test_home_ignores_slide_without_image(): void
    {
        // Slide aktif tapi tanpa gambar tidak boleh merender carousel.
        Slide::create(['title' => 'Tanpa Gambar', 'is_active' => true, 'sort_order' => 0]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('data-slider', false)
            ->assertDontSee('Tanpa Gambar');
    }

    public function test_slide_resource_renders_and_can_create_with_image(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create());

        Livewire::test(ListSlides::class)->assertOk();

        Livewire::test(CreateSlide::class)
            ->fillForm([
                'title' => 'Banner Utama',
                'is_active' => true,
                'image' => [UploadedFile::fake()->image('banner.jpg')],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $slide = Slide::where('title', 'Banner Utama')->first();
        $this->assertNotNull($slide);
        $this->assertCount(1, $slide->getMedia('image'));
        // Gambar harus di disk 'public' agar bisa diakses web (bukan 'local'/private).
        $this->assertSame('public', $slide->getFirstMedia('image')->disk);
    }
}

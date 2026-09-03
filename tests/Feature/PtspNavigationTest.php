<?php

namespace Tests\Feature;

use App\Filament\Resources\GalleryItems\GalleryItemResource;
use App\Filament\Resources\LeaderQuotes\LeaderQuoteResource;
use App\Filament\Resources\PostCategories\PostCategoryResource;
use App\Filament\Resources\Posts\PostResource;
use App\Filament\Resources\Slides\SlideResource;
use App\Filament\Resources\Stats\StatResource;
use App\Models\Menu;
use Database\Seeders\PtspServiceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PtspNavigationTest extends TestCase
{
    use RefreshDatabase;

    public static function resourceTersembunyiProvider(): array
    {
        return [
            [SlideResource::class],
            [StatResource::class],
            [LeaderQuoteResource::class],
            [GalleryItemResource::class],
            [PostResource::class],
            [PostCategoryResource::class],
        ];
    }

    #[DataProvider('resourceTersembunyiProvider')]
    public function test_modul_cms_yang_tidak_dipakai_tidak_muncul_di_navigasi(string $resource): void
    {
        $this->assertFalse($resource::shouldRegisterNavigation());
    }

    public function test_beranda_menampilkan_layanan(): void
    {
        $this->seed(PtspServiceSeeder::class);

        $this->get('/')
            ->assertOk()
            ->assertSee('SPP Pengambilan Ijazah')
            ->assertSee('SPP Surat Pengganti Ijazah Rusak');
    }

    public function test_seeder_menambahkan_menu_layanan_dan_lacak(): void
    {
        $this->seed(PtspServiceSeeder::class);

        $this->assertTrue(Menu::where('target', '/layanan')->exists());
        $this->assertTrue(Menu::where('target', '/lacak')->exists());
    }
}

<?php

namespace Tests\Feature;

use App\Filament\Resources\LeaderQuotes\Pages\CreateLeaderQuote;
use App\Filament\Resources\LeaderQuotes\Pages\ListLeaderQuotes;
use App\Models\LeaderQuote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class LeaderQuoteTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Rendering section Pimpinan di beranda sudah dihapus (bukan bagian
     * PTSP Online, lihat GeneralSettings::HOME_SECTIONS) -- resource Filament
     * di bawah tetap diuji karena modelnya sengaja dipertahankan, bukan
     * dihapus (lihat spec §2.3 fork CMS-web).
     */
    public function test_resource_renders_and_can_create_with_photo_on_public_disk(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create());

        Livewire::test(ListLeaderQuotes::class)->assertOk();

        Livewire::test(CreateLeaderQuote::class)
            ->fillForm([
                'name' => 'Dr. Ahmad',
                'title' => 'Sambutan Direktur',
                'position' => 'Direktur',
                'quote' => 'Melayani dengan hati.',
                'background_style' => 'circles',
                'is_active' => true,
                'photo' => [UploadedFile::fake()->image('foto.png')],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $leader = LeaderQuote::where('name', 'Dr. Ahmad')->first();
        $this->assertNotNull($leader);
        $this->assertSame('Sambutan Direktur', $leader->title);
        $this->assertSame('circles', $leader->background_style);
        $this->assertCount(1, $leader->getMedia('photo'));
        $this->assertSame('public', $leader->getFirstMedia('photo')->disk);
    }
}

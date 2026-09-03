<?php

namespace Tests\Feature;

use App\Filament\Resources\LeaderQuotes\Pages\CreateLeaderQuote;
use App\Models\LeaderQuote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Rendering section Pendamping/Waka di beranda sudah dihapus (bukan bagian
 * PTSP Online, lihat GeneralSettings::HOME_SECTIONS) -- resource & helper
 * style LeaderQuote di bawah tetap diuji karena modelnya sengaja
 * dipertahankan, bukan dihapus (lihat spec §2.3 fork CMS-web).
 */
class PendampingTest extends TestCase
{
    use RefreshDatabase;

    public function test_background_style_has_seven_options(): void
    {
        $this->assertCount(7, LeaderQuote::backgroundStyleOptions());
    }

    public function test_framed_style_helpers(): void
    {
        $this->assertCount(5, LeaderQuote::framedStyleOptions());
        $this->assertCount(12, LeaderQuote::pendampingStyleOptions());
        $this->assertTrue(LeaderQuote::isFramedStyle('framed_card'));
        $this->assertFalse(LeaderQuote::isFramedStyle('arch'));
        $this->assertFalse(LeaderQuote::isFramedStyle(null));
    }

    public function test_resource_can_create_pendamping_with_framed_style(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateLeaderQuote::class)
            ->fillForm([
                'placement' => LeaderQuote::PLACEMENT_PENDAMPING,
                'name' => 'Dedi Haryanto',
                'position' => 'Waka Keasramaan',
                'background_style' => 'framed_card',
                'is_active' => true,
                'photo' => [UploadedFile::fake()->image('waka.jpg')],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $person = LeaderQuote::where('name', 'Dedi Haryanto')->first();
        $this->assertNotNull($person);
        $this->assertSame('framed_card', $person->background_style);
    }

    public function test_resource_can_create_pendamping_with_jpg_and_without_quote(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateLeaderQuote::class)
            ->fillForm([
                'placement' => LeaderQuote::PLACEMENT_PENDAMPING,
                'name' => 'Chairul Wahyudi',
                'position' => 'Waka Kesiswaan',
                'is_active' => true,
                'photo' => [UploadedFile::fake()->image('waka.jpg')],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $person = LeaderQuote::where('name', 'Chairul Wahyudi')->first();
        $this->assertNotNull($person);
        $this->assertSame(LeaderQuote::PLACEMENT_PENDAMPING, $person->placement);
        $this->assertSame('Waka Kesiswaan', $person->position);
        $this->assertNull($person->quote);
        $this->assertCount(1, $person->getMedia('photo'));
    }
}

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

class PendampingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Buat pendamping (di bawah pimpinan) beserta foto agar lolos filter photoUrl().
     */
    private function makePendamping(array $attributes = [], string $file = 'waka.png'): LeaderQuote
    {
        $person = LeaderQuote::create(array_merge([
            'placement' => LeaderQuote::PLACEMENT_PENDAMPING,
            'name' => 'Waka Satu',
            'is_active' => true,
            'sort_order' => 0,
        ], $attributes));

        $person->addMedia(UploadedFile::fake()->image($file))
            ->toMediaCollection('photo');

        return $person;
    }

    public function test_home_shows_pendamping_with_name_and_position(): void
    {
        Storage::fake('public');
        $this->makePendamping([
            'name' => 'Melia Fitri Yani',
            'position' => 'Waka Akademik',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Melia Fitri Yani')
            ->assertSee('Waka Akademik');
    }

    public function test_home_hides_inactive_pendamping(): void
    {
        Storage::fake('public');
        $this->makePendamping(['name' => 'Waka Tampil', 'is_active' => true]);
        $this->makePendamping(['name' => 'Waka Sembunyi', 'is_active' => false]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Waka Tampil')
            ->assertDontSee('Waka Sembunyi');
    }

    public function test_home_ignores_pendamping_without_photo(): void
    {
        LeaderQuote::create([
            'placement' => LeaderQuote::PLACEMENT_PENDAMPING,
            'name' => 'Tanpa Foto',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('Tanpa Foto');
    }

    public function test_pimpinan_still_renders_as_big_section_not_row(): void
    {
        Storage::fake('public');
        $leader = LeaderQuote::create([
            'placement' => LeaderQuote::PLACEMENT_PIMPINAN,
            'name' => 'Kepala Sekolah Utama',
            'quote' => 'Pendidikan adalah kunci.',
            'is_active' => true,
            'sort_order' => 0,
        ]);
        $leader->addMedia(UploadedFile::fake()->image('kepala.png'))->toMediaCollection('photo');

        // Pimpinan tidak boleh tersaring ke barisan pendamping, tetap tampil sebagai sambutan.
        $this->get('/')
            ->assertOk()
            ->assertSee('Kepala Sekolah Utama')
            ->assertSee('Pendidikan adalah kunci.');
    }

    public function test_pendamping_arranged_center_out(): void
    {
        Storage::fake('public');
        // Urutan input 1..5 → tampilan center-out kiri-ke-kanan: E, C, A, B, D.
        $this->makePendamping(['name' => 'AAA', 'sort_order' => 1]);
        $this->makePendamping(['name' => 'BBB', 'sort_order' => 2]);
        $this->makePendamping(['name' => 'CCC', 'sort_order' => 3]);
        $this->makePendamping(['name' => 'DDD', 'sort_order' => 4]);
        $this->makePendamping(['name' => 'EEE', 'sort_order' => 5]);

        $html = $this->get('/')->assertOk()->getContent();

        $order = collect(['AAA', 'BBB', 'CCC', 'DDD', 'EEE'])
            ->mapWithKeys(fn (string $name): array => [$name => strpos($html, '>'.$name.'<')])
            ->sort();

        $this->assertSame(['EEE', 'CCC', 'AAA', 'BBB', 'DDD'], $order->keys()->all());
    }

    public function test_home_omits_row_when_no_pendamping(): void
    {
        // 'data-reveal' saja terlalu umum — section lain (hero, zona integritas)
        // juga memakainya, jadi diperiksa lewat marker khusus section pendamping.
        $this->get('/')
            ->assertOk()
            ->assertDontSee('data-section="pendamping"', false);
    }

    public function test_background_style_has_seven_options(): void
    {
        $this->assertCount(7, LeaderQuote::backgroundStyleOptions());
    }

    public function test_home_renders_pendamping_for_every_background_style(): void
    {
        Storage::fake('public');

        foreach (array_keys(LeaderQuote::backgroundStyleOptions()) as $i => $style) {
            $this->makePendamping([
                'name' => 'Waka '.$style,
                'background_style' => $style,
                'sort_order' => $i,
            ]);
        }

        $response = $this->get('/')->assertOk();

        foreach (array_keys(LeaderQuote::backgroundStyleOptions()) as $style) {
            $response->assertSee('Waka '.$style);
        }
    }

    public function test_framed_style_helpers(): void
    {
        $this->assertCount(5, LeaderQuote::framedStyleOptions());
        $this->assertCount(12, LeaderQuote::pendampingStyleOptions());
        $this->assertTrue(LeaderQuote::isFramedStyle('framed_card'));
        $this->assertFalse(LeaderQuote::isFramedStyle('arch'));
        $this->assertFalse(LeaderQuote::isFramedStyle(null));
    }

    public function test_home_renders_pendamping_for_every_framed_style(): void
    {
        Storage::fake('public');

        foreach (array_keys(LeaderQuote::framedStyleOptions()) as $i => $style) {
            $this->makePendamping([
                'name' => 'Waka '.$style,
                'background_style' => $style,
                'sort_order' => $i,
            ]);
        }

        $response = $this->get('/')->assertOk();

        foreach (array_keys(LeaderQuote::framedStyleOptions()) as $style) {
            $response->assertSee('Waka '.$style);
        }
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

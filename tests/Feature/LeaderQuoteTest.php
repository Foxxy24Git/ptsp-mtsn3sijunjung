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
     * Buat pimpinan beserta foto agar lolos filter photoUrl() di HomeController.
     */
    private function makeLeader(array $attributes = []): LeaderQuote
    {
        $leader = LeaderQuote::create(array_merge([
            'name' => 'Budi Santoso',
            'is_active' => true,
            'sort_order' => 0,
        ], $attributes));

        $leader->addMedia(UploadedFile::fake()->image('leader.png'))
            ->toMediaCollection('photo');

        return $leader;
    }

    public function test_home_shows_active_leader_with_quote(): void
    {
        Storage::fake('public');
        $this->makeLeader([
            'name' => 'Budi Santoso',
            'position' => 'Kepala Sekolah',
            'quote' => 'Pendidikan adalah kunci masa depan.',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('data-reveal', false)
            ->assertSee('Budi Santoso')
            ->assertSee('Kepala Sekolah')
            ->assertSee('Pendidikan adalah kunci masa depan.');
    }

    public function test_home_shows_leader_without_quote(): void
    {
        Storage::fake('public');
        $this->makeLeader(['name' => 'Siti Aminah', 'quote' => null]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Siti Aminah');
    }

    public function test_home_hides_inactive_leader(): void
    {
        Storage::fake('public');
        $this->makeLeader(['name' => 'Pimpinan Tampil', 'is_active' => true]);
        $this->makeLeader(['name' => 'Pimpinan Tersembunyi', 'is_active' => false]);

        $response = $this->get('/')->assertOk();
        $response->assertSee('Pimpinan Tampil');
        $response->assertDontSee('Pimpinan Tersembunyi');
    }

    public function test_home_shows_only_first_leader(): void
    {
        Storage::fake('public');
        $this->makeLeader(['name' => 'Pertama', 'sort_order' => 1]);
        $this->makeLeader(['name' => 'Kedua', 'sort_order' => 2]);

        // Hanya satu pimpinan (urutan terkecil) yang ditampilkan.
        $this->get('/')
            ->assertOk()
            ->assertSee('Pertama')
            ->assertDontSee('Kedua');
    }

    public function test_title_shown_big_with_name_as_eyebrow(): void
    {
        Storage::fake('public');
        $this->makeLeader([
            'name' => 'Dr. H. Samsudin, M.Pd.',
            'title' => 'Sambutan Kepala Madrasah',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Dr. H. Samsudin, M.Pd.')
            ->assertSee('Sambutan Kepala Madrasah');
    }

    public function test_home_omits_section_when_no_leaders(): void
    {
        // 'data-reveal' saja terlalu umum — section lain (hero, zona integritas)
        // juga memakainya, jadi diperiksa lewat marker khusus section pimpinan.
        $this->get('/')
            ->assertOk()
            ->assertDontSee('data-section="leader"', false);
    }

    public function test_home_ignores_leader_without_photo(): void
    {
        LeaderQuote::create(['name' => 'Tanpa Foto', 'is_active' => true, 'sort_order' => 0]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('Tanpa Foto');
    }

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

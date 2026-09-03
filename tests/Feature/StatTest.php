<?php

namespace Tests\Feature;

use App\Filament\Resources\Stats\Pages\CreateStat;
use App\Filament\Resources\Stats\Pages\ListStats;
use App\Models\Stat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StatTest extends TestCase
{
    use RefreshDatabase;

    private function makeStat(array $attributes = []): Stat
    {
        return Stat::create(array_merge([
            'icon' => 'heroicon-o-users',
            'label' => 'Mahasiswa Aktif',
            'value' => 15307,
            'color' => '#7f1d1d',
            'is_active' => true,
            'sort_order' => 0,
        ], $attributes));
    }

    public function test_home_shows_active_stat(): void
    {
        $this->makeStat(['label' => 'Mahasiswa Aktif', 'value' => 15307]);

        $this->get('/')
            ->assertOk()
            ->assertSee('data-countup', false)
            ->assertSee('Mahasiswa Aktif')
            ->assertSee('15307', false);
    }

    public function test_home_hides_inactive_stat(): void
    {
        $this->makeStat(['label' => 'Statistik Tampil', 'is_active' => true]);
        $this->makeStat(['label' => 'Statistik Tersembunyi', 'is_active' => false]);

        $response = $this->get('/')->assertOk();
        $response->assertSee('Statistik Tampil');
        $response->assertDontSee('Statistik Tersembunyi');
    }

    public function test_home_orders_stats_by_sort_order(): void
    {
        $this->makeStat(['label' => 'Kedua', 'sort_order' => 2]);
        $this->makeStat(['label' => 'Pertama', 'sort_order' => 1]);

        $content = $this->get('/')->assertOk()->getContent();

        $this->assertLessThan(
            strpos($content, 'Kedua'),
            strpos($content, 'Pertama'),
            'Statistik dengan sort_order lebih kecil harus dirender lebih dulu.'
        );
    }

    public function test_home_omits_stats_section_when_empty(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertDontSee('data-countup', false);
    }

    public function test_stat_uses_per_item_color_variable(): void
    {
        $this->makeStat(['label' => 'Berwarna', 'color' => '#123456']);

        $this->get('/')
            ->assertOk()
            ->assertSee('--stat-color: #123456', false);
    }

    public function test_stat_resource_renders_and_can_create(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ListStats::class)->assertOk();

        Livewire::test(CreateStat::class)
            ->fillForm([
                'icon' => 'heroicon-o-newspaper',
                'label' => 'Jurnal Terakreditasi',
                'value' => '20',
                'suffix' => '+',
                'color' => '#7f1d1d',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $stat = Stat::where('label', 'Jurnal Terakreditasi')->first();
        $this->assertNotNull($stat);
        $this->assertSame('heroicon-o-newspaper', $stat->icon);
        $this->assertSame('20', $stat->value);
        $this->assertSame('+', $stat->suffix);
    }

    public function test_numeric_value_gets_countup_but_text_value_does_not(): void
    {
        $this->makeStat(['label' => 'Angka', 'value' => '500', 'sort_order' => 1]);
        $this->makeStat(['label' => 'Teks', 'value' => 'A', 'sort_order' => 2]);

        $content = $this->get('/')->assertOk()->getContent();

        // Statistik angka memicu count-up; teks tampil apa adanya tanpa count-up.
        $this->assertStringContainsString('data-countup="500"', $content);
        $this->assertStringNotContainsString('data-countup="A"', $content);
        $this->assertStringContainsString('>A</span>', $content);
    }
}

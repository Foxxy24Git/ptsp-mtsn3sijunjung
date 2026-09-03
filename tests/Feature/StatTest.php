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

    /**
     * Rendering section Statistik di beranda sudah dihapus (bukan bagian
     * PTSP Online, lihat GeneralSettings::HOME_SECTIONS) -- resource Filament
     * di bawah tetap diuji karena modelnya sengaja dipertahankan, bukan
     * dihapus (lihat spec §2.3 fork CMS-web).
     */
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
}

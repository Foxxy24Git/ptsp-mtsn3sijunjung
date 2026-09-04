<?php

namespace Tests\Feature;

use App\Filament\Resources\Menus\MenuResource;
use App\Filament\Resources\Menus\Pages\CreateMenu;
use App\Filament\Resources\Pages\PageResource;
use App\Filament\Resources\SatisfactionSurveys\Pages\CreateSatisfactionSurvey;
use App\Filament\Resources\SatisfactionSurveys\SatisfactionSurveyResource;
use App\Models\Menu;
use App\Models\SatisfactionSurvey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KepuasanResourceTest extends TestCase
{
    use RefreshDatabase;

    private function survei(): SatisfactionSurvey
    {
        $survei = SatisfactionSurvey::create([
            'title' => 'Kepuasan Layanan PTSP',
            'slug' => 'kepuasan-layanan-ptsp',
            'status' => 'published',
        ]);

        $survei->aspects()->create(['label' => 'Kecepatan Layanan', 'sort_order' => 0]);

        return $survei->refresh();
    }

    public function test_menu_kepuasan_layanan_berada_tepat_di_bawah_pages(): void
    {
        $this->assertSame(20, PageResource::getNavigationSort());
        $this->assertSame(30, SatisfactionSurveyResource::getNavigationSort());
        $this->assertSame(10, MenuResource::getNavigationSort());
        $this->assertTrue(SatisfactionSurveyResource::shouldRegisterNavigation());
    }

    public function test_daftar_survei_bisa_dibuka_admin(): void
    {
        $this->survei();

        $this->actingAs(User::factory()->create())
            ->get('/admin/satisfaction-surveys')
            ->assertOk()
            ->assertSee('Kepuasan Layanan PTSP');
    }

    public function test_tamu_tidak_bisa_membuka_daftar_survei(): void
    {
        $this->get('/admin/satisfaction-surveys')->assertRedirect();
    }

    public function test_admin_bisa_membuat_survei_beserta_sub_kepuasan_layanan(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateSatisfactionSurvey::class)
            ->fillForm([
                'title' => 'Kepuasan Layanan PTSP',
                'slug' => 'kepuasan-layanan-ptsp',
                'status' => 'published',
                'collect_suggestion' => true,
                'aspects' => [
                    ['label' => 'Kecepatan Layanan', 'help_text' => 'Waktu tunggu di loket.'],
                    ['label' => 'Keramahan Petugas', 'help_text' => null],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $survei = SatisfactionSurvey::firstWhere('slug', 'kepuasan-layanan-ptsp');

        $this->assertNotNull($survei);
        $this->assertSame(
            ['Kecepatan Layanan', 'Keramahan Petugas'],
            $survei->aspects->pluck('label')->all(),
        );
    }

    public function test_survei_tanpa_sub_ditolak_saat_disimpan(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateSatisfactionSurvey::class)
            ->fillForm([
                'title' => 'Tanpa Sub',
                'slug' => 'tanpa-sub',
                'status' => 'published',
                'aspects' => [],
            ])
            ->call('create')
            ->assertHasFormErrors(['aspects']);

        $this->assertDatabaseCount('satisfaction_surveys', 0);
    }

    public function test_halaman_hasil_menampilkan_rekap_dan_jawaban(): void
    {
        $survei = $this->survei();
        $aspek = $survei->aspects->first();

        $this->post('/kepuasan/'.$survei->slug, [
            'aspek' => [$aspek->id => 5],
            'saran' => 'Pelayanan sudah cepat.',
        ])->assertSessionHasNoErrors();

        $this->actingAs(User::factory()->create())
            ->get('/admin/satisfaction-surveys/'.$survei->slug.'/hasil')
            ->assertOk()
            ->assertSee('Rata-rata per Sub Kepuasan Layanan')
            ->assertSee('Kecepatan Layanan')
            ->assertSee('Sangat Puas')
            ->assertSee('Pelayanan sudah cepat.');
    }

    public function test_menu_kepuasan_menyimpan_slug_survei_yang_dipilih(): void
    {
        $survei = $this->survei();
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateMenu::class)
            ->fillForm([
                'label' => 'Kepuasan Layanan',
                'type' => 'kepuasan',
                'target' => $survei->slug,
                'sort_order' => 5,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $menu = Menu::firstWhere('label', 'Kepuasan Layanan');

        $this->assertNotNull($menu);
        $this->assertSame('kepuasan', $menu->type);
        $this->assertSame($survei->slug, $menu->target);
    }

    public function test_menu_tipe_lain_masih_menyimpan_target_yang_diketik(): void
    {
        // Kolom "target" dirender dua kali (Select untuk tipe kepuasan,
        // TextInput untuk tipe lain). Uji ini menjaga agar komponen yang
        // sedang tersembunyi tidak menghapus nilai milik komponen kembarannya.
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateMenu::class)
            ->fillForm([
                'label' => 'Profil Sekolah',
                'type' => 'page',
                'target' => 'profil-sekolah',
                'sort_order' => 1,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame('profil-sekolah', Menu::firstWhere('label', 'Profil Sekolah')?->target);
    }

    public function test_halaman_hasil_tanpa_jawaban_tetap_terbuka(): void
    {
        $survei = $this->survei();

        $this->actingAs(User::factory()->create())
            ->get('/admin/satisfaction-surveys/'.$survei->slug.'/hasil')
            ->assertOk()
            ->assertSee('Belum ada penilaian yang masuk untuk survei ini.');
    }
}

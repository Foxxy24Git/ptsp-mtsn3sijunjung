<?php

namespace Tests\Feature;

use App\Filament\Pages\ManageGeneralSettings;
use App\Models\User;
use App\Settings\GeneralSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ManageHomeLayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_settings_page_renders(): void
    {
        Livewire::test(ManageGeneralSettings::class)->assertOk();
    }

    public function test_form_prefills_all_master_sections(): void
    {
        // Simpan data tak lengkap (cuma 'layanan'); form harus menampilkan
        // seluruh section master ternormalisasi ('hero' ikut ditambahkan).
        $settings = app(GeneralSettings::class);
        $settings->home_sections = [['key' => 'layanan', 'visible' => true]];
        $settings->save();

        $component = Livewire::test(ManageGeneralSettings::class)->assertOk();

        // assertFormSet dengan closure di versi Filament ini tidak menegaskan
        // apa pun bila closure mengembalikan boolean (hanya array yang diperiksa),
        // jadi state form diperiksa langsung agar assersi benar-benar berarti.
        $state = $component->instance()->form->getRawState();

        $this->assertCount(count(GeneralSettings::HOME_SECTIONS), $state['home_sections']);
        // Repeater menyimpan tiap item dengan key UUID acak, jadi bandingkan
        // array_values() saja. normalizeSections mempertahankan urutan
        // tersimpan ('layanan' duluan), lalu menambahkan key master di belakang.
        $this->assertSame(
            GeneralSettings::normalizeSections([['key' => 'layanan', 'visible' => true]]),
            array_values($state['home_sections'])
        );
    }

    public function test_saving_persists_order_and_visibility(): void
    {
        Livewire::test(ManageGeneralSettings::class)
            ->fillForm([
                'home_sections' => [
                    ['key' => 'layanan', 'visible' => true],
                    ['key' => 'hero', 'visible' => false],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $saved = app(GeneralSettings::class)->home_sections;
        $this->assertSame('layanan', $saved[0]['key']);
        $this->assertFalse($saved[1]['visible']); // hero disembunyikan
        $this->assertSame('hero', $saved[1]['key']);
    }
}

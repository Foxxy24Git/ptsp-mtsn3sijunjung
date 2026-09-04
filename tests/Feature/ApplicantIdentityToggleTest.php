<?php

namespace Tests\Feature;

use App\Filament\Resources\Forms\Pages\CreateForm;
use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Fitur toggle "Wajib Isi Identitas & Kontak Pemohon": tidak semua layanan
 * perlu meminta Nama/WhatsApp/Email pemohon, jadi admin bisa mematikannya
 * per layanan lewat tab Pengaturan.
 */
class ApplicantIdentityToggleTest extends TestCase
{
    use RefreshDatabase;

    private function layanan(array $overrides = []): Form
    {
        return Form::create(array_merge([
            'title' => 'Pengumuman Kelulusan',
            'slug' => 'pengumuman-kelulusan',
            'status' => 'published',
        ], $overrides));
    }

    public function test_bagian_identitas_tampil_dan_wajib_diisi_secara_default(): void
    {
        $this->layanan();

        $this->get('/layanan/pengumuman-kelulusan/ajukan')
            ->assertOk()
            ->assertSee('Identitas & Kontak Pemohon')
            ->assertSee('name="applicant_whatsapp"', false);

        $this->post('/layanan/pengumuman-kelulusan/ajukan', [])
            ->assertSessionHasErrors(['applicant_name', 'applicant_whatsapp', 'applicant_email']);
    }

    public function test_bagian_identitas_disembunyikan_saat_toggle_dimatikan(): void
    {
        $this->layanan(['requires_applicant_identity' => false]);

        $this->get('/layanan/pengumuman-kelulusan/ajukan')
            ->assertOk()
            ->assertDontSee('Identitas & Kontak Pemohon')
            ->assertDontSee('name="applicant_whatsapp"', false);
    }

    public function test_pengajuan_berhasil_tanpa_identitas_saat_toggle_dimatikan(): void
    {
        $this->layanan(['requires_applicant_identity' => false]);

        $response = $this->post('/layanan/pengumuman-kelulusan/ajukan', []);

        $response->assertRedirect(route('permohonan.selesai'));

        $permohonan = FormSubmission::first();
        $this->assertNotNull($permohonan);
        $this->assertNull($permohonan->applicant_name);
        $this->assertNull($permohonan->applicant_whatsapp);
        $this->assertNull($permohonan->applicant_email);
    }

    public function test_admin_bisa_mematikan_toggle_lewat_form_layanan(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateForm::class)
            ->fillForm([
                'title' => 'Pengumuman Kelulusan',
                'slug' => 'pengumuman-kelulusan',
                'status' => 'published',
                'requires_applicant_identity' => false,
                'fields' => [],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $form = Form::where('slug', 'pengumuman-kelulusan')->first();
        $this->assertNotNull($form);
        $this->assertFalse($form->requires_applicant_identity);
    }
}

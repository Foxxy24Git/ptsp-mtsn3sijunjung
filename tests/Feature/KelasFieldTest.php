<?php

namespace Tests\Feature;

use App\Filament\Resources\Forms\Pages\CreateForm;
use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\Kelas;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Fitur field bertipe 'kelas': pilihannya datang langsung dari menu Data
 * Kelas (bukan diketik manual per formulir seperti tipe 'select'), supaya
 * daftar kelas konsisten di semua layanan & cukup dikelola sekali.
 */
class KelasFieldTest extends TestCase
{
    use RefreshDatabase;

    private function layanan(): Form
    {
        return Form::create([
            'title' => 'Pendaftaran Siswa',
            'slug' => 'pendaftaran-siswa',
            'status' => 'published',
        ]);
    }

    public function test_admin_bisa_membuat_layanan_dengan_field_tipe_kelas(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateForm::class)
            ->fillForm([
                'title' => 'Pendaftaran Siswa Baru',
                'slug' => 'pendaftaran-siswa-baru',
                'status' => 'published',
                'fields' => [
                    ['label' => 'Kelas Dituju', 'type' => 'kelas', 'required' => true],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $field = Form::where('slug', 'pendaftaran-siswa-baru')->first()->fields()->first();
        $this->assertSame('kelas', $field->type);
    }

    public function test_halaman_ajukan_menampilkan_pilihan_dari_data_kelas(): void
    {
        Kelas::create(['name' => 'X', 'sort_order' => 1]);
        Kelas::create(['name' => 'XI', 'sort_order' => 2]);
        $layanan = $this->layanan();
        $layanan->fields()->create(['label' => 'Kelas', 'type' => 'kelas', 'required' => true, 'sort_order' => 1]);

        $this->get('/layanan/pendaftaran-siswa/ajukan')
            ->assertOk()
            ->assertSeeInOrder(['<option value="X"', '<option value="XI"'], false);
    }

    public function test_pengajuan_berhasil_dengan_nilai_kelas_yang_valid(): void
    {
        Kelas::create(['name' => 'XII IPA 2']);
        $layanan = $this->layanan();
        $field = $layanan->fields()->create(['label' => 'Kelas', 'type' => 'kelas', 'required' => true, 'sort_order' => 1]);

        $response = $this->post('/layanan/pendaftaran-siswa/ajukan', [
            'applicant_name' => 'Budi Santoso',
            'applicant_whatsapp' => '081234567890',
            'applicant_email' => 'budi@example.com',
            'field_'.$field->id => 'XII IPA 2',
        ]);

        $response->assertRedirect(route('permohonan.selesai'));
        $this->assertSame('XII IPA 2', FormSubmission::first()->data[$field->id]);
    }

    public function test_pengajuan_ditolak_bila_nilai_kelas_tidak_ada_di_data_kelas(): void
    {
        Kelas::create(['name' => 'XII IPA 2']);
        $layanan = $this->layanan();
        $field = $layanan->fields()->create(['label' => 'Kelas', 'type' => 'kelas', 'required' => true, 'sort_order' => 1]);

        $response = $this->post('/layanan/pendaftaran-siswa/ajukan', [
            'applicant_name' => 'Budi Santoso',
            'applicant_whatsapp' => '081234567890',
            'applicant_email' => 'budi@example.com',
            'field_'.$field->id => 'Kelas Tidak Terdaftar',
        ]);

        $response->assertSessionHasErrors(['field_'.$field->id]);
        $this->assertSame(0, FormSubmission::count());
    }
}

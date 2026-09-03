<?php
// tests/Feature/FormSubmitTest.php
namespace Tests\Feature;

use App\Models\Form;
use App\Models\FormSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FormSubmitTest extends TestCase
{
    use RefreshDatabase;

    private function makeForm(): Form
    {
        $form = Form::create([
            'title' => 'Pendaftaran', 'slug' => 'pendaftaran',
            'success_message' => 'Terima kasih sudah mendaftar!', 'status' => 'published',
        ]);
        $form->fields()->create(['label' => 'Nama', 'type' => 'text', 'required' => true, 'sort_order' => 1]);
        $form->fields()->create(['label' => 'Kelas', 'type' => 'select', 'options' => ['X', 'XI'], 'required' => true, 'sort_order' => 2]);
        $form->fields()->create(['label' => 'Ekskul', 'type' => 'checkbox', 'options' => ['Basket', 'Musik'], 'sort_order' => 3]);

        return $form;
    }

    /** Peta label → id field (id sebenarnya, jangan di-hardcode). */
    private function fieldIds(Form $form): \Illuminate\Support\Collection
    {
        return $form->fields()->pluck('id', 'label');
    }

    public function test_valid_submission_is_stored_and_shows_success(): void
    {
        $form = $this->makeForm();
        $id = $this->fieldIds($form);

        $this->post('/form/pendaftaran', [
            'field_'.$id['Nama'] => 'Budi',
            'field_'.$id['Kelas'] => 'X',
            'field_'.$id['Ekskul'] => ['Basket', 'Musik'],
        ])
            ->assertRedirect('/form/pendaftaran')
            ->assertSessionHas('form_success', 'Terima kasih sudah mendaftar!');

        $this->assertDatabaseCount('form_submissions', 1);
        $data = FormSubmission::first()->data;
        $this->assertSame('Budi', $data[$id['Nama']]);
        $this->assertSame('X', $data[$id['Kelas']]);
        $this->assertSame(['Basket', 'Musik'], $data[$id['Ekskul']]);
    }

    public function test_required_field_missing_fails_validation(): void
    {
        $form = $this->makeForm();
        $id = $this->fieldIds($form);

        $this->post('/form/pendaftaran', ['field_'.$id['Kelas'] => 'X'])
            ->assertSessionHasErrors('field_'.$id['Nama']);

        $this->assertDatabaseCount('form_submissions', 0);
    }

    public function test_select_value_outside_options_is_rejected(): void
    {
        $form = $this->makeForm();
        $id = $this->fieldIds($form);

        $this->post('/form/pendaftaran', ['field_'.$id['Nama'] => 'Budi', 'field_'.$id['Kelas'] => 'XII'])
            ->assertSessionHasErrors('field_'.$id['Kelas']);

        $this->assertDatabaseCount('form_submissions', 0);
    }

    public function test_file_upload_is_stored_and_path_recorded(): void
    {
        Storage::fake('public');
        $form = Form::create(['title' => 'Berkas', 'slug' => 'berkas', 'status' => 'published']);
        $field = $form->fields()->create(['label' => 'Foto', 'type' => 'file', 'required' => true, 'sort_order' => 1]);

        $this->post('/form/berkas', [
            'field_'.$field->id => UploadedFile::fake()->image('foto.jpg'),
        ])->assertRedirect('/form/berkas');

        $path = FormSubmission::first()->data[$field->id];
        $this->assertStringStartsWith('forms/', $path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_draft_form_cannot_be_submitted(): void
    {
        Form::create(['title' => 'Draft', 'slug' => 'draft-form', 'status' => 'draft']);

        $this->post('/form/draft-form', [])->assertNotFound();
    }

    private function makeUniqueForm(): Form
    {
        $form = Form::create(['title' => 'Daftar MAN', 'slug' => 'daftar-man', 'status' => 'published']);
        // Email wajib unik; Umur boleh duplikat.
        $form->fields()->create(['label' => 'Email', 'type' => 'text', 'required' => true, 'is_unique' => true, 'sort_order' => 1]);
        $form->fields()->create(['label' => 'Umur', 'type' => 'text', 'is_unique' => false, 'sort_order' => 2]);

        return $form;
    }

    public function test_unique_field_rejects_duplicate_value(): void
    {
        $form = $this->makeUniqueForm();
        $id = $this->fieldIds($form);

        // Pengisian pertama sukses.
        $this->post('/form/daftar-man', ['field_'.$id['Email'] => 'budi@mail.com', 'field_'.$id['Umur'] => '17'])
            ->assertRedirect('/form/daftar-man');

        // Pengisian kedua dengan Email yang sama ditolak.
        $this->post('/form/daftar-man', ['field_'.$id['Email'] => 'budi@mail.com', 'field_'.$id['Umur'] => '18'])
            ->assertSessionHasErrors('field_'.$id['Email']);

        $this->assertDatabaseCount('form_submissions', 1);
    }

    public function test_non_unique_field_allows_duplicate_value(): void
    {
        $form = $this->makeUniqueForm();
        $id = $this->fieldIds($form);

        // Dua siswa berbeda (Email beda) tapi Umur sama → boleh.
        $this->post('/form/daftar-man', ['field_'.$id['Email'] => 'a@mail.com', 'field_'.$id['Umur'] => '17'])
            ->assertRedirect('/form/daftar-man');
        $this->post('/form/daftar-man', ['field_'.$id['Email'] => 'b@mail.com', 'field_'.$id['Umur'] => '17'])
            ->assertRedirect('/form/daftar-man');

        $this->assertDatabaseCount('form_submissions', 2);
    }
}

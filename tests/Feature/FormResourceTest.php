<?php

namespace Tests\Feature;

use App\Filament\Resources\Forms\Pages\CreateForm;
use App\Filament\Resources\Forms\Pages\ListForms;
use App\Models\Form;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class FormResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_form_list_renders(): void
    {
        Livewire::test(ListForms::class)->assertOk();
    }

    public function test_can_create_form_with_fields(): void
    {
        Livewire::test(CreateForm::class)
            ->fillForm([
                'title' => 'Pendaftaran Siswa',
                'slug' => 'pendaftaran-siswa',
                'status' => 'published',
                'fields' => [
                    ['label' => 'Nama', 'type' => 'text', 'required' => true],
                    ['label' => 'Kelas', 'type' => 'select', 'options' => ['X', 'XI'], 'required' => true],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $form = Form::where('slug', 'pendaftaran-siswa')->first();
        $this->assertNotNull($form);
        $this->assertSame(2, $form->fields()->count());
        $this->assertSame('select', $form->fields()->where('label', 'Kelas')->first()->type);
    }

    public function test_can_create_document_field_with_uploaded_file(): void
    {
        Storage::fake('public');

        Livewire::test(CreateForm::class)
            ->fillForm([
                'title' => 'Keluar Masuk Siswa',
                'slug' => 'keluar-masuk-siswa',
                'status' => 'published',
                'fields' => [
                    [
                        'label' => 'Blanko Surat Keluar Masuk',
                        'type' => 'document',
                        'document_path' => [UploadedFile::fake()->create('blanko.pdf', 100, 'application/pdf')],
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $field = Form::where('slug', 'keluar-masuk-siswa')->first()->fields()->first();

        $this->assertSame('document', $field->type);
        $this->assertNotNull($field->document_path);
        Storage::disk('public')->assertExists($field->document_path);
    }

    public function test_document_field_requires_a_file_upload(): void
    {
        Livewire::test(CreateForm::class)
            ->fillForm([
                'title' => 'Tanpa Berkas',
                'slug' => 'tanpa-berkas',
                'status' => 'draft',
                'fields' => [
                    ['label' => 'Blanko', 'type' => 'document'],
                ],
            ])
            ->call('create')
            ->assertHasFormErrors(['fields.0.document_path']);
    }

    public function test_form_slug_must_be_unique(): void
    {
        Form::create(['title' => 'Ada', 'slug' => 'ada', 'status' => 'draft']);

        Livewire::test(CreateForm::class)
            ->fillForm(['title' => 'Ada Lagi', 'slug' => 'ada', 'status' => 'draft'])
            ->call('create')
            ->assertHasFormErrors(['slug']);
    }
}

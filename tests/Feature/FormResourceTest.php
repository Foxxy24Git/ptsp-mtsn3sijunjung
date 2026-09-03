<?php

namespace Tests\Feature;

use App\Filament\Resources\Forms\Pages\CreateForm;
use App\Filament\Resources\Forms\Pages\ListForms;
use App\Models\Form;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_form_slug_must_be_unique(): void
    {
        Form::create(['title' => 'Ada', 'slug' => 'ada', 'status' => 'draft']);

        Livewire::test(CreateForm::class)
            ->fillForm(['title' => 'Ada Lagi', 'slug' => 'ada', 'status' => 'draft'])
            ->call('create')
            ->assertHasFormErrors(['slug']);
    }
}

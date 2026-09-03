<?php
// tests/Feature/FormSubmissionsRelationTest.php
namespace Tests\Feature;

use App\Filament\Resources\Forms\Pages\EditForm;
use App\Filament\Resources\Forms\RelationManagers\SubmissionsRelationManager;
use App\Models\Form;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FormSubmissionsRelationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_relation_manager_lists_submissions(): void
    {
        $form = Form::create(['title' => 'Daftar', 'slug' => 'daftar', 'status' => 'published']);
        $field = $form->fields()->create(['label' => 'Nama', 'type' => 'text', 'sort_order' => 1]);
        $sub = $form->submissions()->create(['data' => [$field->id => 'Budi']]);

        Livewire::test(SubmissionsRelationManager::class, [
            'ownerRecord' => $form,
            'pageClass' => EditForm::class,
        ])
            ->assertOk()
            ->assertCanSeeTableRecords([$sub]);
    }
}

<?php
// tests/Feature/FormModelTest.php
namespace Tests\Feature;

use App\Models\Form;
use App\Models\FormField;
use App\Models\FormSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_form_has_ordered_fields_and_submissions(): void
    {
        $form = Form::create([
            'title' => 'Pendaftaran Siswa',
            'slug' => 'pendaftaran-siswa',
            'status' => 'published',
        ]);

        $form->fields()->create(['label' => 'B', 'type' => 'text', 'required' => true, 'sort_order' => 2]);
        $form->fields()->create(['label' => 'A', 'type' => 'text', 'required' => false, 'sort_order' => 1]);

        $sub = $form->submissions()->create(['data' => ['1' => 'Budi']]);

        $this->assertSame('pendaftaran-siswa', $form->getRouteKey());
        $this->assertSame(['A', 'B'], $form->fields()->pluck('label')->all());
        $this->assertTrue($form->fields->first()->required === false);
        $this->assertSame(['1' => 'Budi'], $sub->fresh()->data);
    }

    public function test_deleting_form_cascades_to_fields_and_submissions(): void
    {
        $form = Form::create(['title' => 'X', 'slug' => 'x', 'status' => 'draft']);
        $form->fields()->create(['label' => 'F', 'type' => 'text', 'sort_order' => 0]);
        $form->submissions()->create(['data' => []]);

        $form->delete();

        $this->assertDatabaseCount('form_fields', 0);
        $this->assertDatabaseCount('form_submissions', 0);
    }

    public function test_form_field_options_cast_to_array(): void
    {
        $form = Form::create(['title' => 'Y', 'slug' => 'y', 'status' => 'draft']);
        $field = $form->fields()->create([
            'label' => 'Kelas', 'type' => 'select',
            'options' => ['X', 'XI', 'XII'], 'sort_order' => 0,
        ]);

        $this->assertSame(['X', 'XI', 'XII'], $field->fresh()->options);
    }
}

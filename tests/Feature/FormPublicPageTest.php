<?php

namespace Tests\Feature;

use App\Models\Form;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormPublicPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_form_page_renders_fields(): void
    {
        $form = Form::create([
            'title' => 'Pendaftaran Siswa',
            'slug' => 'pendaftaran-siswa',
            'description' => 'Isi data dengan benar.',
            'status' => 'published',
        ]);
        $form->fields()->create(['label' => 'Nama Lengkap', 'type' => 'text', 'required' => true, 'sort_order' => 1]);
        $form->fields()->create(['label' => 'Kelas', 'type' => 'select', 'options' => ['X', 'XI'], 'sort_order' => 2]);

        $this->get('/form/pendaftaran-siswa')
            ->assertOk()
            ->assertSee('Pendaftaran Siswa')
            ->assertSee('Isi data dengan benar.')
            ->assertSee('Nama Lengkap')
            ->assertSee('Kelas');
    }

    public function test_draft_form_returns_404(): void
    {
        Form::create(['title' => 'Rahasia', 'slug' => 'rahasia', 'status' => 'draft']);

        $this->get('/form/rahasia')->assertNotFound();
    }

    public function test_unknown_form_returns_404(): void
    {
        $this->get('/form/tidak-ada')->assertNotFound();
    }
}

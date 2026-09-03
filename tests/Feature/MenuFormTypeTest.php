<?php
// tests/Feature/MenuFormTypeTest.php
namespace Tests\Feature;

use App\Models\Form;
use App\Models\Menu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuFormTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_menu_url_for_form_type_points_to_form_page(): void
    {
        Form::create(['title' => 'Pendaftaran', 'slug' => 'pendaftaran', 'status' => 'published']);

        $menu = Menu::create([
            'label' => 'Daftar', 'type' => 'form', 'target' => 'pendaftaran', 'sort_order' => 0,
        ]);

        $this->assertSame(url('/form/pendaftaran'), $menu->url());
    }

    public function test_menu_url_empty_target_returns_hash(): void
    {
        $menu = Menu::create(['label' => 'Kosong', 'type' => 'form', 'target' => '', 'sort_order' => 0]);

        $this->assertSame('#', $menu->url());
    }
}

<?php

namespace Tests\Feature;

use App\Filament\Resources\Menus\Pages\CreateMenu;
use App\Filament\Resources\Menus\Pages\ListMenus;
use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Pages\Pages\ListPages;
use App\Filament\Resources\PostCategories\Pages\CreatePostCategory;
use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Models\Menu;
use App\Models\Page;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentResourcesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_page_list_renders_and_can_create(): void
    {
        Livewire::test(ListPages::class)->assertOk();

        Livewire::test(CreatePage::class)
            ->fillForm([
                'title' => 'Tentang Kami',
                'slug' => 'tentang-kami',
                'content' => '<p>Profil sekolah.</p>',
                'status' => 'published',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('pages', ['slug' => 'tentang-kami', 'status' => 'published']);
    }

    public function test_post_category_can_be_created(): void
    {
        Livewire::test(CreatePostCategory::class)
            ->fillForm(['name' => 'Pengumuman', 'slug' => 'pengumuman'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('post_categories', ['slug' => 'pengumuman']);
    }

    public function test_post_list_renders_and_can_create_with_category(): void
    {
        $category = PostCategory::create(['name' => 'Berita', 'slug' => 'berita']);

        Livewire::test(ListPosts::class)->assertOk();

        Livewire::test(CreatePost::class)
            ->fillForm([
                'title' => 'Upacara Bendera',
                'slug' => 'upacara-bendera',
                'excerpt' => 'Ringkasan.',
                'content' => '<p>Isi berita.</p>',
                'post_category_id' => $category->id,
                'published_at' => now(),
                'status' => 'published',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $post = Post::first();
        $this->assertSame('upacara-bendera', $post->slug);
        $this->assertSame($category->id, $post->post_category_id);
        $this->assertSame('Berita', $post->category->name);
    }

    public function test_menu_list_renders_and_supports_parent_child(): void
    {
        $parent = Menu::create(['label' => 'Profil', 'type' => 'url', 'target' => '#', 'sort_order' => 0]);

        Livewire::test(ListMenus::class)->assertOk();

        Livewire::test(CreateMenu::class)
            ->fillForm([
                'label' => 'Visi Misi',
                'type' => 'page',
                'target' => 'visi-misi',
                'parent_id' => $parent->id,
                'sort_order' => 1,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $child = Menu::where('label', 'Visi Misi')->first();
        $this->assertSame($parent->id, $child->parent_id);
        $this->assertTrue($parent->children->contains($child));
    }

    public function test_page_featured_image_uploads_via_media_library(): void
    {
        Storage::fake('public');

        Livewire::test(CreatePage::class)
            ->fillForm([
                'title' => 'Galeri',
                'slug' => 'galeri',
                'status' => 'published',
                'featured' => [UploadedFile::fake()->image('cover.jpg')],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $page = Page::where('slug', 'galeri')->first();
        $this->assertCount(1, $page->getMedia('featured'));
    }

    public function test_page_slug_must_be_unique(): void
    {
        Page::create(['title' => 'Kontak', 'slug' => 'kontak', 'status' => 'draft']);

        Livewire::test(CreatePage::class)
            ->fillForm([
                'title' => 'Kontak Lain',
                'slug' => 'kontak',
                'status' => 'draft',
            ])
            ->call('create')
            ->assertHasFormErrors(['slug']);
    }
}

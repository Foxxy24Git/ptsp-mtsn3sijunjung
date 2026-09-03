<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostCategory;
use Illuminate\Http\Request;

class PostController extends Controller
{
    /**
     * Listing berita published dengan filter kategori opsional (?kategori=slug).
     */
    public function index(Request $request)
    {
        $categories = PostCategory::orderBy('name')->get();

        $activeCategory = null;
        if ($slug = $request->query('kategori')) {
            $activeCategory = $categories->firstWhere('slug', $slug);
        }

        $posts = Post::query()
            ->where('status', 'published')
            ->when($activeCategory, fn ($query) => $query->where('post_category_id', $activeCategory->id))
            ->with('category')
            ->latest('published_at')
            ->latest('id')
            ->paginate(9)
            ->withQueryString();

        return view('posts.index', compact('posts', 'categories', 'activeCategory'));
    }

    /**
     * Detail berita by slug. Hanya yang published; selain itu 404.
     */
    public function show(Post $post)
    {
        abort_unless($post->status === 'published', 404);

        $post->load('category');

        return view('posts.show', compact('post'));
    }
}

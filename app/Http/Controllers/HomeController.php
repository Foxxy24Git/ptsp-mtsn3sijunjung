<?php

namespace App\Http\Controllers;

use App\Models\GalleryItem;
use App\Models\LeaderQuote;
use App\Models\Post;
use App\Models\Slide;
use App\Models\Stat;

class HomeController extends Controller
{
    /**
     * Homepage: carousel + statistik + quote pimpinan + berita terbaru.
     */
    public function index()
    {
        $slides = Slide::query()
            ->where('is_active', true)
            ->with('media')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->filter(fn (Slide $slide): bool => $slide->imageUrl() !== null)
            ->values();

        $stats = Stat::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $leader = LeaderQuote::query()
            ->where('is_active', true)
            ->where('placement', LeaderQuote::PLACEMENT_PIMPINAN)
            ->with('media')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->first(fn (LeaderQuote $leader): bool => $leader->photoUrl() !== null);

        // Barisan foto "di bawah pimpinan" (mis. waka), diurutkan lalu disusun center-out di partial.
        $pendampings = LeaderQuote::query()
            ->where('is_active', true)
            ->where('placement', LeaderQuote::PLACEMENT_PENDAMPING)
            ->with('media')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->filter(fn (LeaderQuote $item): bool => $item->photoUrl() !== null)
            ->values();

        $posts = Post::query()
            ->where('status', 'published')
            ->with('category')
            ->latest('published_at')
            ->latest('id')
            ->take(6)
            ->get();

        $galleryItems = GalleryItem::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->take(6)
            ->get()
            ->filter(fn (GalleryItem $item): bool => $item->isPhoto() ? $item->imageUrl() !== null : $item->youtubeId() !== null)
            ->values();

        return view('home', compact('slides', 'stats', 'leader', 'pendampings', 'posts', 'galleryItems'));
    }
}

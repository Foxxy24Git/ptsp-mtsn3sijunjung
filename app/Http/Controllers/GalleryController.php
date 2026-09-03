<?php

namespace App\Http\Controllers;

use App\Models\GalleryItem;

class GalleryController extends Controller
{
    /**
     * Galeri foto & video (YouTube) — hanya item aktif.
     */
    public function index()
    {
        $items = GalleryItem::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->filter(fn (GalleryItem $item): bool => $item->isPhoto() ? $item->imageUrl() !== null : $item->youtubeId() !== null)
            ->values();

        return view('galeri.index', compact('items'));
    }
}

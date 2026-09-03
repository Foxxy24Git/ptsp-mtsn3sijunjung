<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Models\Slide;

class HomeController extends Controller
{
    /**
     * Homepage: hero (slider/fallback) + katalog layanan unggulan.
     *
     * Section CMS-web lain (statistik, pimpinan, pendamping, zona integritas,
     * galeri, berita) sengaja tidak lagi di-query di sini -- sudah dikeluarkan
     * dari GeneralSettings::HOME_SECTIONS dan tidak pernah dirender di home.blade.php.
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

        $semuaLayanan = Form::query()
            ->services()
            ->published()
            ->ordered()
            ->with('workUnit')
            ->get();

        // Nomor kartu tetap global walau beranda hanya menampilkan enam teratas.
        $nomorLayanan = $semuaLayanan->pluck('id')->flip()->map(fn (int $index): int => $index + 1);
        $layananUnggulan = $semuaLayanan->take(6);

        return view('home', compact('slides', 'layananUnggulan', 'nomorLayanan'));
    }
}

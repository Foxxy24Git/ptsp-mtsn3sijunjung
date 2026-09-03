<?php

namespace App\Http\Controllers;

use App\Models\Page;

class PageController extends Controller
{
    /**
     * Tampilkan halaman statis by slug. Hanya yang published; selain itu 404.
     */
    public function show(Page $page)
    {
        abort_unless($page->status === 'published', 404);

        // Halaman Zona Integritas punya tampilan khusus (hero + daftar poin),
        // halaman lain tetap pakai template generik.
        $view = $page->slug === 'zona-integritas' ? 'pages.zona-integritas' : 'pages.show';

        return view($view, compact('page'));
    }
}

{{-- Section galeri di beranda: pratinjau beberapa item terbaru + tautan ke halaman
     lengkap. Sama seperti section Berita Terbaru secara visual. Dirender bila ada
     item galeri aktif (home.blade.php, $items dari HomeController). --}}
<section class="mx-auto max-w-6xl px-4 py-14">
    <div class="mb-8 flex items-end justify-between" data-reveal>
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Galeri</h2>
            <p class="mt-1 text-sm text-gray-500">Dokumentasi foto &amp; video kegiatan {{ $settings->site_name }}</p>
        </div>
        <a href="{{ route('galeri.index') }}" class="hidden text-sm font-medium text-primary hover:underline sm:block">Lihat Semua →</a>
    </div>

    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3" data-reveal-group>
        @foreach ($items as $item)
            @include('partials.gallery-item', ['item' => $item])
        @endforeach
    </div>

    <a href="{{ route('galeri.index') }}" class="mt-6 block text-center text-sm font-medium text-primary hover:underline sm:hidden">Lihat Semua →</a>
</section>

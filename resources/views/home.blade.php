@extends('layouts.app')

@section('content')
    {{-- Urutan & visibilitas section diatur admin via home_sections. Tiap
         section tetap punya guard "data ada": toggle admin menyembunyikan
         manual, data kosong menyembunyikan otomatis. --}}
    @foreach ($settings->orderedSections() as $section)
        @continue(! ($section['visible'] ?? true))

        @switch($section['key'])
            @case('hero')
                {{-- Hero: carousel full-bleed bila ada slide aktif, kalau belum ada pakai hero biru. --}}
                @if ($slides->isNotEmpty())
                    @include('partials.slider', ['slides' => $slides])
                @else
                    <section class="bg-primary text-white">
                        <div class="mx-auto max-w-6xl px-4 py-20 text-center" data-reveal>
                            <h1 class="text-3xl font-extrabold sm:text-5xl">Selamat Datang di {{ $settings->site_name }}</h1>
                            <p class="mx-auto mt-4 max-w-2xl text-base text-white/90 sm:text-lg">
                                Informasi terkini seputar kegiatan, prestasi, dan pengumuman sekolah.
                            </p>
                            <a href="{{ route('posts.index') }}" class="mt-8 inline-block rounded-lg bg-white px-6 py-3 text-sm font-semibold text-primary shadow transition hover:bg-gray-100">
                                Lihat Semua Berita
                            </a>
                        </div>
                    </section>
                @endif
                @break

            @case('layanan')
                {{-- Layanan PTSP. Hanya tampil bila ada layanan yang sudah terbit. --}}
                @if ($layananUnggulan->isNotEmpty())
                    @include('partials.layanan')
                @endif
                @break

            @case('stats')
                {{-- Statistik kampus (count-up + hover-fill). Hanya tampil bila ada statistik aktif. --}}
                @if ($stats->isNotEmpty())
                    @include('partials.stats', ['stats' => $stats])
                @endif
                @break

            @case('leader')
                {{-- Sambutan / quote pimpinan (reveal saat scroll). Hanya tampil bila ada pimpinan aktif. --}}
                @if ($leader)
                    @include('partials.leaders', ['leader' => $leader])
                @endif
                @break

            @case('pendamping')
                {{-- Barisan foto di bawah pimpinan (mis. waka). Hanya tampil bila ada pendamping aktif. --}}
                @if ($pendampings->isNotEmpty())
                    @include('partials.pendampings', ['pendampings' => $pendampings])
                @endif
                @break

            @case('zona_integritas')
                {{-- Zona Integritas: gradasi gelap + kartu kaca (logo + poin). Tampil bila
                     judul atau minimal satu poin terisi (default sudah terisi dari migration). --}}
                @if (filled($settings->zi_heading) || ! empty($settings->zi_points))
                    @include('partials.zona-integritas')
                @endif
                @break

            @case('galeri')
                {{-- Galeri: pratinjau foto/video terbaru. Tampil bila ada item aktif. --}}
                @if ($galleryItems->isNotEmpty())
                    @include('partials.galeri', ['items' => $galleryItems])
                @endif
                @break

            @case('posts')
                {{-- Berita terbaru --}}
                <section class="mx-auto max-w-6xl px-4 py-14">
                    <div class="mb-8 flex items-end justify-between" data-reveal>
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900">Berita Terbaru</h2>
                            <p class="mt-1 text-sm text-gray-500">Kabar terbaru dari {{ $settings->site_name }}</p>
                        </div>
                        <a href="{{ route('posts.index') }}" class="hidden text-sm font-medium text-primary hover:underline sm:block">Selengkapnya →</a>
                    </div>

                    @if ($posts->isEmpty())
                        <p class="rounded-lg border border-dashed border-gray-300 bg-white p-10 text-center text-gray-500" data-reveal>
                            Belum ada berita yang dipublikasikan.
                        </p>
                    @else
                        {{-- data-reveal-group: tiap kartu muncul bergantian (cascade), bukan sekaligus --}}
                        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3" data-reveal-group>
                            @foreach ($posts as $post)
                                @include('partials.post-card', ['post' => $post])
                            @endforeach
                        </div>
                    @endif
                </section>
                @break
        @endswitch
    @endforeach
@endsection

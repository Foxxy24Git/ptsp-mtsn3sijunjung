@extends('layouts.app')

@section('content')
    @include('partials.lacak-search')

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
                                Ajukan permohonan layanan secara daring dan pantau statusnya dengan kode resi.
                            </p>
                            <a href="{{ route('layanan.index') }}" class="mt-8 inline-block rounded-lg bg-white px-6 py-3 text-sm font-semibold text-primary shadow transition hover:bg-gray-100">
                                Lihat Katalog Layanan
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
        @endswitch
    @endforeach
@endsection

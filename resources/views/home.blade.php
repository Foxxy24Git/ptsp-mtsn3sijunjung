@extends('layouts.app')

@section('content')
    {{-- Urutan & visibilitas section diatur admin via home_sections. Tiap
         section tetap punya guard "data ada": toggle admin menyembunyikan
         manual, data kosong menyembunyikan otomatis. --}}
    @foreach ($settings->orderedSections() as $section)
        @continue(! ($section['visible'] ?? true))

        @switch($section['key'])
            @case('lacak')
                {{-- Kotak pencarian kode resi. Statis, tidak punya guard "data
                     ada" karena tidak bergantung data apa pun. --}}
                @include('partials.lacak-search')
                @break

            @case('hero')
                {{-- Hero: carousel full-bleed bila ada slide aktif, kalau belum ada
                     pakai hero statis yang backgroundnya (gambar/gradasi) diatur admin. --}}
                @if ($slides->isNotEmpty())
                    @include('partials.slider', ['slides' => $slides])
                @else
                    @include('partials.hero')
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

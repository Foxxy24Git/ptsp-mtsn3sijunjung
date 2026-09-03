@extends('layouts.app')

@section('title', $page->title)

@section('content')
    <nav class="mx-auto max-w-4xl px-4 pt-8 text-sm text-gray-500">
        <a href="{{ url('/') }}" class="hover:text-primary">Beranda</a>
        <span class="mx-1">/</span>
        <span class="text-gray-700">{{ $page->title }}</span>
    </nav>

    <article class="mx-auto max-w-4xl px-4 pb-16 pt-6">
        {{-- Hero: lencana perisai + judul + tagline, senada dengan gaya lencana ikon statistik. --}}
        <div class="flex flex-col items-center text-center" data-reveal>
            <span class="flex h-16 w-16 items-center justify-center rounded-full bg-primary/10 text-primary">
                <x-heroicon-o-shield-check class="h-8 w-8" />
            </span>
            <h1 class="mt-5 text-3xl font-extrabold text-gray-900 sm:text-4xl">{{ $page->title }}</h1>
            <p class="mx-auto mt-3 max-w-2xl text-base text-gray-600">
                Komitmen bersama seluruh warga sekolah untuk mewujudkan tata kelola yang bersih, transparan, akuntabel, dan melayani.
            </p>
        </div>

        @php $img = $page->getFirstMediaUrl('featured'); @endphp
        @if ($img)
            <img src="{{ $img }}" alt="{{ $page->title }}" class="mt-8 w-full rounded-2xl object-cover shadow-sm">
        @endif

        {{-- Konten dari admin (RichEditor). Class .zi-content memberi gaya checklist
             pada daftar poin, lihat app.css. --}}
        <div class="zi-content prose mt-10 max-w-none rounded-2xl border border-gray-100 bg-white p-6 shadow-sm sm:p-10" data-reveal>
            {!! $page->content !!}
        </div>
    </article>
@endsection

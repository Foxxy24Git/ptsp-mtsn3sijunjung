@extends('layouts.app')

@section('title', 'Galeri')

@section('content')
    <div class="mx-auto max-w-6xl px-4 py-12">
        <header class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 sm:text-4xl">Galeri</h1>
            <p class="mt-2 text-gray-500">Dokumentasi foto &amp; video kegiatan {{ $settings->site_name }}.</p>
        </header>

        @if ($items->isEmpty())
            <p class="rounded-lg border border-dashed border-gray-300 bg-white p-10 text-center text-gray-500">
                Belum ada foto atau video di galeri.
            </p>
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3" data-reveal-group>
                @foreach ($items as $item)
                    @include('partials.gallery-item', ['item' => $item])
                @endforeach
            </div>
        @endif
    </div>
@endsection

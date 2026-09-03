@extends('layouts.app')

@section('title', 'Berita')

@section('content')
    <div class="mx-auto max-w-6xl px-4 py-12">
        <header class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 sm:text-4xl">Berita &amp; Pengumuman</h1>
            @if ($activeCategory)
                <p class="mt-2 text-gray-500">Kategori: <span class="font-medium text-primary">{{ $activeCategory->name }}</span></p>
            @endif
        </header>

        {{-- Filter kategori --}}
        <div class="mb-8 flex flex-wrap gap-2">
            <a href="{{ route('posts.index') }}"
               class="rounded-full border px-4 py-1.5 text-sm font-medium transition {{ ! $activeCategory ? 'border-primary bg-primary text-white' : 'border-gray-300 bg-white text-gray-700 hover:border-primary hover:text-primary' }}">
                Semua
            </a>
            @foreach ($categories as $category)
                <a href="{{ route('posts.index', ['kategori' => $category->slug]) }}"
                   class="rounded-full border px-4 py-1.5 text-sm font-medium transition {{ $activeCategory?->is($category) ? 'border-primary bg-primary text-white' : 'border-gray-300 bg-white text-gray-700 hover:border-primary hover:text-primary' }}">
                    {{ $category->name }}
                </a>
            @endforeach
        </div>

        @if ($posts->isEmpty())
            <p class="rounded-lg border border-dashed border-gray-300 bg-white p-10 text-center text-gray-500">
                Belum ada berita pada kategori ini.
            </p>
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($posts as $post)
                    @include('partials.post-card', ['post' => $post])
                @endforeach
            </div>

            <div class="mt-10">
                {{ $posts->links() }}
            </div>
        @endif
    </div>
@endsection

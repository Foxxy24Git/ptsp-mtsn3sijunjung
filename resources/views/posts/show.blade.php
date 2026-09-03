@extends('layouts.app')

@section('title', $post->title)

@section('content')
    @php
        $img = $post->getFirstMediaUrl('featured');
        $date = $post->published_at ?? $post->created_at;
    @endphp

    <article class="mx-auto max-w-3xl px-4 py-12">
        <nav class="mb-6 text-sm text-gray-500">
            <a href="{{ url('/') }}" class="hover:text-primary">Beranda</a>
            <span class="mx-1">/</span>
            <a href="{{ route('posts.index') }}" class="hover:text-primary">Berita</a>
            <span class="mx-1">/</span>
            <span class="text-gray-700">{{ $post->title }}</span>
        </nav>

        <div class="flex flex-wrap items-center gap-3 text-sm">
            @if ($post->category)
                <a href="{{ route('posts.index', ['kategori' => $post->category->slug]) }}"
                   class="rounded-full bg-primary/10 px-3 py-1 font-medium text-primary">{{ $post->category->name }}</a>
            @endif
            <time class="text-gray-500">{{ $date?->locale('id')->isoFormat('D MMMM Y') }}</time>
        </div>

        <h1 class="mt-4 text-3xl font-bold text-gray-900 sm:text-4xl">{{ $post->title }}</h1>

        @if ($img)
            <img src="{{ $img }}" alt="{{ $post->title }}" class="mt-6 w-full rounded-xl object-cover">
        @endif

        <div class="prose mt-8 max-w-none">
            {!! $post->content !!}
        </div>

        <div class="mt-10 border-t border-gray-200 pt-6">
            <a href="{{ route('posts.index') }}" class="text-sm font-medium text-primary hover:underline">← Kembali ke daftar berita</a>
        </div>
    </article>
@endsection

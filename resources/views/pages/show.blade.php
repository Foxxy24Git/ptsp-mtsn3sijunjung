@extends('layouts.app')

@section('title', $page->title)

@section('content')
    <article class="mx-auto max-w-3xl px-4 py-12">
        <nav class="mb-6 text-sm text-gray-500">
            <a href="{{ url('/') }}" class="hover:text-primary">Beranda</a>
            <span class="mx-1">/</span>
            <span class="text-gray-700">{{ $page->title }}</span>
        </nav>

        <h1 class="text-3xl font-bold text-gray-900 sm:text-4xl">{{ $page->title }}</h1>

        @php $img = $page->getFirstMediaUrl('featured'); @endphp
        @if ($img)
            <img src="{{ $img }}" alt="{{ $page->title }}" class="mt-6 w-full rounded-xl object-cover">
        @endif

        <div class="prose mt-8 max-w-none">
            {!! $page->content !!}
        </div>
    </article>
@endsection

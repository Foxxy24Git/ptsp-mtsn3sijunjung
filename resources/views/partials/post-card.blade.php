@php
    $img = $post->getFirstMediaUrl('featured');
    $date = $post->published_at ?? $post->created_at;
@endphp
<article class="group flex flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition hover:shadow-md">
    <a href="{{ route('posts.show', $post->slug) }}" class="block aspect-video overflow-hidden bg-gray-100">
        @if ($img)
            <img src="{{ $img }}" alt="{{ $post->title }}" class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
        @else
            <div class="flex h-full w-full items-center justify-center bg-primary/10 text-primary">
                <svg class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 01-2.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 002.25 2.25h13.5"/></svg>
            </div>
        @endif
    </a>
    <div class="flex flex-1 flex-col p-5">
        <div class="mb-2 flex items-center gap-2 text-xs">
            @if ($post->category)
                <span class="rounded-full bg-primary/10 px-2.5 py-0.5 font-medium text-primary">{{ $post->category->name }}</span>
            @endif
            <time class="text-gray-400">{{ $date?->locale('id')->isoFormat('D MMM Y') }}</time>
        </div>
        <h3 class="text-lg font-semibold leading-snug text-gray-900">
            <a href="{{ route('posts.show', $post->slug) }}" class="hover:text-primary">{{ $post->title }}</a>
        </h3>
        <p class="mt-2 line-clamp-3 text-sm text-gray-600">
            {{ $post->excerpt ?: str(strip_tags($post->content))->limit(120) }}
        </p>
        <a href="{{ route('posts.show', $post->slug) }}" class="mt-4 inline-block text-sm font-medium text-primary hover:underline">Baca selengkapnya →</a>
    </div>
</article>

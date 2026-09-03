{{-- Satu kartu galeri (foto atau video), gaya sama seperti kartu berita (post-card)
     supaya tidak monoton: thumbnail + judul + keterangan selalu tampil (bukan cuma
     saat hover). Thumbnail diklik untuk membuka lightbox global (layouts/app.blade.php
     + app.js). Video diputar langsung di sana via iframe YouTube — tidak pernah
     redirect ke youtube.com. --}}
@php
    $thumb = $item->isPhoto() ? $item->imageUrl() : $item->youtubeThumbnailUrl();
@endphp
<article class="group flex flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition hover:shadow-md">
    <button
        type="button"
        class="block aspect-video w-full overflow-hidden bg-gray-100 text-left"
        data-gallery-item
        data-type="{{ $item->type }}"
        data-full-src="{{ $thumb }}"
        @if ($item->isVideo()) data-embed-url="{{ $item->youtubeEmbedUrl() }}" @endif
        data-title="{{ $item->title }}"
    >
        <span class="relative block h-full w-full">
            <img
                src="{{ $thumb }}"
                alt="{{ $item->title ?: 'Galeri kegiatan sekolah' }}"
                class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
                loading="lazy"
            >

            @if ($item->isVideo())
                <span class="absolute inset-0 flex items-center justify-center bg-black/10 transition group-hover:bg-black/25">
                    <span class="flex h-12 w-12 items-center justify-center rounded-full bg-white/95 text-primary shadow-lg transition group-hover:scale-110">
                        <svg class="ml-0.5 h-5 w-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
                    </span>
                </span>
            @endif
        </span>
    </button>

    @if ($item->title || $item->description)
        <div class="flex flex-1 flex-col p-4">
            @if ($item->title)
                <h3 class="text-sm font-semibold leading-snug text-gray-900 line-clamp-2">{{ $item->title }}</h3>
            @endif
            @if ($item->description)
                <p class="mt-1 line-clamp-2 text-xs text-gray-500">{{ $item->description }}</p>
            @endif
        </div>
    @endif
</article>

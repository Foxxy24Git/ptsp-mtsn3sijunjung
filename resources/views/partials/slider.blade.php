{{-- Hero carousel full-bleed (auto-slide + crossfade + Ken Burns). Gambar jadi latar,
     teks overlay di kiri. Dikontrol resources/js/app.js. Hanya dirender bila ada slide. --}}
@php $isSingle = $slides->count() === 1; @endphp

<section
    class="slider group relative h-[420px] w-full overflow-hidden bg-gray-900 sm:h-[520px] lg:h-[640px]"
    data-slider
    data-interval="5000"
    role="region"
    aria-roledescription="carousel"
    aria-label="Sorotan utama"
>
    @foreach ($slides as $i => $slide)
        @php $link = trim((string) $slide->link_url); @endphp
        <div
            class="slider__slide absolute inset-0 {{ $i === 0 ? 'is-active' : '' }}"
            data-slide
            role="group"
            aria-roledescription="slide"
            aria-label="{{ $i + 1 }} dari {{ $slides->count() }}"
            @if ($i !== 0) aria-hidden="true" @endif
        >
            <img
                src="{{ $slide->imageUrl() }}"
                alt="{{ $slide->title ?: 'Slide '.($i + 1) }}"
                class="slider__img h-full w-full object-cover"
                loading="{{ $i === 0 ? 'eager' : 'lazy' }}"
                draggable="false"
            >

            {{-- Gradient gelap agar teks terbaca (dari kiri & bawah) --}}
            <div class="pointer-events-none absolute inset-0 bg-gradient-to-r from-black/80 via-black/45 to-transparent"></div>
            <div class="pointer-events-none absolute inset-x-0 bottom-0 h-1/3 bg-gradient-to-t from-black/60 to-transparent"></div>

            {{-- Konten teks overlay (kiri, tengah vertikal) --}}
            <div class="absolute inset-0">
                <div class="mx-auto flex h-full max-w-6xl flex-col justify-center px-6 sm:px-8">
                    <div class="max-w-2xl">
                        @if ($slide->title)
                            <h2 class="text-3xl font-extrabold uppercase leading-tight tracking-tight text-white drop-shadow-lg sm:text-5xl lg:text-6xl">{{ $slide->title }}</h2>
                        @endif
                        @if ($slide->description)
                            <p class="mt-4 max-w-xl text-base text-white/90 drop-shadow sm:text-lg">{{ $slide->description }}</p>
                        @endif
                        @if ($link !== '')
                            <a href="{{ $link }}" class="mt-6 inline-block rounded-lg bg-primary px-6 py-3 text-sm font-semibold text-white shadow-lg transition hover:opacity-90">
                                {{ $slide->link_label ?: 'Selengkapnya' }}
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Bila tanpa teks tapi ada link: seluruh slide bisa diklik --}}
            @if (! $slide->title && ! $slide->description && $link !== '')
                <a href="{{ $link }}" class="absolute inset-0" aria-label="Buka tautan slide {{ $i + 1 }}"></a>
            @endif
        </div>
    @endforeach

    @unless ($isSingle)
        {{-- Tombol navigasi (muncul saat hover / fokus keyboard) --}}
        <button type="button" data-slider-prev
            class="absolute left-4 top-1/2 z-10 -translate-y-1/2 rounded-full bg-black/35 p-2.5 text-white opacity-0 transition hover:bg-black/55 focus:opacity-100 focus-visible:outline-none group-hover:opacity-100"
            aria-label="Slide sebelumnya">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        </button>
        <button type="button" data-slider-next
            class="absolute right-4 top-1/2 z-10 -translate-y-1/2 rounded-full bg-black/35 p-2.5 text-white opacity-0 transition hover:bg-black/55 focus:opacity-100 focus-visible:outline-none group-hover:opacity-100"
            aria-label="Slide berikutnya">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </button>

        {{-- Indikator titik (dots) --}}
        <div class="absolute bottom-5 left-1/2 z-10 flex -translate-x-1/2 gap-2" data-slider-dots>
            @foreach ($slides as $i => $slide)
                <button type="button"
                    class="slider__dot h-2.5 w-2.5 rounded-full bg-white/50 transition {{ $i === 0 ? 'is-active' : '' }}"
                    data-dot="{{ $i }}"
                    aria-label="Ke slide {{ $i + 1 }}"></button>
            @endforeach
        </div>
    @endunless
</section>

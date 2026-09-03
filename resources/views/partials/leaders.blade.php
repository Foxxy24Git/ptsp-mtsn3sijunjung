{{-- Section sambutan/quote pimpinan (satu pimpinan). Kiri: foto PNG transparan dengan
     elemen dekoratif pilihan (background_style); kanan: nama (kecil) + judul besar + quote.
     Muncul beranimasi saat di-scroll ([data-reveal]). Dirender bila ada pimpinan aktif berfoto. --}}
@php
    $heading = $leader->title ?: $leader->name;
    $eyebrow = $leader->title ? $leader->name : $leader->position;
    $subline = $leader->title ? $leader->position : null;
    $style = $leader->background_style ?: 'arch';
@endphp

<section class="mx-auto max-w-6xl px-4 py-16 sm:py-20" data-section="leader">
    <div class="grid items-center gap-10 md:grid-cols-2 md:gap-14" data-reveal>
        {{-- Kiri: foto pimpinan + elemen dekoratif --}}
        <div class="relative mx-auto flex h-[24rem] w-full max-w-md items-end justify-center sm:h-[30rem]">
            @include('partials.decor', ['style' => $style])

            <img
                src="{{ $leader->photoUrl() }}"
                alt="{{ $leader->name }}{{ $leader->position ? ' — '.$leader->position : '' }}"
                class="relative z-10 max-h-full w-auto object-contain drop-shadow-2xl"
                loading="lazy"
                draggable="false"
            >
        </div>

        {{-- Kanan: nama, judul, quote --}}
        <div>
            @if ($eyebrow)
                <p class="text-lg font-semibold text-primary sm:text-xl">{{ $eyebrow }}</p>
            @endif

            <h2 class="mt-1 text-4xl font-extrabold leading-tight tracking-tight text-gray-900 sm:text-5xl">{{ $heading }}</h2>

            @if ($subline)
                <p class="mt-2 text-base font-medium text-gray-500">{{ $subline }}</p>
            @endif

            @if ($leader->quote)
                <p class="mt-6 whitespace-pre-line text-base leading-relaxed text-gray-600 sm:text-lg">{{ $leader->quote }}</p>
            @endif

            <span class="mt-8 block h-1 w-24 rounded-full bg-primary"></span>
        </div>
    </div>
</section>

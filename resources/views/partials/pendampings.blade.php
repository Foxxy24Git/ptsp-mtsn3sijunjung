{{-- Barisan foto "di bawah pimpinan" (mis. waka sekolah). Tiap foto PNG/JPG di atas
     elemen dekoratif pilihan masing-masing (background_style), dengan nama (hijau) +
     jabatan (abu). Disusun center-out: entri urutan terkecil di tengah, berikutnya
     melebar kanan-kiri bergantian. Muncul beranimasi saat di-scroll ([data-reveal]).
     Dirender bila ada pendamping aktif berfoto. --}}
@php
    // Susun center-out: index 0 di tengah, ganjil ke kanan, genap ke kiri, lalu digabung.
    $items = $pendampings->values();
    $center = null;
    $right = collect();
    $left = collect();

    foreach ($items as $i => $item) {
        if ($i === 0) {
            $center = $item;
        } elseif ($i % 2 === 1) {
            $right->push($item);
        } else {
            $left->push($item);
        }
    }

    $display = $left->reverse()->values();
    if ($center) {
        $display->push($center);
    }
    $display = $display->concat($right)->values();
@endphp

<section class="mx-auto max-w-6xl px-4 pb-16 sm:pb-20" data-section="pendamping">
    <div class="flex flex-wrap items-end justify-center gap-x-6 gap-y-10 sm:gap-x-8" data-reveal>
        @foreach ($display as $person)
            @php $style = $person->background_style ?: 'arch'; @endphp
            <figure class="flex w-36 flex-col items-center sm:w-44">
                @if (\App\Models\LeaderQuote::isFramedStyle($style))
                    {{-- Foto masuk ke dalam bingkai (di-clip mengikuti bentuk) --}}
                    @include('partials.pendamping-frame', ['person' => $person, 'style' => $style])
                @else
                    {{-- Foto cutout menonjol keluar + elemen dekoratif di belakang --}}
                    <div class="relative flex h-52 w-full items-end justify-center sm:h-60">
                        @include('partials.decor', ['style' => $style])

                        <img
                            src="{{ $person->photoUrl() }}"
                            alt="{{ $person->name }}{{ $person->position ? ' — '.$person->position : '' }}"
                            class="relative z-10 max-h-full w-auto object-contain drop-shadow-xl"
                            loading="lazy"
                            draggable="false"
                        >
                    </div>
                @endif

                <figcaption class="mt-3 text-center">
                    <p class="text-sm font-bold leading-tight text-primary sm:text-base">{{ $person->name }}</p>
                    @if ($person->position)
                        <p class="mt-0.5 text-xs font-semibold uppercase tracking-wide text-gray-600">{{ $person->position }}</p>
                    @endif
                </figcaption>
            </figure>
        @endforeach
    </div>
</section>

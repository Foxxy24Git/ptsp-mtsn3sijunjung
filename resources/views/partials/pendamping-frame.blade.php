{{-- Foto "masuk ke dalam" elemen: foto di-clip mengikuti bentuk bingkai (object-cover),
     bukan cutout yang menonjol keluar. Khusus barisan di bawah pimpinan (waka).
     $person = LeaderQuote, $style = salah satu framedStyleOptions(). --}}
@php
    $alt = $person->name.($person->position ? ' — '.$person->position : '');
    $src = $person->photoUrl();
@endphp

@switch($style)
    @case('framed_circle')
        <div class="relative flex h-52 w-full items-center justify-center sm:h-60">
            <span aria-hidden="true" class="absolute bottom-5 right-5 h-7 w-7 rounded-full bg-primary/40"></span>
            <div class="relative h-40 w-40 overflow-hidden rounded-full bg-white shadow-lg ring-4 ring-primary sm:h-44 sm:w-44">
                <img src="{{ $src }}" alt="{{ $alt }}" class="h-full w-full object-cover object-top" loading="lazy" draggable="false">
            </div>
        </div>
        @break

    @case('framed_arch')
        <div class="relative h-52 w-full sm:h-60">
            <span aria-hidden="true" class="absolute inset-x-4 bottom-0 h-10 rounded-b-2xl bg-primary"></span>
            <div class="absolute inset-x-1 bottom-3 top-0 overflow-hidden rounded-t-[45%] rounded-b-2xl border-2 border-primary/70 bg-white shadow-lg">
                <img src="{{ $src }}" alt="{{ $alt }}" class="h-full w-full object-cover object-top" loading="lazy" draggable="false">
            </div>
        </div>
        @break

    @case('framed_diagonal')
        <div class="relative h-52 w-full sm:h-60">
            <span aria-hidden="true" class="absolute inset-0 bg-primary" style="clip-path: polygon(0 12%, 100% 0, 100% 88%, 0 100%);"></span>
            <div class="absolute inset-[5px] overflow-hidden bg-white" style="clip-path: polygon(0 12%, 100% 0, 100% 88%, 0 100%);">
                <img src="{{ $src }}" alt="{{ $alt }}" class="h-full w-full object-cover object-top" loading="lazy" draggable="false">
            </div>
        </div>
        @break

    @case('framed_blob')
        <div class="relative flex h-52 w-full items-center justify-center sm:h-60">
            <span aria-hidden="true" class="absolute left-1/2 top-1/2 h-44 w-40 -translate-x-1/2 -translate-y-1/2 bg-primary" style="border-radius: 42% 58% 43% 57% / 45% 45% 55% 55%;"></span>
            <div class="relative h-40 w-36 overflow-hidden bg-white shadow-lg" style="border-radius: 42% 58% 43% 57% / 45% 45% 55% 55%;">
                <img src="{{ $src }}" alt="{{ $alt }}" class="h-full w-full object-cover object-top" loading="lazy" draggable="false">
            </div>
        </div>
        @break

    @default {{-- framed_card: kartu membulat + sudut hijau diagonal, seperti gambar contoh --}}
        <div class="relative h-52 w-full sm:h-60">
            <span aria-hidden="true" class="absolute inset-x-3 bottom-2 top-6 rounded-[1.75rem] bg-primary"></span>
            <div class="absolute inset-x-0 bottom-4 top-0 overflow-hidden rounded-[1.75rem] rounded-br-[3.5rem] bg-white shadow-lg ring-1 ring-black/5">
                <img src="{{ $src }}" alt="{{ $alt }}" class="h-full w-full object-cover object-top" loading="lazy" draggable="false">
            </div>
        </div>
@endswitch

{{-- Section statistik. Tanpa background (menyatu dengan halaman). Angka count-up saat
     terlihat (app.js); teks non-angka tampil apa adanya. Saat hover, warna item mengalir
     keluar dari ikon mengisi lingkaran (CSS clip-path). Warna & teks-kontras per item
     lewat --stat-color / --stat-fg. Dirender bila ada statistik aktif (home.blade.php). --}}
<section class="mx-auto max-w-6xl px-4 py-10 sm:py-12">
    {{-- Deretan statistik: selalu terpusat di tengah berapa pun jumlahnya --}}
    <div class="flex flex-wrap items-start justify-center gap-x-10 gap-y-8" data-reveal>
        @foreach ($stats as $stat)
            <div
                class="stat flex w-36 flex-col items-center text-center sm:w-40"
                style="--stat-color: {{ $stat->color }}; --stat-fg: {{ $stat->contrastColor() }}"
            >
                <div class="relative">
                    <div class="stat__circle">
                        <span class="stat__fill" aria-hidden="true"></span>
                        <span
                            class="stat__num"
                            @if ($stat->isNumericValue()) data-countup="{{ $stat->value }}" data-suffix="{{ $stat->suffix }}" @endif
                        >{{ $stat->value }}{{ $stat->suffix }}</span>
                    </div>
                    <span class="stat__badge" aria-hidden="true">
                        <x-dynamic-component :component="$stat->icon" class="h-4 w-4" />
                    </span>
                </div>
                <p class="mt-5 text-sm font-semibold uppercase tracking-wide text-gray-700">{{ $stat->label }}</p>
            </div>
        @endforeach
    </div>
</section>

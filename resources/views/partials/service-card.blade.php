{{-- Kartu katalog layanan. Variabel: $layanan (Form), $nomor (int). --}}
<article class="flex h-full flex-col rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md">
    <div class="flex items-start justify-between gap-3">
        <span class="inline-flex items-center gap-1.5 rounded-full bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary">
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.878.53 2.32-.348a17 17 0 014.486-5.276c.878-.442 1.047-1.621.348-2.32L11.16 3.66A2.25 2.25 0 009.568 3z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z" />
            </svg>
            {{ $layanan->organizer }} / {{ $layanan->workUnit?->name ?? 'Umum' }}
        </span>
        <span class="shrink-0 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500">#{{ $nomor }}</span>
    </div>

    <h3 class="mt-4 line-clamp-2 text-lg font-semibold leading-snug text-gray-900">
        {{ $layanan->title }}
    </h3>

    {{-- mt-auto mendorong blok bawah ke dasar kartu supaya semua kartu
         dalam satu baris berakhir rata walau panjang judulnya berbeda. --}}
    <div class="mt-auto pt-4">
        <div class="flex items-center justify-between rounded-xl bg-gray-50 px-4 py-3 text-sm">
            <span class="flex items-center gap-1.5 text-gray-600">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ $layanan->duration_text ?: 'Menyesuaikan' }}
            </span>
            <span class="flex items-center gap-1.5 font-medium text-primary">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                </svg>
                {{ $layanan->fee_text }}
            </span>
        </div>

        <div class="mt-4 grid grid-cols-2 gap-3">
            <a href="{{ route('layanan.show', $layanan) }}"
               class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-gray-300 px-3 py-2.5 text-sm font-medium text-gray-700 transition hover:border-primary hover:text-primary focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                Rincian
            </a>
            <a href="{{ route('layanan.ajukan', $layanan) }}"
               class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-primary px-3 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                Ajukan
                <span aria-hidden="true">&rarr;</span>
            </a>
        </div>
    </div>
</article>

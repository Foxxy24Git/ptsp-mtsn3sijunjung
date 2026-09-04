{{-- Kotak pencarian cepat kode resi. Selalu tampil di beranda — bukan
     bagian dari $settings->orderedSections() karena ini pintu masuk
     fungsional (pelacakan permohonan), bukan konten yang admin atur
     tampil/sembunyi/urutannya. --}}
@php
    // Tutorial statis 4 langkah di bawah kotak pencarian, untuk pemohon baru
    // yang belum pernah mengajukan (belum punya kode resi untuk dilacak).
    $lacakLangkah = [
        [
            'label' => 'Pilih Layanan',
            'desc' => 'Cari jenis layanan pada katalog publik, lalu klik tombol Rincian atau Ajukan.',
            'tag' => 'Cepat & Mudah',
            'icon' => 'clipboard-document-list',
        ],
        [
            'label' => 'Isi Formulir & Berkas',
            'desc' => 'Lengkapi data diri, nomor WhatsApp & Email aktif, serta unggah dokumen persyaratan.',
            'tag' => 'Unggah Dokumen',
            'icon' => 'arrow-up-tray',
        ],
        [
            'label' => 'Simpan Kode Resi',
            'desc' => 'Sistem menghasilkan Kode Resi. Simpan kode tersebut untuk memantau status berkas.',
            'tag' => 'Wajib Disimpan',
            'icon' => 'identification',
        ],
        [
            'label' => 'Lacak & Selesai',
            'desc' => 'Pantau progres verifikasi secara real-time hingga dokumen selesai & siap diunduh.',
            'tag' => 'Transparan & Akurat',
            'icon' => 'signal',
        ],
    ];
@endphp
<section class="border-b border-gray-200 bg-primary/5">
    <div class="mx-auto max-w-3xl px-4 py-8 text-center" data-reveal>
        <p class="text-sm font-medium text-gray-600">Sudah mengajukan permohonan?</p>
        <h2 class="mt-1 text-lg font-bold text-gray-900">Lacak Status Permohonan Anda</h2>

        <form method="GET" action="{{ route('lacak.index') }}" class="mx-auto mt-4 flex max-w-xl flex-col gap-2 sm:flex-row">
            <label for="beranda-kode-resi" class="sr-only">Kode Resi</label>
            <div class="relative flex-1">
                <svg class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z" />
                </svg>
                <input id="beranda-kode-resi" type="text" name="receipt_code"
                       placeholder="Ketik Kode Resi... (mis. PTSP-2609-A7K3QX)"
                       class="w-full rounded-full border border-gray-300 bg-white py-3 pl-11 pr-4 font-mono text-sm uppercase text-gray-900 shadow-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
            </div>
            <button type="submit"
                    class="inline-flex items-center justify-center gap-1.5 rounded-full bg-primary px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                Lacak
                <span aria-hidden="true">&rarr;</span>
            </button>
        </form>
    </div>

    <div class="mx-auto max-w-6xl px-4 pb-10" data-reveal-group>
        <div class="border-t border-gray-900/10 pt-8 text-center">
            <p class="inline-flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-gray-500">
                <x-heroicon-o-light-bulb class="h-4 w-4 text-primary" aria-hidden="true" />
                Belum pernah mengajukan?
            </p>
            <h3 class="mt-1 text-base font-bold text-gray-900">4 Langkah Mengurus Permohonan</h3>
        </div>

        <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($lacakLangkah as $langkah)
                <div class="rounded-xl border border-gray-200 bg-white p-4 text-left shadow-sm">
                    <div class="flex items-center justify-between gap-2">
                        <span class="inline-flex items-center gap-1.5 text-xs font-bold text-primary">
                            <x-dynamic-component :component="'heroicon-o-'.$langkah['icon']" class="h-4 w-4" aria-hidden="true" />
                            {{ sprintf('%02d', $loop->iteration) }}
                        </span>
                        @if ($loop->last)
                            <x-heroicon-o-check-circle class="h-4 w-4 shrink-0 text-emerald-500" aria-hidden="true" />
                        @else
                            <x-heroicon-o-arrow-right class="h-4 w-4 shrink-0 text-gray-300" aria-hidden="true" />
                        @endif
                    </div>

                    <h4 class="mt-2.5 text-sm font-semibold text-gray-900">{{ $langkah['label'] }}</h4>
                    <p class="mt-1 text-xs leading-relaxed text-gray-500">{{ $langkah['desc'] }}</p>

                    <p class="mt-2.5 text-[11px] font-semibold uppercase tracking-wide text-primary">{{ $langkah['tag'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

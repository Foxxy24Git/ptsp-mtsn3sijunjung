{{-- Kotak pencarian cepat kode resi. Selalu tampil di beranda — bukan
     bagian dari $settings->orderedSections() karena ini pintu masuk
     fungsional (pelacakan permohonan), bukan konten yang admin atur
     tampil/sembunyi/urutannya. --}}
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
</section>

{{-- Daftar dokumen yang diunggah admin untuk diunduh pengaju (field bertipe
     'document' pada layanan ini, bukan isian). Dipakai di modal Rincian
     (template pada service-card.blade.php) & halaman detail (layanan/show.blade.php).
     Variabel: $layanan (Form) -- relasi fields harus sudah dimuat & difilter
     type=document oleh controller (lihat ServiceController & HomeController)
     supaya tidak N+1 dan tidak ikut field isian lain. --}}
@php $dokumen = $layanan->fields->where('type', 'document')->whereNotNull('document_path'); @endphp

@if ($dokumen->isNotEmpty())
    <div>
        <h3 class="text-base font-semibold text-gray-900">Dokumen Terkait</h3>
        <div class="mt-2 space-y-2">
            @foreach ($dokumen as $dok)
                <a href="{{ $dok->documentUrl() }}" download="{{ $dok->documentDownloadName() }}"
                   class="flex items-center gap-2.5 rounded-lg border border-gray-200 px-3.5 py-2.5 text-sm font-medium text-gray-700 transition hover:border-primary hover:bg-primary/5 hover:text-primary">
                    <svg class="h-4 w-4 shrink-0 text-primary" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    <span class="flex-1">{{ $dok->label }}</span>
                    <span class="shrink-0 text-xs text-gray-400">Unduh</span>
                </a>
            @endforeach
        </div>
    </div>
@endif

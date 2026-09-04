{{-- Kartu dokumen unduhan pada Halaman (koleksi media 'documents' milik
     Page). Dipasang terpisah dari 'featured' karena 'featured' dirender
     sebagai gambar banner (lihat pages/show.blade.php), bukan berkas
     unduhan. Variabel: $page (Page). --}}
@php $dokumen = $page->getMedia('documents'); @endphp

@if ($dokumen->isNotEmpty())
    <div class="mt-10">
        <h3 class="text-base font-semibold text-gray-900">Dokumen Terkait</h3>
        <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3">
            @foreach ($dokumen as $dok)
                {{-- Filament menyimpan berkas di disk dengan nama acak (ULID) demi
                     keamanan (lihat BaseFileUpload::preserveFilenames()) -- nama asli
                     cuma bertahan di kolom `name` (tanpa ekstensi), jadi disusun
                     ulang di sini, bukan pakai $dok->file_name. --}}
                @php $namaTampil = $dok->name.'.'.$dok->extension; @endphp
                <a href="{{ $dok->getUrl() }}" download="{{ $namaTampil }}"
                   class="flex flex-col overflow-hidden rounded-xl border border-gray-200 bg-white text-center transition hover:border-primary hover:bg-primary/5">
                    <div class="flex aspect-[4/3] items-center justify-center bg-gray-50">
                        @if ($dok->hasGeneratedConversion('preview'))
                            {{-- Pratinjau halaman pertama sungguhan (cuma PDF -- lihat
                                 Page::registerMediaConversions()). Word (doc/docx) tidak
                                 bisa dirender begini, otomatis jatuh ke ikon di bawah. --}}
                            <img src="{{ $dok->getUrl('preview') }}" alt="" class="h-full w-full object-cover object-top">
                        @else
                            <svg class="h-9 w-9 shrink-0 text-primary" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25M9 16.5v.75m3-3v3M15 12v5.25m-4.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                            </svg>
                        @endif
                    </div>
                    <div class="flex flex-col gap-1 px-4 py-3">
                        <span class="line-clamp-2 break-all text-sm font-medium text-gray-700">{{ $namaTampil }}</span>
                        <span class="text-xs text-gray-400">{{ $dok->human_readable_size }}</span>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
@endif

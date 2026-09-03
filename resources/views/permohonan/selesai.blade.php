@extends('layouts.app')

@section('title', 'Permohonan Terkirim')

@section('content')
    <div class="mx-auto max-w-xl px-4 py-14">
        <div class="rounded-2xl border border-gray-200 bg-white p-8 text-center shadow-sm">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-primary/10">
                <svg class="h-8 w-8 text-primary" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
            </div>

            <h1 class="mt-5 text-2xl font-bold text-gray-900">Permohonan Terkirim</h1>
            @if ($layanan)
                <p class="mt-1 text-gray-600">{{ $layanan }}</p>
            @endif

            <p class="mt-6 text-sm font-medium text-gray-500">Kode Resi Anda</p>
            {{-- Spasi antar blok membuat kode jauh lebih mudah dibaca ulang
                 dari layar maupun dari kertas. --}}
            <p class="mt-1 font-mono text-2xl font-bold tracking-[0.2em] text-primary sm:text-3xl" data-copy-source>{{ $kode }}</p>

            <p class="mt-4 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-900">
                Simpan kode ini. Kode resi adalah satu-satunya cara melacak status permohonan Anda.
            </p>

            <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-center">
                <button type="button" data-copy-button data-copy-value="{{ $kode }}"
                        class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 transition hover:border-primary hover:text-primary focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                    Salin Kode
                </button>
                <button type="button" onclick="window.print()"
                        class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 transition hover:border-primary hover:text-primary focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                    Cetak Bukti
                </button>
                <a href="{{ route('lacak.index') }}"
                   class="rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                    Lacak Permohonan
                </a>
            </div>
        </div>
    </div>
@endsection

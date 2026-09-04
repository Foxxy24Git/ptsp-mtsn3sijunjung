@extends('layouts.app')

@section('title', $layanan->title)

@section('content')
    <div class="mx-auto max-w-5xl px-4 py-10">
        <a href="{{ route('layanan.index') }}" class="text-sm text-gray-500 hover:text-primary">&larr; Kembali ke daftar layanan</a>

        <h1 class="mt-3 text-2xl font-bold text-gray-900 sm:text-3xl">{{ $layanan->title }}</h1>
        @if ($layanan->description)
            <p class="mt-2 text-gray-600">{{ $layanan->description }}</p>
        @endif

        {{-- Di ponsel kartu ringkas tampil lebih dulu (order-first) supaya tombol
             ajukan terlihat tanpa menggulir melewati seluruh syarat. --}}
        <div class="mt-6 grid gap-6 lg:grid-cols-3">
            <aside class="order-first lg:order-last">
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm lg:sticky lg:top-24">
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500">Satuan Kerja</dt>
                            <dd class="text-right font-medium text-gray-900">{{ $layanan->workUnit?->name ?? 'Umum' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500">Waktu Layanan</dt>
                            <dd class="text-right font-medium text-gray-900">{{ $layanan->duration_text ?: 'Menyesuaikan' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500">Biaya</dt>
                            <dd class="text-right font-medium text-primary">{{ $layanan->fee_text }}</dd>
                        </div>
                    </dl>

                    <a href="{{ route('layanan.ajukan', $layanan) }}"
                       class="mt-5 flex w-full items-center justify-center rounded-lg bg-primary px-4 py-3 font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                        Ajukan Permohonan
                    </a>
                </div>
            </aside>

            <div class="lg:col-span-2">
                <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-gray-900">Persyaratan &amp; Alur</h2>
                    <div class="prose mt-3 max-w-none">
                        {!! $layanan->requirements ?: '<p>Rincian persyaratan belum diisi operator.</p>' !!}
                    </div>
                </section>

                @if ($layanan->legal_basis)
                    <section class="mt-5 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                        <h2 class="text-lg font-semibold text-gray-900">Dasar Hukum</h2>
                        <div class="prose mt-3 max-w-none">{!! $layanan->legal_basis !!}</div>
                    </section>
                @endif

                @if ($layanan->fields->where('type', 'document')->whereNotNull('document_path')->isNotEmpty())
                    <section class="mt-5 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                        @include('partials.service-documents', ['layanan' => $layanan])
                    </section>
                @endif
            </div>
        </div>
    </div>
@endsection

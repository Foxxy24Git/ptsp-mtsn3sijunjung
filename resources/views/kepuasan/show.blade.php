@extends('layouts.app')

@section('title', $survei->title)
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags((string) $survei->description) ?: 'Survei kepuasan layanan', 155))

@section('content')
@php
    $skala = \App\Support\SatisfactionScale::LEVELS;
    $awal = \App\Support\SatisfactionScale::DEFAULT;
    $sukses = session('kepuasan_sukses');
@endphp

@include('partials.kepuasan-defs', ['skala' => $skala])

<div class="mx-auto max-w-3xl px-4 py-10">
    <header>
        <p class="text-xs font-semibold uppercase tracking-wide text-primary">Survei Kepuasan Layanan</p>
        <h1 class="mt-2 text-2xl font-bold text-gray-900 sm:text-3xl">{{ $survei->title }}</h1>
        @if ($survei->description)
            <p class="mt-3 text-base leading-relaxed text-gray-600">{{ $survei->description }}</p>
        @endif
    </header>

    @if ($sukses)
        {{-- Formulir sengaja disembunyikan setelah berhasil: menampilkannya lagi
             mengundang kiriman ganda dari satu orang yang sama. --}}
        <div class="mt-8 rounded-2xl border border-green-200 bg-green-50 p-6 text-center" role="status">
            <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-green-600 text-white">
                <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                </svg>
            </span>
            <p class="mt-4 text-lg font-semibold text-green-900">{{ $sukses }}</p>
            <div class="mt-6 flex flex-wrap justify-center gap-3">
                <a href="{{ route('kepuasan.show', $survei->slug) }}"
                   class="rounded-lg border border-green-600 px-4 py-2.5 text-sm font-semibold text-green-800 transition hover:bg-green-100 focus:outline-none focus:ring-2 focus:ring-green-600 focus:ring-offset-2">
                    Isi penilaian lagi
                </a>
                <a href="{{ url('/') }}"
                   class="rounded-lg bg-green-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-green-800 focus:outline-none focus:ring-2 focus:ring-green-600 focus:ring-offset-2">
                    Kembali ke Beranda
                </a>
            </div>
        </div>
    @else
        @if ($errors->any())
            {{-- Ringkasan error di atas, bukan hanya di bawah tiap isian:
                 pada layar ponsel isian yang bermasalah bisa jauh di bawah. --}}
            <div class="mt-8 rounded-xl border border-red-200 bg-red-50 p-4" role="alert">
                <p class="text-sm font-semibold text-red-800">Penilaian belum bisa dikirim:</p>
                <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-red-700">
                    @foreach ($errors->all() as $pesan)
                        <li>{{ $pesan }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('kepuasan.kirim', $survei->slug) }}"
              class="mt-8 space-y-5"
              data-locking-form
              data-kepuasan-levels="{{ json_encode($skala) }}">
            @csrf

            {{-- Honeypot: disembunyikan dari manusia, diisi bot. --}}
            <div class="hidden" aria-hidden="true">
                <label for="website">Website</label>
                <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
            </div>

            @foreach ($survei->aspects as $index => $aspect)
                @include('partials.kepuasan-aspect', [
                    'aspect' => $aspect,
                    'nomor' => $index + 1,
                    'skala' => $skala,
                    'awal' => $awal,
                ])
            @endforeach

            @if ($survei->collect_suggestion)
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
                    <label for="saran" class="block text-base font-semibold text-gray-900">
                        Saran &amp; Masukan <span class="font-normal text-gray-500">(opsional)</span>
                    </label>
                    <p class="mt-1 text-sm text-gray-600">Tuliskan hal yang menurut Anda perlu diperbaiki.</p>
                    <textarea id="saran" name="saran" rows="4" maxlength="1000"
                              class="mt-3 block w-full rounded-lg border border-gray-300 px-3 py-2 text-base text-gray-900 shadow-sm focus:border-primary focus:ring-1 focus:ring-primary"
                              placeholder="Contoh: antrean di loket agak lama pada jam sibuk.">{{ old('saran') }}</textarea>
                    @error('saran')
                        <p class="mt-2 text-sm font-medium text-red-700" role="alert">{{ $message }}</p>
                    @enderror
                </div>
            @endif

            <div class="pt-1">
                <button type="submit" data-submit-button
                        class="flex w-full items-center justify-center gap-2 rounded-lg bg-primary px-6 py-3.5 text-base font-semibold text-white shadow-sm transition hover:brightness-110 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.77 59.77 0 0121.485 12 59.768 59.768 0 013.27 20.876L5.999 12zm0 0h7.5"/>
                    </svg>
                    <span data-submit-label>Kirim Penilaian</span>
                </button>
                <p class="mt-3 text-sm text-gray-500">
                    Penilaian dikirim tanpa nama. Kami tidak menyimpan identitas pengisi survei.
                </p>
            </div>
        </form>
    @endif
</div>
@endsection

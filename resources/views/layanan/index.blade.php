@extends('layouts.app')

@section('title', 'Daftar Layanan')

@section('content')
    <div class="mx-auto max-w-6xl px-4 py-10">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Daftar Layanan Tersedia</h1>
                <p class="mt-1 text-sm text-gray-600">
                    Pilih layanan di bawah untuk melihat rincian syarat atau memulai pengajuan.
                </p>
            </div>

            <form method="GET" action="{{ route('layanan.index') }}" class="sm:w-72">
                @if ($unit)
                    <input type="hidden" name="unit" value="{{ $unit }}">
                @endif
                <label for="q" class="sr-only">Cari layanan</label>
                <input id="q" type="search" name="q" value="{{ $q }}"
                       placeholder="Cari nama layanan..."
                       class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-primary focus:ring-1 focus:ring-primary">
            </form>
        </div>

        {{-- Chip filter memakai query string sehingga tetap bekerja tanpa
             JavaScript dan bisa di-bookmark. --}}
        <div class="mt-6 flex flex-wrap gap-2">
            @php $chipAktif = 'border-primary bg-primary text-white'; @endphp
            @php $chipMati = 'border-gray-300 bg-white text-gray-700 hover:border-primary hover:text-primary'; @endphp

            <a href="{{ route('layanan.index', array_filter(['q' => $q])) }}"
               class="rounded-full border px-4 py-1.5 text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 {{ $unit ? $chipMati : $chipAktif }}">
                Seluruh Satuan Kerja
            </a>
            @foreach ($satuanKerja as $sk)
                <a href="{{ route('layanan.index', array_filter(['unit' => $sk->slug, 'q' => $q])) }}"
                   class="rounded-full border px-4 py-1.5 text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 {{ $unit === $sk->slug ? $chipAktif : $chipMati }}">
                    {{ $sk->name }}
                </a>
            @endforeach
        </div>

        @if ($layanan->isEmpty())
            <p class="mt-10 rounded-xl border border-dashed border-gray-300 bg-white px-6 py-10 text-center text-gray-500">
                Tidak ada layanan yang cocok dengan pilihan Anda.
            </p>
        @else
            <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($layanan as $item)
                    @include('partials.service-card', ['layanan' => $item, 'nomor' => $nomor[$item->id]])
                @endforeach
            </div>
        @endif
    </div>
@endsection

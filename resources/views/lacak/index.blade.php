@extends('layouts.app')

@section('title', 'Lacak Permohonan')

@php
    $warnaStatus = [
        'diajukan' => 'bg-gray-100 text-gray-700',
        'diproses' => 'bg-amber-100 text-amber-800',
        'selesai' => 'bg-green-100 text-green-800',
        'ditolak' => 'bg-red-100 text-red-800',
    ];
@endphp

@section('content')
    <div class="mx-auto max-w-2xl px-4 py-12">
        <h1 class="text-2xl font-bold text-gray-900">Lacak Permohonan</h1>
        <p class="mt-1 text-sm text-gray-600">
            Masukkan kode resi beserta 4 digit terakhir nomor WhatsApp yang Anda daftarkan.
        </p>

        <form method="POST" action="{{ route('lacak.cari') }}" class="mt-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            @csrf
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="sm:col-span-2">
                    <label for="receipt_code" class="block text-sm font-medium text-gray-700">Kode Resi</label>
                    <input id="receipt_code" type="text" name="receipt_code" value="{{ old('receipt_code', $kodeResiAwal ?? '') }}" required
                           placeholder="PTSP-2609-A7K3QX"
                           class="mt-1.5 block w-full rounded-lg border border-gray-300 px-3 py-2.5 font-mono uppercase text-gray-900 shadow-sm focus:border-primary focus:ring-1 focus:ring-primary">
                </div>
                <div>
                    <label for="whatsapp_last4" class="block text-sm font-medium text-gray-700">4 Digit Terakhir WA</label>
                    <input id="whatsapp_last4" type="text" name="whatsapp_last4" value="{{ old('whatsapp_last4') }}" required
                           inputmode="numeric" maxlength="4" placeholder="7890"
                           class="mt-1.5 block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-gray-900 shadow-sm focus:border-primary focus:ring-1 focus:ring-primary">
                </div>
            </div>

            @if ($errors->any())
                <p class="mt-3 text-sm font-medium text-red-600">{{ $errors->first() }}</p>
            @endif

            <button type="submit"
                    class="mt-5 w-full rounded-lg bg-primary px-5 py-3 font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 sm:w-auto">
                Cek Status
            </button>
        </form>

        @if ($permohonan)
            <div class="mt-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="font-mono text-sm text-gray-500">{{ $permohonan->receipt_code }}</p>
                        <h2 class="mt-1 text-lg font-semibold text-gray-900">{{ $permohonan->form->title }}</h2>
                        <p class="mt-0.5 text-sm text-gray-500">
                            Diajukan {{ $permohonan->created_at->translatedFormat('d F Y, H:i') }}
                        </p>
                    </div>
                    {{-- Badge selalu memuat teks, bukan hanya warna: perbedaan
                         "Selesai" dan "Ditolak" tidak boleh bergantung pada
                         kemampuan membedakan merah dan hijau. --}}
                    <span class="rounded-full px-3 py-1 text-sm font-semibold {{ $warnaStatus[$permohonan->status] ?? 'bg-gray-100 text-gray-700' }}">
                        {{ \App\Models\FormSubmission::STATUSES[$permohonan->status] ?? $permohonan->status }}
                    </span>
                </div>

                @if ($permohonan->admin_note)
                    <div class="mt-4 rounded-lg bg-gray-50 px-4 py-3 text-sm text-gray-700">
                        <span class="font-medium text-gray-900">Catatan petugas:</span>
                        {{ $permohonan->admin_note }}
                    </div>
                @endif

                <h3 class="mt-6 text-sm font-semibold text-gray-900">Riwayat</h3>
                <ol class="mt-3 space-y-4 border-l-2 border-gray-200 pl-5">
                    @foreach ($permohonan->statusLogs as $log)
                        <li class="relative">
                            <span class="absolute -left-[27px] top-1.5 h-3 w-3 rounded-full bg-primary ring-4 ring-white"></span>
                            <p class="text-sm font-medium text-gray-900">
                                {{ \App\Models\FormSubmission::STATUSES[$log->status] ?? $log->status }}
                            </p>
                            <p class="text-xs text-gray-500">{{ $log->created_at->translatedFormat('d F Y, H:i') }}</p>
                            @if ($log->note)
                                <p class="mt-1 text-sm text-gray-600">{{ $log->note }}</p>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </div>
        @endif
    </div>
@endsection

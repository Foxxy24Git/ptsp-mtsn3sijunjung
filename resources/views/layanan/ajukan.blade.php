@extends('layouts.app')

@section('title', 'Ajukan '.$layanan->title)

@section('content')
    <div class="mx-auto max-w-3xl px-4 py-10">
        <a href="{{ route('layanan.show', $layanan) }}" class="text-sm text-gray-500 hover:text-primary">&larr; Kembali ke rincian layanan</a>

        <form method="POST" action="{{ route('layanan.kirim', $layanan) }}" enctype="multipart/form-data"
              data-locking-form class="mt-4 space-y-6">
            @csrf

            {{-- Honeypot: tersembunyi dari manusia, tapi terisi oleh bot yang
                 mengisi semua input. Bukan display:none agar tidak dilewati
                 sebagian bot yang sudah pintar. --}}
            <div class="absolute left-[-9999px]" aria-hidden="true">
                <label for="website">Jangan isi kolom ini</label>
                <input id="website" type="text" name="website" tabindex="-1" autocomplete="off">
            </div>

            <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-200 pb-3">
                    <h2 class="flex items-center gap-2 text-base font-bold text-gray-900">
                        <svg class="h-5 w-5 text-primary" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.336a6.721 6.721 0 01-3.17.789 6.721 6.721 0 01-3.168-.789 3.376 3.376 0 016.338 0z" />
                        </svg>
                        Identitas &amp; Kontak Pemohon
                    </h2>
                    <p class="text-xs text-red-500">* Wajib diisi dengan benar</p>
                </div>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="applicant_name" class="block text-sm font-medium text-gray-700">
                            Nama Lengkap Pemohon <span class="text-red-500">*</span>
                        </label>
                        <input id="applicant_name" type="text" name="applicant_name" value="{{ old('applicant_name') }}" required
                               placeholder="Ketik nama lengkap sesuai KTP"
                               class="mt-1.5 block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-gray-900 shadow-sm focus:border-primary focus:ring-1 focus:ring-primary">
                        @error('applicant_name')<p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="layanan_dituju" class="block text-sm font-medium text-gray-700">Layanan yang Dituju</label>
                        <input id="layanan_dituju" type="text" value="{{ $layanan->title }}" readonly
                               class="mt-1.5 block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 font-medium text-primary">
                    </div>

                    <div>
                        <label for="applicant_whatsapp" class="block text-sm font-medium text-gray-700">
                            Nomor WhatsApp / HP Aktif <span class="text-red-500">*</span>
                        </label>
                        <input id="applicant_whatsapp" type="tel" name="applicant_whatsapp" value="{{ old('applicant_whatsapp') }}" required
                               placeholder="Contoh: 081234567890"
                               class="mt-1.5 block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-gray-900 shadow-sm focus:border-primary focus:ring-1 focus:ring-primary">
                        <p class="mt-1 text-xs text-gray-500">Untuk koordinasi &amp; verifikasi saat melacak permohonan.</p>
                        @error('applicant_whatsapp')<p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="applicant_email" class="block text-sm font-medium text-gray-700">
                            Alamat Email Aktif <span class="text-red-500">*</span>
                        </label>
                        <input id="applicant_email" type="email" name="applicant_email" value="{{ old('applicant_email') }}" required
                               placeholder="Contoh: nama@gmail.com"
                               class="mt-1.5 block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-gray-900 shadow-sm focus:border-primary focus:ring-1 focus:ring-primary">
                        <p class="mt-1 text-xs text-gray-500">Dipakai bila petugas perlu menghubungi Anda.</p>
                        @error('applicant_email')<p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </section>

            @if ($layanan->fields->isNotEmpty())
                <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-200 pb-3">
                        <h2 class="flex items-center gap-2 text-base font-bold text-gray-900">
                            <svg class="h-5 w-5 text-primary" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 00-1.883 2.542l.857 6a2.25 2.25 0 002.227 1.932H19.05a2.25 2.25 0 002.227-1.932l.857-6a2.25 2.25 0 00-1.883-2.542m-16.5 0V6A2.25 2.25 0 016 3.75h3.879a1.5 1.5 0 011.06.44l2.122 2.12a1.5 1.5 0 001.06.44H18A2.25 2.25 0 0120.25 9v.776" />
                            </svg>
                            Kelengkapan Berkas &amp; Data Isian
                        </h2>
                        <p class="text-xs text-red-500">* Menandakan kolom wajib diisi</p>
                    </div>

                    <div class="mt-5 space-y-6">
                        @foreach ($layanan->fields as $index => $field)
                            @include('partials.field-input', ['field' => $field, 'nomor' => $index + 1])
                        @endforeach
                    </div>
                </section>
            @endif

            <div class="flex justify-end">
                <button type="submit" data-submit-button
                        class="inline-flex items-center gap-2 rounded-full bg-primary px-7 py-3.5 text-base font-semibold text-white shadow-md transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60">
                    <span data-submit-label>Kirim Permohonan Sekarang</span>
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                    </svg>
                </button>
            </div>
        </form>
    </div>
@endsection

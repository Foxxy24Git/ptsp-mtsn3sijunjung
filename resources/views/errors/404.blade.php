@extends('layouts.app')

@section('title', 'Halaman Tidak Ditemukan')

@section('content')
    <section class="mx-auto flex max-w-xl flex-col items-center px-4 py-24 text-center">
        <p class="text-6xl font-extrabold text-primary">404</p>
        <h1 class="mt-4 text-2xl font-bold text-gray-900">Halaman tidak ditemukan</h1>
        <p class="mt-3 text-gray-600">
            Maaf, halaman yang Anda cari tidak tersedia atau mungkin telah dipindahkan.
        </p>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="{{ url('/') }}" class="rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-white transition hover:opacity-90">Kembali ke Beranda</a>
            <a href="{{ route('posts.index') }}" class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">Lihat Berita</a>
        </div>
    </section>
@endsection

{{-- Section "Layanan PTSP" di beranda. Variabel: $layananUnggulan, $nomorLayanan. --}}
<section class="mx-auto max-w-6xl px-4 py-14">
    <div class="mb-8 flex items-end justify-between" data-reveal>
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Layanan PTSP</h2>
            <p class="mt-1 text-sm text-gray-500">Ajukan permohonan secara daring, pantau statusnya lewat kode resi.</p>
        </div>
        <a href="{{ route('layanan.index') }}" class="hidden text-sm font-medium text-primary hover:underline sm:block">
            Lihat semua &rarr;
        </a>
    </div>

    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3" data-reveal-group>
        @foreach ($layananUnggulan as $item)
            @include('partials.service-card', ['layanan' => $item, 'nomor' => $nomorLayanan[$item->id]])
        @endforeach
    </div>
</section>

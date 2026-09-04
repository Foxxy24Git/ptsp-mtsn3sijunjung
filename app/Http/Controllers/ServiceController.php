<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Models\WorkUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ServiceController extends Controller
{
    /** Katalog kartu layanan, dengan filter satuan kerja & pencarian judul. */
    public function index(Request $request)
    {
        $semua = Form::query()
            ->services()
            ->published()
            ->ordered()
            ->with(['workUnit', 'fields' => fn ($query) => $query->where('type', 'document')])
            ->get();

        // Nomor kartu dihitung dari urutan PENUH, bukan dari hasil yang sedang
        // tersaring, sehingga nomor sebuah layanan tidak berubah saat difilter.
        $nomor = $semua->pluck('id')->flip()->map(fn (int $index): int => $index + 1);

        $unit = $request->query('unit');
        $q = trim((string) $request->query('q', ''));

        $layanan = $semua
            ->when($unit, fn ($daftar) => $daftar->filter(
                fn (Form $item): bool => $item->workUnit?->slug === $unit,
            ))
            ->when($q !== '', fn ($daftar) => $daftar->filter(
                fn (Form $item): bool => Str::contains(Str::lower($item->title), Str::lower($q)),
            ))
            ->values();

        $satuanKerja = WorkUnit::orderBy('sort_order')->orderBy('name')->get();

        return view('layanan.index', compact('layanan', 'nomor', 'satuanKerja', 'unit', 'q'));
    }

    /** Halaman rincian syarat & dasar hukum satu layanan. */
    public function show(Form $form)
    {
        // Form biasa (is_service = false) tetap dilayani rute /form/{slug} lama.
        abort_unless($form->isPublishedService(), 404);

        $form->load(['workUnit', 'fields' => fn ($query) => $query->where('type', 'document')]);

        return view('layanan.show', ['layanan' => $form]);
    }
}

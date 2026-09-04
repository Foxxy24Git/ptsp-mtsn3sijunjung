{{-- Hero beranda. Dipakai saat belum ada slide aktif (kalau ada slide, home.blade.php
     merender partials/slider). Background bisa gambar upload admin (otomatis diberi
     lapisan gelap agar tulisan tetap terbaca) atau gradasi 2 warna. Kalau warna
     gradasi dikosongkan admin, otomatis mengikuti Warna Tema (primary_color) supaya
     tidak pernah tabrakan dengan tema situs. Pola sama dengan partials/zona-integritas. --}}
@php
    $heroBgImageUrl = $settings->hero_bg_image
        ? \Illuminate\Support\Facades\Storage::disk('public')->url($settings->hero_bg_image)
        : null;

    // Hex 3/6 digit -> [r,g,b]. Null kalau bukan hex valid (mis. admin mengetik manual).
    $heroRgb = function (?string $hex): ?array {
        $hex = ltrim((string) $hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        if (strlen($hex) !== 6 || ! ctype_xdigit($hex)) {
            return null;
        }

        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    };

    $heroLuminance = function (?string $hex) use ($heroRgb): float {
        $rgb = $heroRgb($hex);

        return $rgb ? (0.299 * $rgb[0] + 0.587 * $rgb[1] + 0.114 * $rgb[2]) / 255 : 0;
    };

    // Gelapkan warna untuk ujung gradasi default (0.55 = 55% lebih gelap).
    $heroDarken = function (string $hex, float $amount) use ($heroRgb): string {
        $rgb = $heroRgb($hex);
        if (! $rgb) {
            return $hex;
        }

        return sprintf('#%02x%02x%02x', ...array_map(
            fn (int $c): int => (int) round($c * (1 - $amount)),
            $rgb
        ));
    };

    $heroFrom = trim((string) $settings->hero_bg_from) ?: $settings->primary_color;
    $heroTo = trim((string) $settings->hero_bg_to) ?: $heroDarken($heroFrom, 0.55);

    // Gambar selalu diberi lapisan gelap (scrim), jadi teks putih selalu aman.
    // Tanpa gambar: kecerahan rata-rata gradasi menentukan teks gelap/terang.
    $heroIsLight = $heroBgImageUrl
        ? false
        : (($heroLuminance($heroFrom) + $heroLuminance($heroTo)) / 2) > 0.6;

    $heroTextMain = $heroIsLight ? 'text-gray-900' : 'text-white';
    $heroTextMuted = $heroIsLight ? 'text-gray-700' : 'text-white/90';
    $heroBtnClass = $heroIsLight
        ? 'bg-gray-900 text-white hover:bg-gray-800'
        : 'bg-white text-primary hover:bg-gray-100';
@endphp

<section
    class="relative overflow-hidden bg-primary"
    style="{{ $heroBgImageUrl
        ? "background-image: url('{$heroBgImageUrl}'); background-size: cover; background-position: center;"
        : "background: linear-gradient(135deg, {$heroFrom} 0%, {$heroTo} 100%);" }}"
>
    @if ($heroBgImageUrl)
        {{-- Lapisan gelap di atas gambar agar tulisan tetap terbaca (kontras terjamin). --}}
        <div class="absolute inset-0 bg-gradient-to-br from-black/70 via-black/55 to-black/70" aria-hidden="true"></div>
    @endif

    <div class="relative mx-auto max-w-6xl px-4 py-20 text-center" data-reveal>
        <h1 class="text-3xl font-extrabold {{ $heroTextMain }} sm:text-5xl">Selamat Datang di {{ $settings->site_name }}</h1>
        <p class="mx-auto mt-4 max-w-2xl text-base {{ $heroTextMuted }} sm:text-lg">
            Ajukan permohonan layanan secara daring dan pantau statusnya dengan kode resi.
        </p>
        <a href="{{ route('layanan.index') }}" class="mt-8 inline-block rounded-lg px-6 py-3 text-sm font-semibold shadow transition {{ $heroBtnClass }}">
            Lihat Katalog Layanan
        </a>
    </div>
</section>

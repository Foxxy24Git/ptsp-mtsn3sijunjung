{{-- Section "Zona Integritas" di beranda. Background bisa gambar upload admin (dengan
     lapisan gelap otomatis) atau gradasi 2 warna yang juga diatur admin. Warna teks &
     kartu kaca menyesuaikan otomatis (terang/gelap) berdasarkan kecerahan background,
     sama seperti pola header/footer di layouts/app.blade.php.
     Logo, background, label, judul, deskripsi, dan poin semua bisa diedit admin di
     Pengaturan Situs. Tampil/urutan diatur lewat general.home_sections (home.blade.php). --}}
@php
    $ziLogoUrl = $settings->zi_logo ? \Illuminate\Support\Facades\Storage::disk('public')->url($settings->zi_logo) : null;
    $ziBgImageUrl = $settings->zi_bg_image ? \Illuminate\Support\Facades\Storage::disk('public')->url($settings->zi_bg_image) : null;

    $ziLuminance = function (?string $hex): float {
        $hex = ltrim((string) $hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        if (strlen($hex) !== 6 || ! ctype_xdigit($hex)) {
            return 0;
        }
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        return (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
    };

    // Gambar selalu diberi lapisan gelap (scrim) jadi teks putih selalu aman.
    // Tanpa gambar: kecerahan rata-rata gradasi menentukan teks gelap/terang.
    $ziIsLight = $ziBgImageUrl
        ? false
        : (($ziLuminance($settings->zi_bg_from) + $ziLuminance($settings->zi_bg_to)) / 2) > 0.6;

    $ziTextMain = $ziIsLight ? 'text-gray-900' : 'text-white';
    $ziTextMuted = $ziIsLight ? 'text-gray-600' : 'text-white/80';
    $ziEyebrowClass = $ziIsLight ? 'text-gray-500' : 'text-white/70';
    $ziCardBg = $ziIsLight ? 'bg-gray-900/5' : 'bg-white/10';
    $ziCardBorder = $ziIsLight ? 'border-gray-900/10' : 'border-white/15';
    $ziBadgeBg = $ziIsLight ? 'bg-gray-900/10' : 'bg-white/20';
    $ziBtnClass = $ziIsLight ? 'bg-gray-900 text-white hover:bg-gray-800' : 'bg-white text-gray-900 hover:bg-white/90';
@endphp

<section
    class="relative overflow-hidden py-16 sm:py-20"
    style="{{ $ziBgImageUrl ? "background-image: url('{$ziBgImageUrl}'); background-size: cover; background-position: center;" : "background: linear-gradient(135deg, {$settings->zi_bg_from} 0%, {$settings->zi_bg_to} 100%);" }}"
>
    @if ($ziBgImageUrl)
        {{-- Lapisan gelap di atas gambar agar tulisan tetap terbaca (kontras terjamin). --}}
        <div class="absolute inset-0 bg-gradient-to-br from-black/70 via-black/60 to-black/70" aria-hidden="true"></div>
    @endif

    <div class="relative mx-auto grid max-w-6xl items-center gap-10 px-4 lg:grid-cols-2 lg:gap-16" data-reveal>
        {{-- Kiri: label + judul + deskripsi + tautan --}}
        <div>
            @if ($settings->zi_eyebrow)
                <p class="text-sm font-bold uppercase tracking-wider {{ $ziEyebrowClass }}">{{ $settings->zi_eyebrow }}</p>
            @endif

            @if ($settings->zi_heading)
                <h2 class="mt-2 text-3xl font-extrabold leading-tight {{ $ziTextMain }} sm:text-4xl">{{ $settings->zi_heading }}</h2>
            @endif

            @if ($settings->zi_description)
                <p class="mt-4 max-w-xl text-base leading-relaxed {{ $ziTextMuted }}">{{ $settings->zi_description }}</p>
            @endif

            <a
                href="{{ url('/zona-integritas') }}"
                class="mt-8 inline-flex items-center gap-2 rounded-lg px-6 py-3 text-sm font-semibold shadow transition {{ $ziBtnClass }}"
            >
                Selengkapnya
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l5 5a1 1 0 010 1.414l-5 5a1 1 0 01-1.414-1.414L13.586 10H4a1 1 0 110-2h9.586l-3.293-3.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
            </a>
        </div>

        {{-- Kanan: kartu kaca — logo + daftar poin --}}
        <div class="rounded-2xl border {{ $ziCardBorder }} {{ $ziCardBg }} p-6 shadow-2xl backdrop-blur-xl sm:p-8">
            <div class="flex flex-col items-center gap-6 sm:flex-row sm:items-start">
                {{-- Logo ZI (upload admin) atau ikon perisai bawaan --}}
                <div class="flex h-28 w-28 shrink-0 items-center justify-center rounded-xl bg-white/90 p-3 shadow-lg">
                    @if ($ziLogoUrl)
                        <img src="{{ $ziLogoUrl }}" alt="Logo Zona Integritas" class="h-full w-full object-contain">
                    @else
                        <x-heroicon-o-shield-check class="h-14 w-14 text-primary" />
                    @endif
                </div>

                @if (! empty($settings->zi_points))
                    <ul class="w-full space-y-3">
                        @foreach ($settings->zi_points as $point)
                            <li class="flex items-start gap-3 text-sm {{ $ziTextMain }} sm:text-base">
                                <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full {{ $ziBadgeBg }}">
                                    <svg class="h-3 w-3 {{ $ziTextMain }}" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.42l-7.5 7.5a1 1 0 01-1.42 0l-3.5-3.5a1 1 0 111.42-1.42l2.79 2.79 6.79-6.79a1 1 0 011.42 0z" clip-rule="evenodd"/></svg>
                                </span>
                                <span>{{ $point }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</section>

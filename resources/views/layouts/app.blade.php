<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    {{-- Tandai JS aktif sedini mungkin agar animasi reveal tak menyembunyikan konten bagi pengguna tanpa JS. --}}
    <script>document.documentElement.classList.add('js')</script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@hasSection('title')@yield('title') — @endif{{ $settings->site_name }}</title>
    <meta name="description" content="@yield('meta_description', 'Website resmi ' . $settings->site_name)">

    {{-- Ikon tab browser: pakai logo situs kalau sudah di-upload di Pengaturan Situs. --}}
    @if ($logoUrl)
        <link rel="icon" href="{{ $logoUrl }}">
    @endif

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Warna tema + warna header/footer dari GeneralSettings. Teks header/footer
         dipilih otomatis (gelap/terang) sesuai kecerahan warna latar agar terbaca. --}}
    @php
        $hbg = $settings->header_color ?: '#ffffff';
        $hex = ltrim($hbg, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
        $isLight = $luminance > 0.6;
        $hFg = $isLight ? '#111827' : '#ffffff';
        $hMuted = $isLight ? '#4b5563' : 'rgba(255, 255, 255, 0.82)';
        $hHover = $isLight ? 'rgba(0, 0, 0, 0.06)' : 'rgba(255, 255, 255, 0.16)';
        $hBorder = $isLight ? '#e5e7eb' : 'rgba(255, 255, 255, 0.18)';
    @endphp
    <style>:root {
        --color-primary: {{ $settings->primary_color }};
        --header-bg: {{ $hbg }};
        --header-fg: {{ $hFg }};
        --header-muted: {{ $hMuted }};
        --header-hover: {{ $hHover }};
        --header-border: {{ $hBorder }};
    }</style>
</head>
<body class="flex min-h-screen flex-col bg-gray-50 font-sans text-gray-800 antialiased">

    <header class="sticky top-0 z-40 border-b bg-[var(--header-bg)]" style="border-color: var(--header-border);">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3">
            {{-- Identitas situs (logo + nama dari GeneralSettings) --}}
            <a href="{{ url('/') }}" class="flex items-center gap-3">
                @if ($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ $settings->site_name }}" class="h-10 w-auto">
                @else
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary text-lg font-bold text-white">
                        {{ mb_substr($settings->site_name, 0, 1) }}
                    </span>
                @endif
                <span class="text-lg font-bold text-[var(--header-fg)]">{{ $settings->site_name }}</span>
            </a>

            {{-- Grup kanan: nav menu + tautan staf + toggle mobile disatukan dalam
                 satu flex agar menu baru yang ditambahkan selalu nempel di kanan,
                 dekat "Masuk Petugas" — bukan tersebar ke tengah lewat justify-between
                 3 kolom seperti sebelumnya. --}}
            <div class="flex items-center gap-4">
            {{-- Navigasi desktop (dari tabel menus, termasuk submenu) --}}
            <nav class="hidden items-center gap-1 md:flex">
                @foreach ($navMenus as $menu)
                    @php $hasChildren = $menu->children->isNotEmpty(); @endphp
                    <div class="group relative">
                        <a href="{{ $menu->url() }}"
                           class="flex items-center gap-1 rounded-md px-3 py-2 text-sm font-medium text-[var(--header-fg)] hover:bg-[var(--header-hover)]">
                            {{ $menu->label }}
                            @if ($hasChildren)
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.06l3.71-3.83a.75.75 0 111.08 1.04l-4.25 4.39a.75.75 0 01-1.08 0L5.21 8.27a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
                            @endif
                        </a>
                        @if ($hasChildren)
                            <div class="invisible absolute left-0 top-full z-50 min-w-48 rounded-lg border border-gray-100 bg-white py-2 opacity-0 shadow-lg transition duration-150 group-hover:visible group-hover:opacity-100">
                                @foreach ($menu->children as $child)
                                    <a href="{{ $child->url() }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-primary">{{ $child->label }}</a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </nav>

            {{-- Tautan staf: masuk atau ke dashboard petugas (tetap <a> terpisah
                 dari <nav>, bukan bagian dari $navMenus). --}}
            {{-- Arahkan ke /petugas/login (bukan /petugas): kalau yang sedang
                 login adalah admin, mount() Login akan logout sesi salah-panel
                 itu dan menampilkan form login petugas. Kalau langsung ke
                 /petugas (dashboard), admin malah kena 403 dari middleware
                 sebelum sempat melihat form login. --}}
            @php $petugasUser = auth()->user(); @endphp
            <a href="{{ url('/petugas/login') }}"
               class="flex items-center gap-1.5 rounded-md px-2.5 py-2 text-sm font-medium text-[var(--header-fg)] hover:bg-[var(--header-hover)]"
               aria-label="{{ $petugasUser && $petugasUser->role === \App\Models\User::ROLE_PETUGAS ? 'Dashboard Petugas' : 'Masuk Petugas' }}">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                </svg>
                <span class="hidden sm:inline">
                    @if ($petugasUser && $petugasUser->role === \App\Models\User::ROLE_PETUGAS)
                        Dashboard Petugas — {{ $petugasUser->name }}
                    @else
                        Masuk Petugas
                    @endif
                </span>
            </a>

            {{-- Toggle menu mobile (tanpa JS, memakai <details>) --}}
            <details class="relative md:hidden">
                <summary class="flex cursor-pointer list-none items-center rounded-md p-2 text-[var(--header-fg)] hover:bg-[var(--header-hover)] [&::-webkit-details-marker]:hidden">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </summary>
                <div class="absolute right-0 top-full z-50 mt-2 w-64 rounded-lg border border-gray-100 bg-white p-2 shadow-lg">
                    @foreach ($navMenus as $menu)
                        <a href="{{ $menu->url() }}" class="block rounded-md px-3 py-2 text-sm font-medium text-gray-800 hover:bg-gray-50">{{ $menu->label }}</a>
                        @foreach ($menu->children as $child)
                            <a href="{{ $child->url() }}" class="block rounded-md px-3 py-2 pl-6 text-sm text-gray-600 hover:bg-gray-50">— {{ $child->label }}</a>
                        @endforeach
                    @endforeach
                </div>
            </details>
            </div>
        </div>
    </header>

    <main class="flex-1">
        @yield('content')
    </main>

    {{-- Footer memakai warna latar & teks yang sama dengan header (sinkron). --}}
    <footer class="mt-16 border-t bg-[var(--header-bg)] text-[var(--header-fg)]" style="border-color: var(--header-border);">
        <div class="mx-auto grid max-w-6xl gap-8 px-4 py-10 sm:grid-cols-2">
            <div>
                <div class="flex items-center gap-3">
                    @if ($logoUrl)
                        <img src="{{ $logoUrl }}" alt="{{ $settings->site_name }}" class="h-9 w-auto">
                    @endif
                    <span class="text-lg font-bold text-[var(--header-fg)]">{{ $settings->site_name }}</span>
                </div>
                @if ($settings->address)
                    <p class="mt-3 max-w-sm text-sm text-[var(--header-muted)]">{{ $settings->address }}</p>
                @endif
            </div>
            <div class="text-sm text-[var(--header-muted)] sm:text-right">
                @if ($settings->phone)
                    <p>Telepon: <a href="tel:{{ $settings->phone }}" class="hover:text-primary">{{ $settings->phone }}</a></p>
                @endif
                @if ($settings->email)
                    <p>Email: <a href="mailto:{{ $settings->email }}" class="hover:text-primary">{{ $settings->email }}</a></p>
                @endif

                {{-- Ikon sosial media (kecuali WhatsApp yang tampil sebagai tombol mengambang).
                     Tiap ikon hanya muncul bila tautannya diisi di Pengaturan Situs. --}}
                @if ($settings->instagram || $settings->facebook || $settings->youtube || $settings->email)
                    <div class="mt-4 flex gap-3 sm:justify-end">
                        @if ($settings->instagram)
                            <a href="{{ $settings->instagram }}" target="_blank" rel="noopener" aria-label="Instagram"
                               class="text-[var(--header-fg)] transition hover:text-primary">
                                <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.16c3.2 0 3.58.01 4.85.07 1.17.05 1.8.25 2.23.41.56.22.96.48 1.38.9.42.42.68.82.9 1.38.16.42.36 1.06.41 2.23.06 1.27.07 1.65.07 4.85s-.01 3.58-.07 4.85c-.05 1.17-.25 1.8-.41 2.23-.22.56-.48.96-.9 1.38-.42.42-.82.68-1.38.9-.42.16-1.06.36-2.23.41-1.27.06-1.65.07-4.85.07s-3.58-.01-4.85-.07c-1.17-.05-1.8-.25-2.23-.41a3.7 3.7 0 01-1.38-.9 3.7 3.7 0 01-.9-1.38c-.16-.42-.36-1.06-.41-2.23-.06-1.27-.07-1.65-.07-4.85s.01-3.58.07-4.85c.05-1.17.25-1.8.41-2.23.22-.56.48-.96.9-1.38.42-.42.82-.68 1.38-.9.42-.16 1.06-.36 2.23-.41 1.27-.06 1.65-.07 4.85-.07zM12 0C8.74 0 8.33.01 7.05.07 5.78.13 4.9.33 4.14.63c-.79.31-1.46.72-2.12 1.38A5.86 5.86 0 00.63 4.14c-.3.76-.5 1.64-.56 2.9C.01 8.33 0 8.74 0 12s.01 3.67.07 4.95c.06 1.27.26 2.15.56 2.91.31.79.72 1.46 1.38 2.12.66.66 1.33 1.07 2.12 1.38.76.3 1.64.5 2.91.56C8.33 23.99 8.74 24 12 24s3.67-.01 4.95-.07c1.27-.06 2.15-.26 2.91-.56a5.86 5.86 0 002.12-1.38 5.86 5.86 0 001.38-2.12c.3-.76.5-1.64.56-2.91.06-1.28.07-1.69.07-4.95s-.01-3.67-.07-4.95c-.06-1.27-.26-2.15-.56-2.91a5.86 5.86 0 00-1.38-2.12A5.86 5.86 0 0019.86.63c-.76-.3-1.64-.5-2.91-.56C15.67.01 15.26 0 12 0zm0 5.84a6.16 6.16 0 100 12.32 6.16 6.16 0 000-12.32zm0 10.16a4 4 0 110-8 4 4 0 010 8zm6.41-10.4a1.44 1.44 0 11-2.88 0 1.44 1.44 0 012.88 0z"/></svg>
                            </a>
                        @endif
                        @if ($settings->facebook)
                            <a href="{{ $settings->facebook }}" target="_blank" rel="noopener" aria-label="Facebook"
                               class="text-[var(--header-fg)] transition hover:text-primary">
                                <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M24 12.07C24 5.4 18.63 0 12 0S0 5.4 0 12.07C0 18.1 4.39 23.1 10.13 24v-8.44H7.08v-3.49h3.05V9.41c0-3.02 1.79-4.69 4.53-4.69 1.31 0 2.68.24 2.68.24v2.97h-1.51c-1.49 0-1.96.93-1.96 1.89v2.25h3.33l-.53 3.49h-2.8V24C19.61 23.1 24 18.1 24 12.07z"/></svg>
                            </a>
                        @endif
                        @if ($settings->youtube)
                            <a href="{{ $settings->youtube }}" target="_blank" rel="noopener" aria-label="YouTube"
                               class="text-[var(--header-fg)] transition hover:text-primary">
                                <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M23.5 6.19a3.02 3.02 0 00-2.12-2.14C19.5 3.55 12 3.55 12 3.55s-7.5 0-9.38.5A3.02 3.02 0 00.5 6.19C0 8.08 0 12 0 12s0 3.92.5 5.81a3.02 3.02 0 002.12 2.14c1.88.5 9.38.5 9.38.5s7.5 0 9.38-.5a3.02 3.02 0 002.12-2.14C24 15.92 24 12 24 12s0-3.92-.5-5.81zM9.6 15.57V8.43L15.82 12 9.6 15.57z"/></svg>
                            </a>
                        @endif
                        @if ($settings->email)
                            <a href="mailto:{{ $settings->email }}" aria-label="Email"
                               class="text-[var(--header-fg)] transition hover:text-primary">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75l9.75 6.75 9.75-6.75M3.75 5.25h16.5c.83 0 1.5.67 1.5 1.5v10.5c0 .83-.67 1.5-1.5 1.5H3.75c-.83 0-1.5-.67-1.5-1.5V6.75c0-.83.67-1.5 1.5-1.5z"/></svg>
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        </div>
        <div class="border-t py-4" style="border-color: var(--header-border);">
            <p class="text-center text-xs text-[var(--header-muted)]">&copy; {{ date('Y') }} {{ $settings->site_name }}. Seluruh hak cipta dilindungi.</p>
        </div>
    </footer>

    {{-- Tombol WhatsApp mengambang di pojok kanan-bawah. Nomor dinormalisasi:
         buang karakter non-angka, ubah awalan '0' menjadi '62' (kode Indonesia). --}}
    @if ($settings->whatsapp)
        @php
            $waDigits = preg_replace('/\D+/', '', $settings->whatsapp);
            if (str_starts_with($waDigits, '0')) {
                $waDigits = '62' . substr($waDigits, 1);
            }
        @endphp
        <a href="https://wa.me/{{ $waDigits }}" target="_blank" rel="noopener" aria-label="WhatsApp"
           class="fixed bottom-6 right-6 z-50 flex h-14 w-14 items-center justify-center rounded-full bg-[#25D366] text-white shadow-lg transition hover:scale-105 hover:bg-[#1ebe57]">
            <svg class="h-8 w-8" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M17.47 14.38c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.96-.94 1.16-.17.2-.35.22-.65.07-.3-.15-1.26-.46-2.4-1.48-.89-.79-1.49-1.77-1.66-2.07-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.07-.15-.67-1.62-.92-2.22-.24-.58-.49-.5-.67-.51-.17-.01-.37-.01-.57-.01-.2 0-.52.07-.8.37-.27.3-1.05 1.02-1.05 2.49 0 1.47 1.07 2.89 1.22 3.09.15.2 2.1 3.21 5.1 4.5.71.31 1.27.49 1.7.63.71.23 1.36.2 1.87.12.57-.09 1.76-.72 2-1.41.25-.69.25-1.28.17-1.41-.07-.13-.27-.2-.57-.35zM12.05 21.5h-.01a9.42 9.42 0 01-4.8-1.32l-.34-.2-3.57.94.95-3.48-.22-.36a9.4 9.4 0 01-1.44-5.02c0-5.2 4.24-9.44 9.46-9.44 2.53 0 4.9.99 6.69 2.78a9.38 9.38 0 012.76 6.67c0 5.2-4.24 9.44-9.44 9.44zm8.03-17.47A11.36 11.36 0 0012.05.5C5.8.5.72 5.58.72 11.82c0 2 .52 3.95 1.52 5.67L.63 23.5l6.15-1.61a11.34 11.34 0 005.27 1.34h.01c6.25 0 11.33-5.08 11.33-11.32 0-3.03-1.18-5.87-3.31-8z"/></svg>
        </a>
    @endif

    {{-- Lightbox galeri (dipakai bersama oleh halaman /galeri & section galeri di beranda).
         Tombol pemicu: elemen [data-gallery-item] dengan data-type, data-full-src,
         data-embed-url (khusus video), data-title. Logika buka/tutup di app.js. --}}
    <div id="gallery-lightbox" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/90 p-4" data-gallery-lightbox>
        <button type="button" class="absolute right-4 top-4 z-10 flex h-10 w-10 items-center justify-center rounded-full text-white/80 transition hover:bg-white/10 hover:text-white" data-gallery-close aria-label="Tutup">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <div class="w-full max-w-4xl" data-gallery-content></div>
    </div>

    {{-- Modal "Rincian Layanan" (dipakai oleh partials/service-card.blade.php).
         Tombol pemicu: [data-layanan-detail] + <template data-layanan-template>
         berisi konten yang di-clone ke sini saat dibuka. Logika buka/tutup di app.js. --}}
    <div id="layanan-modal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/60 p-4" data-layanan-modal>
        <div class="relative max-h-[90vh] w-full max-w-xl overflow-y-auto rounded-2xl bg-white shadow-xl" data-layanan-panel>
            <button type="button" class="absolute right-4 top-4 z-10 flex h-9 w-9 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-700" data-layanan-close aria-label="Tutup">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
            <div class="p-6 sm:p-7" data-layanan-content></div>
        </div>
    </div>

    @stack('scripts')
</body>
</html>

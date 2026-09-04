{{-- Gradasi wajah kepuasan. Sengaja didefinisikan SEKALI per halaman (bukan di
     dalam partials/kepuasan-face.blade.php) supaya id-nya tidak pernah ganda
     saat satu halaman merender puluhan wajah — id SVG ganda membuat browser
     memakai definisi pertama dan warna wajah jadi salah.
     Butuh: $skala (SatisfactionScale::LEVELS). --}}
<svg width="0" height="0" aria-hidden="true" focusable="false" style="position:absolute">
    <defs>
        @foreach ($skala as $level => $info)
            <linearGradient id="kepuasan-face-{{ $level }}" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="{{ $info['light'] }}"></stop>
                <stop offset="100%" stop-color="{{ $info['color'] }}"></stop>
            </linearGradient>
        @endforeach
    </defs>
</svg>

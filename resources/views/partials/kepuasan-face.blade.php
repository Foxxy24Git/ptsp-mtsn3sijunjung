{{-- Satu wajah kepuasan sebagai SVG (bukan emoji: emoji berubah bentuk tiap
     platform dan tidak bisa diwarnai/dianimasikan lewat CSS).
     Butuh: $level (1–5). Gradasinya dari partials/kepuasan-defs.blade.php. --}}
<svg class="kepuasan__svg" viewBox="0 0 48 48" aria-hidden="true" focusable="false">
    <circle cx="24" cy="24" r="23" fill="url(#kepuasan-face-{{ $level }})"></circle>
    {{-- Kilau tipis di bagian atas supaya wajah terlihat membulat, bukan datar. --}}
    <ellipse cx="24" cy="13" rx="15" ry="7.5" fill="#ffffff" opacity="0.16"></ellipse>

    <g fill="none" stroke="#111827" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round">
        @switch($level)
            @case(1)
                <path d="M11 15 L21.5 20.5"></path>
                <path d="M37 15 L26.5 20.5"></path>
                <path d="M15.5 35.5 Q24 28.5 32.5 35.5"></path>
                @break
            @case(2)
                <path d="M12.5 17.2 L21.5 19.4"></path>
                <path d="M35.5 17.2 L26.5 19.4"></path>
                <path d="M16 34 Q24 30 32 34"></path>
                @break
            @case(3)
                <path d="M12.5 18 L21.5 18"></path>
                <path d="M35.5 18 L26.5 18"></path>
                <path d="M16 34 L32 34"></path>
                @break
            @case(4)
                <path d="M13 18.2 Q17.2 15.8 21.5 17.6"></path>
                <path d="M35 18.2 Q30.8 15.8 26.5 17.6"></path>
                <path d="M15.5 31.5 Q24 38.5 32.5 31.5"></path>
                @break
            @default
                {{-- Mata tersenyum (garis melengkung), mulut terbuka lebar. --}}
                <path d="M12.5 25 Q17.5 19.5 22.5 25"></path>
                <path d="M25.5 25 Q30.5 19.5 35.5 25"></path>
        @endswitch
    </g>

    @if ($level === 5)
        <path d="M14 30 Q24 41.5 34 30 Z" fill="#111827"></path>
    @else
        <circle cx="17.5" cy="{{ $level === 1 ? 25.5 : 26 }}" r="3.4" fill="#111827"></circle>
        <circle cx="30.5" cy="{{ $level === 1 ? 25.5 : 26 }}" r="3.4" fill="#111827"></circle>
    @endif
</svg>

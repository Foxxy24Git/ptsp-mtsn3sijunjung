{{-- Satu sub kepuasan layanan: 5 wajah + slider geser 1–5.
     Butuh: $aspect, $nomor, $skala (LEVELS), $awal (nilai awal slider).

     Slider-nya <input type="range"> asli, bukan tiruan div+drag: tanpa JS pun
     masih bisa digeser dan terkirim, bisa dijalankan pakai panah keyboard,
     dan dibacakan pembaca layar. Tombol wajah hanya jalur pintas ketuk —
     dirender disabled dan baru diaktifkan app.js. --}}
@php
    $nilai = (int) old('aspek.'.$aspect->id, $awal);
    $nilai = array_key_exists($nilai, $skala) ? $nilai : $awal;
    $aktif = $skala[$nilai];
    $bantuanId = 'aspek-'.$aspect->id.'-bantuan';
@endphp

<div class="kepuasan rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6"
     data-kepuasan
     style="--kepuasan-color: {{ $aktif['color'] }}; --kepuasan-text: {{ $aktif['text'] }};">

    <div class="flex items-start gap-3">
        <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-gray-100 text-xs font-bold text-gray-600">
            {{ $nomor }}
        </span>
        <div class="min-w-0">
            <label for="aspek-{{ $aspect->id }}" class="block text-base font-semibold text-gray-900">
                {{ $aspect->label }}
            </label>
            @if ($aspect->help_text)
                <p id="{{ $bantuanId }}" class="mt-1 text-sm text-gray-600">{{ $aspect->help_text }}</p>
            @endif
        </div>
    </div>

    <div class="kepuasan__faces">
        @foreach ($skala as $level => $info)
            <button type="button"
                    class="kepuasan__face {{ $level === $nilai ? 'is-active' : '' }}"
                    data-kepuasan-face="{{ $level }}"
                    aria-label="Beri nilai {{ $level }} dari 5 — {{ $info['label'] }}"
                    aria-pressed="{{ $level === $nilai ? 'true' : 'false' }}"
                    disabled>
                @include('partials.kepuasan-face', ['level' => $level])
                <span class="kepuasan__face-label">{{ $info['short'] }}</span>
            </button>
        @endforeach
    </div>

    <input type="range"
           class="kepuasan__range"
           data-kepuasan-range
           id="aspek-{{ $aspect->id }}"
           name="aspek[{{ $aspect->id }}]"
           min="{{ array_key_first($skala) }}"
           max="{{ array_key_last($skala) }}"
           step="1"
           value="{{ $nilai }}"
           aria-valuetext="{{ $aktif['label'] }}"
           @if ($aspect->help_text) aria-describedby="{{ $bantuanId }}" @endif>

    <p class="kepuasan__value">
        <span class="text-gray-500">Penilaian Anda:</span>
        <strong data-kepuasan-output>{{ $aktif['label'] }}</strong>
    </p>

    @error('aspek.'.$aspect->id)
        <p class="mt-2 text-sm font-medium text-red-700" role="alert">{{ $message }}</p>
    @enderror
</div>

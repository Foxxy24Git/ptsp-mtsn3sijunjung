{{--
    Rekap hasil satu survei Kepuasan Layanan. Variabel dari
    HasilSatisfactionSurvey::getViewData(): $survei, $rekap, $skala,
    $jawaban, $batas.

    Styling ditulis mandiri (bukan utility Tailwind) dengan sengaja — sama
    alasannya dengan resources/views/filament/form-submission-detail.blade.php:
    CSS bawaan Filament tidak memindai file Blade kustom, jadi class Tailwind
    di sini tidak pernah ikut ter-compile. var(--primary-500) dst. sudah
    disediakan Filament di root halaman.
--}}
<x-filament-panels::page>
    @include('partials.kepuasan-defs', ['skala' => $skala])

    <style>
        .kh { font-size: .875rem; color: #111827; }
        .kh-section { margin-top: 1.75rem; }
        .kh-section:first-child { margin-top: 0; }
        .kh-section-title {
            font-size: .6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em;
            color: #6b7280; margin-bottom: .75rem;
        }

        .kh-card { border: 1px solid #e5e7eb; border-radius: .75rem; background: #ffffff; padding: 1rem 1.125rem; }

        .kh-face { display: inline-flex; flex-shrink: 0; }
        .kh-face .kepuasan__svg { display: block; width: 100%; height: 100%; }
        .kh-face--lg { width: 3.5rem; height: 3.5rem; }
        .kh-face--sm { width: 1.5rem; height: 1.5rem; }

        /* Ringkasan atas */
        .kh-summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: .875rem; }
        .kh-stat-label {
            font-size: .6875rem; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: #9ca3af;
        }
        .kh-stat-value { margin-top: .25rem; font-size: 1.75rem; font-weight: 700; line-height: 1.1; color: #111827; }
        .kh-stat-sub { margin-top: .1875rem; font-size: .8125rem; color: #6b7280; }
        .kh-stat-row { display: flex; align-items: center; gap: .875rem; }

        /* Baris per sub kepuasan layanan */
        .kh-aspect { border: 1px solid #e5e7eb; border-radius: .75rem; background: #ffffff; padding: 1rem 1.125rem; }
        .kh-aspect + .kh-aspect { margin-top: .75rem; }
        .kh-aspect-head { display: flex; flex-wrap: wrap; align-items: baseline; justify-content: space-between; gap: .5rem 1rem; }
        .kh-aspect-name { font-size: .9375rem; font-weight: 700; color: #111827; }
        .kh-aspect-score { font-size: .875rem; font-weight: 700; color: var(--kh-fg, #6b7280); white-space: nowrap; }
        .kh-aspect-score span { font-weight: 500; color: #6b7280; }

        .kh-bar { position: relative; height: .625rem; margin-top: .75rem; border-radius: 9999px; background: #f3f4f6; overflow: hidden; }
        .kh-bar-fill {
            position: absolute; inset: 0 auto 0 0; border-radius: 9999px;
            background: var(--kh-bar, #94a3b8);
        }

        .kh-dist { display: grid; grid-template-columns: repeat(auto-fit, minmax(92px, 1fr)); gap: .5rem; margin-top: .875rem; }
        .kh-dist-item { display: flex; align-items: center; gap: .4375rem; font-size: .8125rem; color: #4b5563; }
        .kh-dist-count { font-weight: 700; color: #111827; }

        /* Tabel jawaban mentah */
        .kh-table-wrap { overflow-x: auto; border: 1px solid #e5e7eb; border-radius: .75rem; background: #ffffff; }
        .kh-table { width: 100%; border-collapse: collapse; font-size: .8125rem; }
        .kh-table th, .kh-table td { padding: .625rem .75rem; text-align: left; vertical-align: top; white-space: nowrap; }
        .kh-table th {
            font-size: .6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em;
            color: #6b7280; background: #f9fafb; border-bottom: 1px solid #e5e7eb;
        }
        .kh-table th.kh-th-aspect { max-width: 11rem; overflow: hidden; text-overflow: ellipsis; }
        .kh-table tbody tr + tr td { border-top: 1px solid #f3f4f6; }
        .kh-table td.kh-td-saran { white-space: normal; min-width: 16rem; color: #374151; }

        .kh-score {
            display: inline-flex; align-items: center; gap: .375rem; padding: .1875rem .5rem;
            border-radius: 9999px; font-size: .75rem; font-weight: 700; white-space: nowrap;
            color: var(--kh-fg); background: color-mix(in srgb, var(--kh-bg) 16%, transparent);
        }
        .kh-empty { color: #9ca3af; font-style: italic; }
        .kh-note { margin-top: .625rem; font-size: .75rem; color: #6b7280; }

        .kh-blank { border: 1px dashed #d1d5db; border-radius: .75rem; padding: 2rem 1rem; text-align: center; color: #6b7280; }

        /* Dark mode: Filament memasang class "dark" pada <html>. */
        html.dark .kh { color: #f3f4f6; }
        html.dark .kh-section-title { color: #9ca3af; }
        html.dark .kh-card,
        html.dark .kh-aspect,
        html.dark .kh-table-wrap { background: rgba(255, 255, 255, .03); border-color: rgba(255, 255, 255, .1); }
        html.dark .kh-stat-label { color: #6b7280; }
        html.dark .kh-stat-value,
        html.dark .kh-aspect-name,
        html.dark .kh-dist-count { color: #f9fafb; }
        html.dark .kh-stat-sub,
        html.dark .kh-dist-item { color: #9ca3af; }
        html.dark .kh-bar { background: rgba(255, 255, 255, .08); }
        html.dark .kh-table th { color: #9ca3af; background: rgba(255, 255, 255, .04); border-color: rgba(255, 255, 255, .1); }
        html.dark .kh-table tbody tr + tr td { border-color: rgba(255, 255, 255, .07); }
        html.dark .kh-table td.kh-td-saran { color: #d1d5db; }
        html.dark .kh-empty { color: #6b7280; }
        html.dark .kh-blank { border-color: rgba(255, 255, 255, .16); color: #9ca3af; }
        html.dark .kh-note { color: #9ca3af; }
    </style>

    <div class="kh">
        @php
            $rerata = $rekap['average'];
            $tingkat = $rerata === null ? null : \App\Support\SatisfactionScale::round($rerata);
        @endphp

        <div class="kh-section">
            <div class="kh-section-title">Ringkasan</div>
            <div class="kh-summary">
                <div class="kh-card">
                    <div class="kh-stat-label">Responden</div>
                    <div class="kh-stat-value">{{ number_format($rekap['responden'], 0, ',', '.') }}</div>
                    <div class="kh-stat-sub">{{ number_format($rekap['jawaban'], 0, ',', '.') }} penilaian terkumpul</div>
                </div>
                <div class="kh-card">
                    <div class="kh-stat-label">Rata-rata Keseluruhan</div>
                    <div class="kh-stat-value">{{ $rerata === null ? '—' : number_format($rerata, 2, ',', '.') }}<span style="font-size:1rem;font-weight:500;color:#9ca3af;"> / 5</span></div>
                    <div class="kh-stat-sub">{{ \App\Support\SatisfactionScale::percentage($rerata) }}% dari nilai maksimum</div>
                </div>
                <div class="kh-card">
                    <div class="kh-stat-label">Mutu Layanan</div>
                    <div class="kh-stat-row" style="margin-top:.375rem;">
                        @if ($tingkat)
                            <span class="kh-face kh-face--lg">
                                @include('partials.kepuasan-face', ['level' => $tingkat])
                            </span>
                        @endif
                        <div>
                            <div style="font-size:1.0625rem;font-weight:700;color:{{ $tingkat ? $skala[$tingkat]['text'] : '#6b7280' }};">
                                {{ $rekap['grade'] }}
                            </div>
                            <div class="kh-stat-sub">Berdasarkan seluruh penilaian masuk</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="kh-section">
            <div class="kh-section-title">Rata-rata per Sub Kepuasan Layanan</div>

            @forelse ($rekap['aspects'] as $baris)
                @php
                    $avg = $baris['average'];
                    $lvl = $avg === null ? null : \App\Support\SatisfactionScale::round($avg);
                    $pct = \App\Support\SatisfactionScale::percentage($avg);
                @endphp
                <div class="kh-aspect" style="--kh-fg: {{ $lvl ? $skala[$lvl]['text'] : '#6b7280' }}; --kh-bar: {{ $lvl ? $skala[$lvl]['color'] : '#cbd5e1' }};">
                    <div class="kh-aspect-head">
                        <div class="kh-aspect-name">{{ $baris['aspect']->label }}</div>
                        <div class="kh-aspect-score">
                            {{ $avg === null ? '—' : number_format($avg, 2, ',', '.') }}
                            <span>/ 5 &middot; {{ $avg === null ? 'Belum ada penilaian' : \App\Support\SatisfactionScale::grade($avg) }}</span>
                        </div>
                    </div>

                    <div class="kh-bar" role="img"
                         aria-label="Rata-rata {{ $baris['aspect']->label }}: {{ $avg === null ? 'belum ada penilaian' : number_format($avg, 2, ',', '.').' dari 5' }}">
                        <div class="kh-bar-fill" style="width: {{ $pct }}%;"></div>
                    </div>

                    <div class="kh-dist">
                        @foreach ($skala as $level => $info)
                            <div class="kh-dist-item">
                                <span class="kh-face kh-face--sm">
                                    @include('partials.kepuasan-face', ['level' => $level])
                                </span>
                                <span class="kh-dist-count">{{ $baris['distribution'][$level] }}</span>
                                <span>{{ $info['short'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="kh-blank">Survei ini belum punya sub kepuasan layanan. Tambahkan lewat tab "Ubah Survei".</div>
            @endforelse
        </div>

        <div class="kh-section">
            <div class="kh-section-title">Jawaban Masuk</div>

            @if ($jawaban->isEmpty())
                <div class="kh-blank">Belum ada penilaian yang masuk untuk survei ini.</div>
            @else
                <div class="kh-table-wrap">
                    <table class="kh-table">
                        <thead>
                            <tr>
                                <th>Waktu</th>
                                @foreach ($rekap['aspects'] as $baris)
                                    <th class="kh-th-aspect" title="{{ $baris['aspect']->label }}">{{ $baris['aspect']->label }}</th>
                                @endforeach
                                <th>Saran &amp; Masukan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($jawaban as $response)
                                <tr>
                                    <td>{{ $response->created_at->translatedFormat('d M Y, H:i') }}</td>
                                    @foreach ($rekap['aspects'] as $baris)
                                        @php $nilai = $response->scoreFor($baris['aspect']->id); @endphp
                                        <td>
                                            @if ($nilai)
                                                <span class="kh-score"
                                                      style="--kh-fg: {{ $skala[$nilai]['text'] }}; --kh-bg: {{ $skala[$nilai]['color'] }};">
                                                    {{ $nilai }} &middot; {{ $skala[$nilai]['short'] }}
                                                </span>
                                            @else
                                                <span class="kh-empty">—</span>
                                            @endif
                                        </td>
                                    @endforeach
                                    <td class="kh-td-saran">
                                        {{ $response->suggestion ?: '' }}
                                        @unless ($response->suggestion)
                                            <span class="kh-empty">—</span>
                                        @endunless
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($rekap['responden'] > $batas)
                    <p class="kh-note">Menampilkan {{ $batas }} jawaban terbaru dari {{ number_format($rekap['responden'], 0, ',', '.') }} responden.</p>
                @endif
            @endif
        </div>
    </div>
</x-filament-panels::page>

{{--
    Detail satu permohonan, dipakai ulang di tiga tempat: panel Permohonan
    admin, tab Permohonan di dalam Layanan (admin), dan panel Permohonan
    petugas. Variabel: $submission.

    Styling ditulis mandiri (bukan utility Tailwind) dengan sengaja: panel
    Filament di proyek ini memakai CSS bawaan Filament yang tidak memindai
    file Blade kustom seperti ini, sehingga class Tailwind apa pun yang
    ditulis di sini tidak pernah ikut ter-compile dan akan render polos
    tanpa warna/grid/font — persis bug yang membuat tampilan lama rata
    tanpa hierarki. var(--primary-500) dst. sudah didefinisikan Filament
    di root halaman, jadi warna aksen di sini otomatis ikut warna panel
    yang sedang aktif (Amber di admin, Sky di petugas) tanpa hardcode.
--}}
@php
    $statusMeta = [
        'diajukan' => ['label' => 'Diajukan', 'fg' => '#6b7280', 'bg' => 'rgba(107,114,128,.14)'],
        'diproses' => ['label' => 'Diproses', 'fg' => '#d97706', 'bg' => 'rgba(217,119,6,.14)'],
        'selesai' => ['label' => 'Selesai', 'fg' => '#16a34a', 'bg' => 'rgba(22,163,74,.14)'],
        'ditolak' => ['label' => 'Ditolak', 'fg' => '#dc2626', 'bg' => 'rgba(220,38,38,.14)'],
    ];
    $status = $statusMeta[$submission->status] ?? ['label' => $submission->status, 'fg' => '#6b7280', 'bg' => 'rgba(107,114,128,.14)'];
@endphp

<style>
    .pd { font-size: .875rem; line-height: 1.5; color: #111827; }
    .pd-section { margin-top: 1.5rem; }
    .pd-section:first-child { margin-top: 0; }
    .pd-section-title {
        font-size: .6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em;
        color: #6b7280; margin-bottom: .75rem;
    }

    .pd-head {
        display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between;
        gap: .75rem 1.5rem; padding-bottom: 1.25rem; border-bottom: 1px solid #e5e7eb;
    }
    .pd-resi { display: flex; align-items: center; gap: .375rem; }
    .pd-resi code {
        font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 1.0625rem;
        font-weight: 700; letter-spacing: .02em; color: #111827;
    }
    .pd-copy {
        display: inline-flex; align-items: center; justify-content: center;
        width: 1.75rem; height: 1.75rem; border-radius: .375rem; border: 1px solid #e5e7eb;
        color: #6b7280; background: transparent; cursor: pointer; padding: 0;
    }
    .pd-copy:hover { background: #f3f4f6; color: #111827; }
    .pd-copy:focus-visible { outline: 2px solid var(--primary-500, #2563eb); outline-offset: 1px; }
    .pd-copy .pd-icon-check { display: none; color: #16a34a; }
    .pd-copy.is-copied .pd-icon-copy { display: none; }
    .pd-copy.is-copied .pd-icon-check { display: inline-block; }
    .pd-meta { margin-top: .3rem; font-size: .8125rem; color: #6b7280; }

    .pd-badge {
        display: inline-flex; align-items: center; gap: .4375rem; padding: .3125rem .75rem;
        border-radius: 9999px; font-size: .75rem; font-weight: 700; white-space: nowrap;
        color: var(--pd-fg); background: var(--pd-bg);
    }
    .pd-badge::before { content: ''; width: .4rem; height: .4rem; border-radius: 9999px; background: currentColor; flex-shrink: 0; }

    .pd-note {
        margin-top: .875rem; padding: .75rem .875rem; border-radius: .5rem;
        background: #f9fafb; border: 1px solid #e5e7eb; font-size: .8125rem; color: #374151;
    }
    .pd-note strong { color: #111827; }

    .pd-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 1rem 1.5rem; }
    .pd-stack { display: flex; flex-direction: column; gap: 1rem; }
    .pd-field-label {
        font-size: .6875rem; font-weight: 600; text-transform: uppercase; letter-spacing: .04em;
        color: #9ca3af; margin-bottom: .25rem;
    }
    .pd-field-value { font-size: .875rem; color: #111827; word-break: break-word; }
    /* pre-line HANYA di sini (bukan di .pd-field-value): kalau dipasang di
       div pembungkus yang isinya blok kondisional Blade bercabang, spasi
       & baris baru dari indentasi kode ikut "dilestarikan" sebagai baris
       kosong sungguhan — itulah penyebab WhatsApp/Email dulu turun jauh
       dari labelnya. Dibatasi ke elemen yang isinya cuma nilai itu sendiri. */
    .pd-multiline { white-space: pre-line; }
    .pd-empty { color: #9ca3af; font-style: italic; }

    .pd-link {
        color: var(--primary-600, #2563eb); text-decoration: none;
        display: inline-flex; align-items: center; gap: .375rem;
    }
    .pd-link:hover { text-decoration: underline; }
    .pd-link:focus-visible { outline: 2px solid var(--primary-500, #2563eb); outline-offset: 2px; border-radius: .25rem; }
    .pd-icon { width: 1rem; height: 1rem; flex-shrink: 0; }

    .pd-file-btn {
        display: inline-flex; align-items: center; gap: .4375rem; padding: .4375rem .8125rem;
        border-radius: .5rem; border: 1px solid var(--primary-500, #2563eb);
        color: var(--primary-600, #2563eb); font-size: .8125rem; font-weight: 600; text-decoration: none;
    }
    .pd-file-btn:hover { background: color-mix(in srgb, var(--primary-500, #2563eb) 12%, transparent); }
    .pd-file-btn:focus-visible { outline: 2px solid var(--primary-500, #2563eb); outline-offset: 2px; }

    .pd-timeline { list-style: none; margin: 0; padding: 0; }
    .pd-timeline li { position: relative; padding: 0 0 1.25rem 1.5rem; border-left: 2px solid #e5e7eb; }
    .pd-timeline li:last-child { border-color: transparent; padding-bottom: 0; }
    .pd-timeline li::before {
        content: ''; position: absolute; left: -.4375rem; top: .1875rem;
        width: .75rem; height: .75rem; border-radius: 9999px;
        background: var(--primary-500, #2563eb); box-shadow: 0 0 0 3px #ffffff;
    }
    .pd-tl-label { font-size: .8125rem; font-weight: 700; color: #111827; }
    .pd-tl-time { font-size: .75rem; color: #9ca3af; margin-top: .0625rem; }
    .pd-tl-note { font-size: .8125rem; color: #4b5563; margin-top: .3125rem; }

    /* Dark mode: Filament menambah class "dark" pada <html>, bukan pada modal ini saja. */
    html.dark .pd { color: #f3f4f6; }
    html.dark .pd-section-title { color: #9ca3af; }
    html.dark .pd-head { border-color: rgba(255, 255, 255, .08); }
    html.dark .pd-resi code { color: #f9fafb; }
    html.dark .pd-copy { border-color: rgba(255, 255, 255, .14); color: #9ca3af; }
    html.dark .pd-copy:hover { background: rgba(255, 255, 255, .06); color: #f9fafb; }
    html.dark .pd-meta { color: #9ca3af; }
    html.dark .pd-note { background: rgba(255, 255, 255, .04); border-color: rgba(255, 255, 255, .08); color: #d1d5db; }
    html.dark .pd-note strong { color: #f9fafb; }
    html.dark .pd-field-label { color: #6b7280; }
    html.dark .pd-field-value { color: #f3f4f6; }
    html.dark .pd-empty { color: #6b7280; }
    html.dark .pd-timeline li { border-color: rgba(255, 255, 255, .14); }
    html.dark .pd-timeline li::before { box-shadow: 0 0 0 3px #1e1e27; }
    html.dark .pd-tl-label { color: #f9fafb; }
    html.dark .pd-tl-time { color: #6b7280; }
    html.dark .pd-tl-note { color: #9ca3af; }
</style>

<div class="pd">
    {{-- Header: kode resi (bisa disalin), layanan, tanggal, status saat ini --}}
    <div class="pd-head">
        <div>
            <div class="pd-resi">
                <code>{{ $submission->receipt_code }}</code>
                <button type="button" class="pd-copy" aria-label="Salin kode resi" title="Salin kode resi"
                        data-copy="{{ $submission->receipt_code }}"
                        onclick="navigator.clipboard.writeText(this.dataset.copy); this.classList.add('is-copied'); clearTimeout(this._t); this._t = setTimeout(() => this.classList.remove('is-copied'), 1500)">
                    <svg class="pd-icon pd-icon-copy" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5" />
                    </svg>
                    <svg class="pd-icon pd-icon-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                </button>
            </div>
            <p class="pd-meta">{{ $submission->form->title }} &middot; Diajukan {{ $submission->created_at->translatedFormat('d F Y, H:i') }}</p>
        </div>
        <span class="pd-badge" style="--pd-fg: {{ $status['fg'] }}; --pd-bg: {{ $status['bg'] }};">{{ $status['label'] }}</span>
    </div>

    @if ($submission->admin_note)
        <div class="pd-note"><strong>Catatan terakhir:</strong> {{ $submission->admin_note }}</div>
    @endif

    <div class="pd-section">
        <div class="pd-section-title">Identitas Pemohon</div>
        <div class="pd-grid">
            <div>
                <div class="pd-field-label">Nama</div>
                <div class="pd-field-value">{{ $submission->applicant_name ?: '—' }}</div>
            </div>
            <div>
                <div class="pd-field-label">WhatsApp</div>
                <div class="pd-field-value">
                    @if ($submission->applicant_whatsapp)
                        <a href="https://wa.me/{{ $submission->applicant_whatsapp }}" target="_blank" rel="noopener" class="pd-link">
                            <svg class="pd-icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.47 14.38c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.96-.94 1.16-.17.2-.35.22-.65.07-.3-.15-1.26-.46-2.4-1.48-.89-.79-1.49-1.77-1.66-2.07-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.07-.15-.67-1.62-.92-2.22-.24-.58-.49-.5-.67-.51-.17-.01-.37-.01-.57-.01-.2 0-.52.07-.8.37-.27.3-1.05 1.02-1.05 2.49 0 1.47 1.07 2.89 1.22 3.09.15.2 2.1 3.21 5.1 4.5.71.31 1.27.49 1.7.63.71.23 1.36.2 1.87.12.57-.09 1.76-.72 2-1.41.25-.69.25-1.28.17-1.41-.07-.13-.27-.2-.57-.35z" /></svg>
                            {{ $submission->applicant_whatsapp }}
                        </a>
                    @else
                        <span class="pd-empty">—</span>
                    @endif
                </div>
            </div>
            <div>
                <div class="pd-field-label">Email</div>
                <div class="pd-field-value">
                    @if ($submission->applicant_email)
                        <a href="mailto:{{ $submission->applicant_email }}" class="pd-link">
                            <svg class="pd-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75l9.75 6.75 9.75-6.75M3.75 5.25h16.5c.83 0 1.5.67 1.5 1.5v10.5c0 .83-.67 1.5-1.5 1.5H3.75c-.83 0-1.5-.67-1.5-1.5V6.75c0-.83.67-1.5 1.5-1.5z" />
                            </svg>
                            {{ $submission->applicant_email }}
                        </a>
                    @else
                        <span class="pd-empty">—</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="pd-section">
        <div class="pd-section-title">Isian &amp; Berkas</div>
        <div class="pd-stack">
            @foreach ($submission->form->fields as $field)
                @php $value = $submission->data[$field->id] ?? null; @endphp
                <div>
                    <div class="pd-field-label">{{ $field->label }}</div>
                    <div class="pd-field-value">
                        @if ($field->type === 'file' && $value)
                            <a href="{{ route('permohonan.berkas', ['submission' => $submission->id, 'field' => $field->id]) }}" class="pd-file-btn">
                                <svg class="pd-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                </svg>
                                Unduh Berkas
                            </a>
                        @elseif (is_array($value))
                            {{ $value === [] ? '—' : implode(', ', $value) }}
                        @elseif ($value !== null && $value !== '')
                            <span class="pd-multiline">{{ $value }}</span>
                        @else
                            <span class="pd-empty">—</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="pd-section">
        <div class="pd-section-title">Riwayat Status</div>
        <ol class="pd-timeline">
            @foreach ($submission->statusLogs as $log)
                <li>
                    <div class="pd-tl-label">{{ \App\Models\FormSubmission::STATUSES[$log->status] ?? $log->status }}</div>
                    <div class="pd-tl-time">{{ $log->created_at->translatedFormat('d F Y, H:i') }}</div>
                    @if ($log->note)
                        <div class="pd-tl-note">{{ $log->note }}</div>
                    @endif
                </li>
            @endforeach
        </ol>
    </div>
</div>

{{-- Satu baris pertanyaan pada blok "Kelengkapan Berkas & Data Isian".
     Variabel: $field (FormField), $nomor (int).
     Tipe 'document': bukan isian pemohon, melainkan dokumen yang diunggah
     ADMIN untuk diunduh pemohon (mis. blanko keluar/masuk siswa) -- makanya
     tidak punya badge wajib/opsional & tidak pernah divalidasi/disimpan
     (lihat DynamicFieldRules & ApplicationController::store). --}}
@php
    $name = 'field_'.$field->id;
    $inputClass = 'mt-2 block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-gray-900 shadow-sm focus:border-primary focus:ring-1 focus:ring-primary';
@endphp

<div>
    <label for="{{ $name }}" class="flex items-center gap-2 text-sm font-semibold text-gray-800">
        @if ($field->type === 'file')
            <svg class="h-4 w-4 text-primary" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25M9 16.5v.75m3-3v3M15 12v5.25m-4.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
            </svg>
        @elseif ($field->type === 'document')
            <svg class="h-4 w-4 text-primary" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
            </svg>
        @else
            <svg class="h-4 w-4 text-primary" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
            </svg>
        @endif

        <span>{{ $nomor }}. {{ $field->label }}</span>

        @if ($field->type === 'document')
            {{-- bukan isian: tidak ada badge wajib/opsional --}}
        @elseif ($field->required)
            <span class="text-red-500" aria-hidden="true">*</span>
            <span class="sr-only">wajib diisi</span>
        @else
            {{-- Ditulis eksplisit: ketiadaan bintang merah saja terlalu ambigu. --}}
            <span class="text-xs font-normal text-gray-400">(Opsional)</span>
        @endif
    </label>

    @if ($field->type === 'text')
        <input id="{{ $name }}" type="text" name="{{ $name }}" value="{{ old($name) }}"
               @required($field->required)
               placeholder="Masukkan {{ Str::lower($field->label) }}..."
               class="{{ $inputClass }}">

    @elseif ($field->type === 'textarea')
        <textarea id="{{ $name }}" name="{{ $name }}" rows="4" @required($field->required)
                  placeholder="Masukkan {{ Str::lower($field->label) }}..."
                  class="{{ $inputClass }}">{{ old($name) }}</textarea>

    @elseif ($field->type === 'date')
        <input id="{{ $name }}" type="date" name="{{ $name }}" value="{{ old($name) }}"
               @required($field->required) class="{{ $inputClass }}">

    @elseif ($field->type === 'number')
        <input id="{{ $name }}" type="number" name="{{ $name }}" value="{{ old($name) }}"
               @required($field->required) class="{{ $inputClass }}">

    @elseif ($field->type === 'select')
        <select id="{{ $name }}" name="{{ $name }}" @required($field->required) class="{{ $inputClass }}">
            <option value="">&mdash; Pilih &mdash;</option>
            @foreach (($field->options ?? []) as $opt)
                <option value="{{ $opt }}" @selected(old($name) === $opt)>{{ $opt }}</option>
            @endforeach
        </select>

    @elseif ($field->type === 'checkbox')
        <div class="mt-2 space-y-2">
            @foreach (($field->options ?? []) as $opt)
                <label class="flex items-center gap-3 text-gray-700">
                    <input type="checkbox" name="{{ $name }}[]" value="{{ $opt }}"
                           @checked(in_array($opt, (array) old($name, [])))
                           class="h-5 w-5 rounded border-gray-300 text-primary focus:ring-primary">
                    {{ $opt }}
                </label>
            @endforeach
        </div>

    @elseif ($field->type === 'file')
        {{-- Input file asli selalu ada di DOM sehingga tetap bisa dioperasikan
             keyboard & pembaca layar; JavaScript hanya menambah tarik-lepas. --}}
        <div data-dropzone
             class="dropzone relative mt-2 rounded-xl border-2 border-dashed border-gray-300 bg-primary/5 px-6 py-8 text-center transition">
            <input id="{{ $name }}" type="file" name="{{ $name }}" @required($field->required)
                   accept=".pdf,.jpg,.jpeg,.png"
                   class="dropzone__input block w-full text-sm text-gray-600">

            <div data-dropzone-idle class="dropzone__idle pointer-events-none">
                <svg class="mx-auto h-8 w-8 text-primary" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" />
                </svg>
                <p class="mt-2 text-sm font-semibold text-gray-800">Klik atau Tarik File ke Area Ini</p>
                <p class="mt-0.5 text-xs text-gray-500">Format: PDF, JPG, PNG (Maksimal 5MB)</p>
            </div>

            <div data-dropzone-filled class="dropzone__filled" hidden>
                <p class="text-sm font-semibold text-gray-800" data-dropzone-name></p>
                <button type="button" data-dropzone-clear
                        class="relative z-10 mt-2 text-xs font-medium text-primary underline">
                    Hapus &amp; pilih berkas lain
                </button>
            </div>
        </div>

    @elseif ($field->type === 'document')
        @if ($field->documentUrl())
            <a href="{{ $field->documentUrl() }}" download="{{ $field->documentDownloadName() }}"
               class="mt-2 inline-flex items-center gap-2 rounded-lg border border-primary/30 bg-primary/5 px-4 py-2.5 text-sm font-semibold text-primary transition hover:bg-primary/10 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                Unduh {{ $field->label }}
            </a>
        @else
            <p class="mt-2 text-sm italic text-gray-400">Dokumen belum diunggah operator.</p>
        @endif

    @elseif ($field->type === 'kelas')
        {{-- Pilihan diambil langsung dari tabel referensi `kelas` (menu Data
             Kelas), bukan dari $field->options seperti tipe 'select' --
             supaya daftar kelas konsisten di semua formulir & cukup dikelola
             sekali di satu tempat. --}}
        @php $kelasOptions = \App\Models\Kelas::orderBy('sort_order')->pluck('name'); @endphp
        <select id="{{ $name }}" name="{{ $name }}" @required($field->required) class="{{ $inputClass }}">
            <option value="">&mdash; Pilih Kelas &mdash;</option>
            @foreach ($kelasOptions as $opt)
                <option value="{{ $opt }}" @selected(old($name) === $opt)>{{ $opt }}</option>
            @endforeach
        </select>
    @endif

    @if ($field->help_text)
        <p class="mt-1.5 text-xs text-gray-500">{{ $field->help_text }}</p>
    @endif

    @error($name)
        <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>
    @enderror
</div>

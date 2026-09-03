@extends('layouts.app')

@section('title', $form->title)

@section('content')
    <div class="mx-auto max-w-2xl px-4 py-10">
        {{-- Header dalam kartu dengan aksen warna di atas (ala Google Form) --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="h-2.5 bg-primary"></div>
            <div class="p-6 sm:p-8">
                <h1 class="text-2xl font-bold text-gray-900 sm:text-3xl">{{ $form->title }}</h1>
                @if ($form->description)
                    <p class="mt-3 text-gray-600">{{ $form->description }}</p>
                @endif
            </div>
        </div>

        @if (session('form_success'))
            {{-- Popup sukses: tampil ~1 detik lalu otomatis kembali ke beranda,
                 supaya siswa tidak mengisi form dua kali. --}}
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
                <div class="w-full max-w-sm rounded-2xl bg-white p-6 text-center shadow-xl">
                    <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-green-100">
                        <svg class="h-7 w-7 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                    </div>
                    <p class="text-gray-800">{{ session('form_success') }}</p>
                    <a href="{{ route('home') }}" class="mt-4 inline-block text-sm text-primary underline">Kembali ke beranda</a>
                </div>
            </div>
            <script>
                setTimeout(function () {
                    window.location.href = @json(route('home'));
                }, 2000);
            </script>
        @endif

        @if ($errors->any())
            <div class="mt-4 rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-red-800 shadow-sm">
                <p class="font-medium">Periksa kembali isian Anda:</p>
                <ul class="mt-1 list-disc pl-5 text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('forms.submit', $form) }}" enctype="multipart/form-data" class="mt-6 space-y-5">
            @csrf

            @foreach ($form->fields as $field)
                @php $name = 'field_'.$field->id; @endphp
                {{-- Tiap pertanyaan dalam kartunya sendiri --}}
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm transition hover:shadow-md">
                    <label class="block text-base font-medium text-gray-900">
                        {{ $field->label }}
                        @if ($field->required)<span class="ml-0.5 text-red-500">*</span>@endif
                    </label>

                    @if ($field->type === 'text')
                        <input type="text" name="{{ $name }}" value="{{ old($name) }}"
                            class="mt-3 block w-full rounded-lg border border-gray-300 px-3 py-2 text-gray-900 shadow-sm focus:border-primary focus:ring-1 focus:ring-primary">
                    @elseif ($field->type === 'textarea')
                        <textarea name="{{ $name }}" rows="4"
                            class="mt-3 block w-full rounded-lg border border-gray-300 px-3 py-2 text-gray-900 shadow-sm focus:border-primary focus:ring-1 focus:ring-primary">{{ old($name) }}</textarea>
                    @elseif ($field->type === 'select')
                        <select name="{{ $name }}"
                            class="mt-3 block w-full rounded-lg border border-gray-300 px-3 py-2 text-gray-900 shadow-sm focus:border-primary focus:ring-1 focus:ring-primary">
                            <option value="">— Pilih —</option>
                            @foreach (($field->options ?? []) as $opt)
                                <option value="{{ $opt }}" @selected(old($name) === $opt)>{{ $opt }}</option>
                            @endforeach
                        </select>
                    @elseif ($field->type === 'checkbox')
                        <div class="mt-3 space-y-3">
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
                        <input type="file" name="{{ $name }}"
                            class="mt-3 block w-full text-sm text-gray-600 file:mr-4 file:rounded-md file:border-0 file:bg-primary/10 file:px-4 file:py-2 file:font-medium file:text-primary hover:file:bg-primary/20">
                    @endif
                </div>
            @endforeach

            {{-- Tombol kirim di baris tersendiri --}}
            <div class="flex justify-end pt-4">
                <button type="submit"
                    class="rounded-lg bg-primary px-6 py-2.5 font-medium text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                    Kirim
                </button>
            </div>
        </form>
    </div>
@endsection

{{-- resources/views/filament/form-submission-detail.blade.php --}}
<div class="space-y-4 text-sm">
    @foreach ($submission->form->fields as $field)
        @php $value = $submission->data[$field->id] ?? null; @endphp
        <div>
            <div class="font-medium text-gray-500">{{ $field->label }}</div>
            <div class="mt-0.5 text-gray-900">
                @if ($field->type === 'file' && $value)
                    <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($value) }}"
                       target="_blank" class="text-primary underline">Lihat file</a>
                @elseif (is_array($value))
                    {{ implode(', ', $value) ?: '—' }}
                @else
                    {{ $value !== null && $value !== '' ? $value : '—' }}
                @endif
            </div>
        </div>
    @endforeach
</div>

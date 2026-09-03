{{-- Detail satu permohonan di panel operator. Variabel: $submission. --}}
<div class="space-y-5 text-sm">
    <div>
        <h3 class="font-semibold text-gray-900">Identitas Pemohon</h3>
        <dl class="mt-2 grid gap-2 sm:grid-cols-2">
            <div><dt class="text-gray-500">Kode Resi</dt><dd class="font-mono">{{ $submission->receipt_code }}</dd></div>
            <div><dt class="text-gray-500">Nama</dt><dd>{{ $submission->applicant_name }}</dd></div>
            <div><dt class="text-gray-500">WhatsApp</dt><dd>{{ $submission->applicant_whatsapp }}</dd></div>
            <div><dt class="text-gray-500">Email</dt><dd>{{ $submission->applicant_email }}</dd></div>
        </dl>
    </div>

    <div>
        <h3 class="font-semibold text-gray-900">Isian &amp; Berkas</h3>
        <div class="mt-2 space-y-3">
            @foreach ($submission->form->fields as $field)
                @php $value = $submission->data[$field->id] ?? null; @endphp
                <div>
                    <div class="font-medium text-gray-500">{{ $field->label }}</div>
                    <div class="mt-0.5 text-gray-900">
                        @if ($field->type === 'file' && $value)
                            <a href="{{ route('permohonan.berkas', ['submission' => $submission->id, 'field' => $field->id]) }}"
                               class="text-primary underline">Unduh berkas</a>
                        @elseif (is_array($value))
                            {{ implode(', ', $value) ?: '—' }}
                        @else
                            {{ $value !== null && $value !== '' ? $value : '—' }}
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div>
        <h3 class="font-semibold text-gray-900">Riwayat Status</h3>
        <ol class="mt-2 space-y-2">
            @foreach ($submission->statusLogs as $log)
                <li>
                    <span class="font-medium">{{ \App\Models\FormSubmission::STATUSES[$log->status] ?? $log->status }}</span>
                    <span class="text-gray-500">— {{ $log->created_at->format('d M Y H:i') }}</span>
                    @if ($log->note)
                        <div class="text-gray-600">{{ $log->note }}</div>
                    @endif
                </li>
            @endforeach
        </ol>
    </div>
</div>

<?php

namespace App\Support;

use App\Models\Form;
use App\Models\FormField;
use App\Models\Kelas;
use Illuminate\Validation\Rule;

/**
 * Menerjemahkan definisi field yang dibuat operator menjadi aturan validasi
 * Laravel. Dipisahkan dari controller supaya bisa dites sendiri dan dipakai
 * ulang bila nanti ada kanal pengajuan lain.
 */
class DynamicFieldRules
{
    public static function key(FormField $field): string
    {
        return 'field_'.$field->id;
    }

    public static function rules(Form $form): array
    {
        $rules = [];

        foreach ($form->fields as $field) {
            // Tipe 'document': berkas yang diunggah admin untuk diunduh pengaju,
            // bukan isian -- tidak pernah dikirim balik lewat request, jadi tidak
            // divalidasi sama sekali.
            if ($field->type === 'document') {
                continue;
            }

            $key = self::key($field);
            $wajib = $field->required ? 'required' : 'nullable';

            $rules[$key] = match ($field->type) {
                'select' => [$wajib, Rule::in($field->options ?? [])],
                // Tipe 'kelas': pilihan datang dari tabel referensi `kelas`
                // (menu Data Kelas), bukan dari $field->options seperti 'select'.
                'kelas' => [$wajib, Rule::in(Kelas::orderBy('sort_order')->pluck('name'))],
                'checkbox' => [$wajib, 'array'],
                'date' => [$wajib, 'date'],
                'number' => [$wajib, 'numeric'],
                'file' => [$wajib, 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
                default => [$wajib, 'string', 'max:5000'],
            };

            if ($field->type === 'checkbox') {
                $rules[$key.'.*'] = [Rule::in($field->options ?? [])];
            }
        }

        return $rules;
    }

    /** Nama field yang dipakai dalam pesan error, memakai label dari operator. */
    public static function attributes(Form $form): array
    {
        $attributes = [];

        foreach ($form->fields as $field) {
            if ($field->type === 'document') {
                continue;
            }

            $attributes[self::key($field)] = $field->label;
        }

        return $attributes;
    }
}

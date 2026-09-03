<?php

namespace App\Http\Controllers;

use App\Models\Form;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FormController extends Controller
{
    /**
     * Tampilkan form publik by slug. Hanya yang published; selain itu 404.
     */
    public function show(Form $form)
    {
        abort_unless($form->status === 'published', 404);

        $form->load('fields');

        return view('forms.show', compact('form'));
    }

    /**
     * Terima & simpan jawaban form. Validasi dibangun dinamis dari definisi field.
     */
    public function submit(Request $request, Form $form)
    {
        abort_unless($form->status === 'published', 404);

        $form->load('fields');

        $rules = [];
        $attributes = [];

        foreach ($form->fields as $field) {
            $key = 'field_'.$field->id;
            $attributes[$key] = $field->label;
            $required = $field->required ? 'required' : 'nullable';

            $rules[$key] = match ($field->type) {
                'select' => [$required, Rule::in($field->options ?? [])],
                'checkbox' => [$required, 'array'],
                'file' => [$required, 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
                default => [$required, 'string', 'max:5000'],
            };

            if ($field->type === 'checkbox') {
                $rules[$key.'.*'] = [Rule::in($field->options ?? [])];
            }
        }

        $validated = $request->validate($rules, [], $attributes);

        // Field yang ditandai "tidak boleh duplikat" (mis. Email/NIS): tolak bila
        // nilainya sudah pernah dipakai di jawaban form ini sebelumnya.
        $duplicateErrors = [];
        foreach ($form->fields as $field) {
            if (! $field->is_unique) {
                continue;
            }

            $key = 'field_'.$field->id;
            $value = $validated[$key] ?? null;

            if ($value === null || $value === '' || $value === []) {
                continue;
            }

            $alreadyUsed = $form->submissions()
                ->get(['data'])
                ->contains(fn ($submission) => ($submission->data[$field->id] ?? null) === $value);

            if ($alreadyUsed) {
                $duplicateErrors[$key] = "Nilai untuk \"{$field->label}\" sudah pernah digunakan dan tidak boleh sama.";
            }
        }

        if ($duplicateErrors !== []) {
            throw ValidationException::withMessages($duplicateErrors);
        }

        $data = [];
        foreach ($form->fields as $field) {
            $key = 'field_'.$field->id;

            if ($field->type === 'file') {
                if ($request->hasFile($key)) {
                    $data[$field->id] = $request->file($key)->store('forms', 'public');
                }
                continue;
            }

            $data[$field->id] = $validated[$key] ?? ($field->type === 'checkbox' ? [] : null);
        }

        $form->submissions()->create(['data' => $data]);

        return redirect()
            ->route('forms.show', $form)
            ->with('form_success', $form->success_message ?: 'Terima kasih, jawaban Anda telah dikirim.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\SatisfactionSurvey;
use App\Support\SatisfactionScale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SatisfactionController extends Controller
{
    /** Halaman survei kepuasan layanan (slider emoji per sub kepuasan layanan). */
    public function show(SatisfactionSurvey $survey)
    {
        abort_unless($survey->isPublicallyOpen(), 404);

        $survey->load('aspects');

        return view('kepuasan.show', ['survei' => $survey]);
    }

    /** Simpan penilaian. Anonim: tidak ada identitas pengisi yang direkam. */
    public function store(Request $request, SatisfactionSurvey $survey)
    {
        abort_unless($survey->isPublicallyOpen(), 404);

        // Honeypot: manusia tidak pernah melihat kolom ini, bot mengisinya.
        // Ditolak diam-diam supaya bot tidak belajar dari pesan error.
        if (filled($request->input('website'))) {
            return redirect()->route('kepuasan.show', $survey->slug);
        }

        $survey->load('aspects');

        $aturan = [
            'aspek' => ['required', 'array'],
            'saran' => ['nullable', 'string', 'max:1000'],
        ];
        $atribut = ['saran' => 'Saran & Masukan'];

        foreach ($survey->aspects as $aspect) {
            $aturan["aspek.{$aspect->id}"] = ['required', 'integer', Rule::in(SatisfactionScale::scores())];
            $atribut["aspek.{$aspect->id}"] = $aspect->label;
        }

        $validated = $request->validate($aturan, [], $atribut);

        DB::transaction(function () use ($survey, $validated): void {
            $response = $survey->responses()->create([
                'suggestion' => $survey->collect_suggestion ? ($validated['saran'] ?? null) : null,
            ]);

            foreach ($survey->aspects as $aspect) {
                $response->answers()->create([
                    'satisfaction_aspect_id' => $aspect->id,
                    'score' => (int) $validated['aspek'][$aspect->id],
                ]);
            }
        });

        return redirect()
            ->route('kepuasan.show', $survey->slug)
            ->with('kepuasan_sukses', $survey->success_message ?: 'Terima kasih! Penilaian Anda sudah kami terima.');
    }
}

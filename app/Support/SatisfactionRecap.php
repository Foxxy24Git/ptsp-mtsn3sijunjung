<?php

namespace App\Support;

use App\Models\SatisfactionAspect;
use App\Models\SatisfactionSurvey;
use Illuminate\Support\Facades\DB;

/**
 * Rekap hasil survei kepuasan: jumlah responden, rata-rata keseluruhan, dan
 * rata-rata + sebaran nilai per sub kepuasan layanan.
 *
 * Perhitungan dilakukan lewat satu query agregat (bukan memuat semua jawaban
 * ke memori) supaya tetap ringan walau responden sudah ribuan.
 */
final class SatisfactionRecap
{
    /**
     * @return array{
     *     responden: int,
     *     jawaban: int,
     *     average: float|null,
     *     grade: string,
     *     aspects: array<int, array{
     *         aspect: SatisfactionAspect,
     *         average: float|null,
     *         total: int,
     *         distribution: array<int, int>
     *     }>
     * }
     */
    public static function for(SatisfactionSurvey $survey): array
    {
        $aspects = $survey->aspects()->get();

        $sebaran = DB::table('satisfaction_answers')
            ->whereIn('satisfaction_aspect_id', $aspects->pluck('id'))
            ->selectRaw('satisfaction_aspect_id, score, COUNT(*) as jumlah')
            ->groupBy('satisfaction_aspect_id', 'score')
            ->get()
            ->groupBy('satisfaction_aspect_id');

        $kosong = array_fill_keys(SatisfactionScale::scores(), 0);

        $totalNilai = 0;
        $totalJawaban = 0;

        $hasil = $aspects->map(function (SatisfactionAspect $aspect) use ($sebaran, $kosong, &$totalNilai, &$totalJawaban): array {
            $distribution = $kosong;
            $jumlah = 0;
            $bobot = 0;

            foreach ($sebaran->get($aspect->id, collect()) as $baris) {
                $skor = (int) $baris->score;

                if (! array_key_exists($skor, $distribution)) {
                    continue;
                }

                $distribution[$skor] = (int) $baris->jumlah;
                $jumlah += (int) $baris->jumlah;
                $bobot += $skor * (int) $baris->jumlah;
            }

            $totalNilai += $bobot;
            $totalJawaban += $jumlah;

            return [
                'aspect' => $aspect,
                'average' => $jumlah > 0 ? round($bobot / $jumlah, 2) : null,
                'total' => $jumlah,
                'distribution' => $distribution,
            ];
        })->all();

        $average = $totalJawaban > 0 ? round($totalNilai / $totalJawaban, 2) : null;

        return [
            'responden' => $survey->responses()->count(),
            'jawaban' => $totalJawaban,
            'average' => $average,
            'grade' => SatisfactionScale::grade($average),
            'aspects' => $hasil,
        ];
    }
}

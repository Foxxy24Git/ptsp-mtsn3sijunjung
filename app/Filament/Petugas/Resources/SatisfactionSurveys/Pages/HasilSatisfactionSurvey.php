<?php

namespace App\Filament\Petugas\Resources\SatisfactionSurveys\Pages;

use App\Filament\Petugas\Resources\SatisfactionSurveys\SatisfactionSurveyResource;
use App\Support\SatisfactionRecap;
use App\Support\SatisfactionScale;
use BackedEnum;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Icons\Heroicon;

/**
 * Rekap hasil satu survei untuk petugas. Sama persis dengan
 * HasilSatisfactionSurvey milik admin (view Blade & logika rekap dipakai
 * ulang apa adanya, keduanya panel-agnostic) — hanya $resource yang beda
 * karena Filament menaut tiap halaman resource ke satu panel.
 */
class HasilSatisfactionSurvey extends Page
{
    use InteractsWithRecord;

    /** Batas tabel jawaban mentah agar halaman tetap ringan tanpa paginasi. */
    private const BATAS_JAWABAN = 50;

    protected static string $resource = SatisfactionSurveyResource::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Hasil Survei';

    protected string $view = 'filament.kepuasan-hasil';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    public function getTitle(): string
    {
        return 'Hasil: '.$this->getRecord()->title;
    }

    protected function getViewData(): array
    {
        $survei = $this->getRecord();

        return [
            'survei' => $survei,
            'rekap' => SatisfactionRecap::for($survei),
            'skala' => SatisfactionScale::LEVELS,
            'batas' => self::BATAS_JAWABAN,
            'jawaban' => $survei->responses()
                ->with('answers')
                ->latest('id')
                ->limit(self::BATAS_JAWABAN)
                ->get(),
        ];
    }
}

<?php

namespace App\Filament\Resources\SatisfactionSurveys;

use App\Filament\Resources\SatisfactionSurveys\Pages\CreateSatisfactionSurvey;
use App\Filament\Resources\SatisfactionSurveys\Pages\EditSatisfactionSurvey;
use App\Filament\Resources\SatisfactionSurveys\Pages\HasilSatisfactionSurvey;
use App\Filament\Resources\SatisfactionSurveys\Pages\ListSatisfactionSurveys;
use App\Filament\Resources\SatisfactionSurveys\Schemas\SatisfactionSurveyForm;
use App\Filament\Resources\SatisfactionSurveys\Tables\SatisfactionSurveysTable;
use App\Models\SatisfactionSurvey;
use BackedEnum;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SatisfactionSurveyResource extends Resource
{
    protected static ?string $model = SatisfactionSurvey::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFaceSmile;

    /**
     * Urutan sidebar kelompok tanpa grup: Menus (10) → Pages (20) →
     * Kepuasan Layanan (30) → Pengaturan Situs (90). Nilainya ditulis
     * eksplisit di tiap resource supaya urutan tidak bergantung pada urutan
     * penemuan file oleh Filament.
     */
    protected static ?int $navigationSort = 30;

    protected static ?string $navigationLabel = 'Kepuasan Layanan';

    protected static ?string $modelLabel = 'Survei Kepuasan';

    protected static ?string $pluralModelLabel = 'Kepuasan Layanan';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return SatisfactionSurveyForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SatisfactionSurveysTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    /** Tab "Ubah Survei" & "Hasil Survei" di dalam satu record. */
    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            EditSatisfactionSurvey::class,
            HasilSatisfactionSurvey::class,
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSatisfactionSurveys::route('/'),
            'create' => CreateSatisfactionSurvey::route('/create'),
            'edit' => EditSatisfactionSurvey::route('/{record}/edit'),
            'hasil' => HasilSatisfactionSurvey::route('/{record}/hasil'),
        ];
    }
}

<?php

namespace App\Filament\Petugas\Resources\SatisfactionSurveys;

use App\Filament\Petugas\Resources\SatisfactionSurveys\Pages\HasilSatisfactionSurvey;
use App\Filament\Petugas\Resources\SatisfactionSurveys\Pages\ListSatisfactionSurveys;
use App\Models\SatisfactionSurvey;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Versi khusus petugas dari SatisfactionSurveyResource (admin): hanya
 * menampilkan daftar survei + tombol "Hasil" (statistik). Tabelnya ditulis
 * ulang (bukan memakai SatisfactionSurveysTable milik admin apa adanya)
 * karena tabel itu menaut tombol Hasil ke URL SatisfactionSurveyResource
 * admin serta menyertakan EditAction & DeleteBulkAction — kalau dipakai
 * apa adanya, petugas akan dilempar ke form login admin saat klik Hasil,
 * dan bisa mengubah/menghapus survei di luar cakupan "statistik".
 * Tidak ada halaman create/edit: sama seperti PermohonanResource, petugas
 * hanya boleh melihat.
 */
class SatisfactionSurveyResource extends Resource
{
    protected static ?string $model = SatisfactionSurvey::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Kepuasan Layanan';

    protected static ?string $modelLabel = 'Survei Kepuasan';

    protected static ?string $pluralModelLabel = 'Kepuasan Layanan';

    protected static ?string $recordTitleAttribute = 'title';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Judul')
                    ->description(fn (SatisfactionSurvey $record): string => '/kepuasan/'.$record->slug)
                    ->searchable(),
                TextColumn::make('aspects_count')
                    ->label('Sub')
                    ->counts('aspects'),
                TextColumn::make('responses_count')
                    ->label('Responden')
                    ->counts('responses'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->colors(['success' => 'published', 'gray' => 'draft']),
            ])
            ->defaultSort('sort_order')
            ->filters([
                SelectFilter::make('status')
                    ->options(['draft' => 'Draft', 'published' => 'Terbit']),
            ])
            ->recordActions([
                Action::make('hasil')
                    ->label('Hasil')
                    ->icon('heroicon-o-chart-bar')
                    ->color('gray')
                    ->url(fn (SatisfactionSurvey $record): string => static::getUrl('hasil', ['record' => $record])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSatisfactionSurveys::route('/'),
            'hasil' => HasilSatisfactionSurvey::route('/{record}/hasil'),
        ];
    }
}

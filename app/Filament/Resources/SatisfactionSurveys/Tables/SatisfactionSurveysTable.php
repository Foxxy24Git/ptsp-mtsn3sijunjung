<?php

namespace App\Filament\Resources\SatisfactionSurveys\Tables;

use App\Filament\Resources\SatisfactionSurveys\SatisfactionSurveyResource;
use App\Models\SatisfactionSurvey;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SatisfactionSurveysTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable(),
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
            ->reorderable('sort_order')
            ->filters([
                SelectFilter::make('status')
                    ->options(['draft' => 'Draft', 'published' => 'Terbit']),
            ])
            ->recordActions([
                Action::make('hasil')
                    ->label('Hasil')
                    ->icon('heroicon-o-chart-bar')
                    ->color('gray')
                    ->url(fn (SatisfactionSurvey $record): string => SatisfactionSurveyResource::getUrl('hasil', ['record' => $record])),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

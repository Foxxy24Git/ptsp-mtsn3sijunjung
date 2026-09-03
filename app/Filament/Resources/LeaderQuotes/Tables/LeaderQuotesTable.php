<?php

namespace App\Filament\Resources\LeaderQuotes\Tables;

use App\Models\LeaderQuote;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LeaderQuotesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('photo')
                    ->label('Foto')
                    ->collection('photo')
                    ->circular(),
                TextColumn::make('placement')
                    ->label('Peletakan')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => LeaderQuote::placementOptions()[$state] ?? $state)
                    ->color(fn (string $state): string => $state === LeaderQuote::PLACEMENT_PIMPINAN ? 'primary' : 'gray'),
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),
                TextColumn::make('position')
                    ->label('Jabatan')
                    ->placeholder('—')
                    ->searchable(),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                TextColumn::make('sort_order')
                    ->label('Urutan')
                    ->numeric()
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

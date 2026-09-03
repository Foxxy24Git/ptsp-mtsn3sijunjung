<?php

namespace App\Filament\Resources\Forms\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FormsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable(),
                TextColumn::make('title')
                    ->label('Nama Layanan')
                    ->searchable(),
                TextColumn::make('workUnit.name')
                    ->label('Satuan Kerja')
                    ->badge()
                    ->placeholder('Umum'),
                TextColumn::make('duration_text')
                    ->label('Waktu')
                    ->placeholder('Menyesuaikan'),
                TextColumn::make('fee_text')
                    ->label('Biaya'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->colors(['success' => 'published', 'gray' => 'draft']),
                TextColumn::make('submissions_count')
                    ->label('Permohonan')
                    ->counts('submissions'),
            ])
            ->defaultSort('sort_order')
            // Urutan geser di sini langsung menentukan nomor kartu di katalog publik.
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

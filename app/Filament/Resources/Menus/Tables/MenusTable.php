<?php

namespace App\Filament\Resources\Menus\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MenusTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('label')
                    ->description(fn ($record): ?string => $record->parent?->label
                        ? '↳ submenu dari: ' . $record->parent->label
                        : null)
                    ->searchable(),
                TextColumn::make('type')
                    ->badge(),
                TextColumn::make('target')
                    ->limit(40)
                    ->placeholder('—'),
                TextColumn::make('parent.label')
                    ->label('Parent')
                    ->placeholder('— (menu utama)')
                    ->sortable(),
                TextColumn::make('sort_order')
                    ->label('Urutan')
                    ->numeric()
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'page' => 'Page',
                        'post' => 'Post',
                        'url' => 'URL',
                        'form' => 'Form',
                        'kepuasan' => 'Kepuasan Layanan',
                    ]),
                SelectFilter::make('parent_id')
                    ->label('Parent')
                    ->relationship('parent', 'label'),
            ])
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

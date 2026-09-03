<?php

namespace App\Filament\Resources\Menus\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MenuForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('label')
                    ->required()
                    ->maxLength(255),
                Select::make('type')
                    ->options([
                        'page' => 'Page',
                        'post' => 'Post',
                        'url' => 'URL',
                        'form' => 'Form',
                    ])
                    ->default('url')
                    ->required(),
                TextInput::make('target')
                    ->label('Target (slug / URL)')
                    ->helperText('Slug halaman/berita, atau URL lengkap untuk tipe URL.')
                    ->maxLength(255),
                Select::make('parent_id')
                    ->label('Parent (submenu dari)')
                    ->relationship('parent', 'label')
                    ->searchable()
                    ->preload()
                    ->nullable(),
                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0)
                    ->required(),
            ]);
    }
}

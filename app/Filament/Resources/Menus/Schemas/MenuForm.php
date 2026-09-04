<?php

namespace App\Filament\Resources\Menus\Schemas;

use App\Models\SatisfactionSurvey;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
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
                        'kepuasan' => 'Kepuasan Layanan',
                    ])
                    ->default('url')
                    ->required()
                    ->live(),
                // Untuk tipe Kepuasan Layanan, slug tidak diketik manual:
                // admin memilih surveinya dan slug-nya yang tersimpan ke
                // kolom target — supaya menu tidak pernah menunjuk slug yang
                // salah ketik atau surveinya sudah dihapus.
                Select::make('target')
                    ->label('Survei Kepuasan Layanan')
                    ->options(fn (): array => SatisfactionSurvey::query()
                        ->ordered()
                        ->pluck('title', 'slug')
                        ->all())
                    ->searchable()
                    ->preload()
                    ->required()
                    ->helperText('Alamat menu jadi /kepuasan/{slug}. Buat surveinya dulu di menu Kepuasan Layanan.')
                    ->visible(fn (Get $get): bool => $get('type') === 'kepuasan'),
                TextInput::make('target')
                    ->label('Target (slug / URL)')
                    ->helperText('Slug halaman/berita, atau URL lengkap untuk tipe URL.')
                    ->maxLength(255)
                    ->visible(fn (Get $get): bool => $get('type') !== 'kepuasan'),
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

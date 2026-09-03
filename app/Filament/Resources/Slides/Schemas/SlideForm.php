<?php

namespace App\Filament\Resources\Slides\Schemas;

use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SlideForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                SpatieMediaLibraryFileUpload::make('image')
                    ->label('Gambar')
                    ->collection('image')
                    ->disk('public')
                    ->image()
                    ->imageEditor()
                    ->required()
                    ->helperText('Gambar wajib. Disarankan rasio lebar (mis. 1600×640) agar pas sebagai banner.')
                    ->columnSpanFull(),
                TextInput::make('title')
                    ->label('Judul (opsional)')
                    ->maxLength(255),
                Textarea::make('description')
                    ->label('Deskripsi (opsional)')
                    ->rows(2)
                    ->maxLength(500)
                    ->columnSpanFull(),
                TextInput::make('link_url')
                    ->label('URL Tautan (opsional)')
                    ->url()
                    ->maxLength(255)
                    ->helperText('Bila diisi, slide bisa diklik menuju URL ini.'),
                TextInput::make('link_label')
                    ->label('Teks Tombol (opsional)')
                    ->maxLength(255)
                    ->helperText('Mis. "Selengkapnya". Hanya tampil bila URL Tautan diisi.'),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true)
                    ->helperText('Nonaktifkan untuk menyembunyikan slide tanpa menghapus.'),
            ]);
    }
}

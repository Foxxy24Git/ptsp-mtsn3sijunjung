<?php

namespace App\Filament\Resources\GalleryItems\Schemas;

use App\Models\GalleryItem;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class GalleryItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type')
                    ->label('Jenis')
                    ->options([
                        GalleryItem::TYPE_PHOTO => 'Foto',
                        GalleryItem::TYPE_VIDEO => 'Video (YouTube)',
                    ])
                    ->default(GalleryItem::TYPE_PHOTO)
                    ->required()
                    ->live()
                    ->native(false),
                TextInput::make('title')
                    ->label('Judul (opsional)')
                    ->maxLength(255)
                    ->columnSpanFull(),
                Textarea::make('description')
                    ->label('Keterangan (opsional)')
                    ->rows(2)
                    ->maxLength(500)
                    ->helperText('Tampil di bawah judul pada kartu galeri.')
                    ->columnSpanFull(),
                SpatieMediaLibraryFileUpload::make('image')
                    ->label('Foto')
                    ->collection('image')
                    ->disk('public')
                    ->image()
                    ->imageEditor()
                    ->maxSize(5120)
                    ->helperText('Maksimal 5 MB.')
                    ->visible(fn (Get $get): bool => $get('type') === GalleryItem::TYPE_PHOTO)
                    ->required(fn (Get $get): bool => $get('type') === GalleryItem::TYPE_PHOTO)
                    ->columnSpanFull(),
                TextInput::make('youtube_url')
                    ->label('Link YouTube')
                    ->url()
                    ->maxLength(255)
                    ->helperText('Tempel link video YouTube, mis. https://youtu.be/xxxxxxxxxxx atau https://www.youtube.com/watch?v=xxxxxxxxxxx. Video akan diputar langsung di halaman ini.')
                    ->rule(fn (): \Closure => function (string $attribute, ?string $value, \Closure $fail) {
                        if ($value && ! GalleryItem::extractYoutubeId($value)) {
                            $fail('Link YouTube tidak valid atau ID video tidak ditemukan.');
                        }
                    })
                    ->visible(fn (Get $get): bool => $get('type') === GalleryItem::TYPE_VIDEO)
                    ->required(fn (Get $get): bool => $get('type') === GalleryItem::TYPE_VIDEO)
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true)
                    ->helperText('Nonaktifkan untuk menyembunyikan dari galeri tanpa menghapus.'),
            ]);
    }
}

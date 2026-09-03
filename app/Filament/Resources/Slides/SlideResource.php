<?php

namespace App\Filament\Resources\Slides;

use App\Filament\Resources\Slides\Pages\CreateSlide;
use App\Filament\Resources\Slides\Pages\EditSlide;
use App\Filament\Resources\Slides\Pages\ListSlides;
use App\Filament\Resources\Slides\Schemas\SlideForm;
use App\Filament\Resources\Slides\Tables\SlidesTable;
use App\Models\Slide;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SlideResource extends Resource
{
    protected static ?string $model = Slide::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    /**
     * Modul bawaan CMS-web yang tidak dipakai PTSP. Sengaja disembunyikan,
     * bukan dihapus: menghapusnya berarti menyentuh migrasi, seeder, dan
     * layout beranda sekaligus. Pembersihan dijadwalkan terpisah.
     */
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationLabel = 'Slider';

    protected static ?string $modelLabel = 'Slide';

    protected static ?string $pluralModelLabel = 'Slider';

    public static function form(Schema $schema): Schema
    {
        return SlideForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SlidesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSlides::route('/'),
            'create' => CreateSlide::route('/create'),
            'edit' => EditSlide::route('/{record}/edit'),
        ];
    }
}

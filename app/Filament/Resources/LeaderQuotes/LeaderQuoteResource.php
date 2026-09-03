<?php

namespace App\Filament\Resources\LeaderQuotes;

use App\Filament\Resources\LeaderQuotes\Pages\CreateLeaderQuote;
use App\Filament\Resources\LeaderQuotes\Pages\EditLeaderQuote;
use App\Filament\Resources\LeaderQuotes\Pages\ListLeaderQuotes;
use App\Filament\Resources\LeaderQuotes\Schemas\LeaderQuoteForm;
use App\Filament\Resources\LeaderQuotes\Tables\LeaderQuotesTable;
use App\Models\LeaderQuote;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class LeaderQuoteResource extends Resource
{
    protected static ?string $model = LeaderQuote::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    /**
     * Modul bawaan CMS-web yang tidak dipakai PTSP. Sengaja disembunyikan,
     * bukan dihapus: menghapusnya berarti menyentuh migrasi, seeder, dan
     * layout beranda sekaligus. Pembersihan dijadwalkan terpisah.
     */
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationLabel = 'Pimpinan';

    protected static ?string $modelLabel = 'Pimpinan';

    protected static ?string $pluralModelLabel = 'Pimpinan';

    public static function form(Schema $schema): Schema
    {
        return LeaderQuoteForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LeaderQuotesTable::configure($table);
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
            'index' => ListLeaderQuotes::route('/'),
            'create' => CreateLeaderQuote::route('/create'),
            'edit' => EditLeaderQuote::route('/{record}/edit'),
        ];
    }
}

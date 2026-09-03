<?php

namespace App\Filament\Resources\Stats;

use App\Filament\Resources\Stats\Pages\CreateStat;
use App\Filament\Resources\Stats\Pages\EditStat;
use App\Filament\Resources\Stats\Pages\ListStats;
use App\Filament\Resources\Stats\Schemas\StatForm;
use App\Filament\Resources\Stats\Tables\StatsTable;
use App\Models\Stat;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class StatResource extends Resource
{
    protected static ?string $model = Stat::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    /**
     * Modul bawaan CMS-web yang tidak dipakai PTSP. Sengaja disembunyikan,
     * bukan dihapus: menghapusnya berarti menyentuh migrasi, seeder, dan
     * layout beranda sekaligus. Pembersihan dijadwalkan terpisah.
     */
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationLabel = 'Statistik';

    protected static ?string $modelLabel = 'Statistik';

    protected static ?string $pluralModelLabel = 'Statistik';

    public static function form(Schema $schema): Schema
    {
        return StatForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StatsTable::configure($table);
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
            'index' => ListStats::route('/'),
            'create' => CreateStat::route('/create'),
            'edit' => EditStat::route('/{record}/edit'),
        ];
    }
}

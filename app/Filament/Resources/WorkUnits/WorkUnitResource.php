<?php

namespace App\Filament\Resources\WorkUnits;

use App\Filament\Resources\WorkUnits\Pages\CreateWorkUnit;
use App\Filament\Resources\WorkUnits\Pages\EditWorkUnit;
use App\Filament\Resources\WorkUnits\Pages\ListWorkUnits;
use App\Filament\Resources\WorkUnits\Schemas\WorkUnitForm;
use App\Filament\Resources\WorkUnits\Tables\WorkUnitsTable;
use App\Models\WorkUnit;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class WorkUnitResource extends Resource
{
    protected static ?string $model = WorkUnit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|\UnitEnum|null $navigationGroup = 'PTSP';

    protected static ?int $navigationSort = 30;

    protected static ?string $navigationLabel = 'Satuan Kerja';

    protected static ?string $modelLabel = 'Satuan Kerja';

    protected static ?string $pluralModelLabel = 'Satuan Kerja';

    public static function form(Schema $schema): Schema
    {
        return WorkUnitForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WorkUnitsTable::configure($table);
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
            'index' => ListWorkUnits::route('/'),
            'create' => CreateWorkUnit::route('/create'),
            'edit' => EditWorkUnit::route('/{record}/edit'),
        ];
    }
}

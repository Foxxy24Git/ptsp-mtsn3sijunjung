<?php

namespace App\Filament\Resources\Petugas;

use App\Filament\Resources\Petugas\Pages\CreatePetugas;
use App\Filament\Resources\Petugas\Pages\EditPetugas;
use App\Filament\Resources\Petugas\Pages\ListPetugas;
use App\Filament\Resources\Petugas\Schemas\PetugasForm;
use App\Filament\Resources\Petugas\Tables\PetugasTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PetugasResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static string|\UnitEnum|null $navigationGroup = 'PTSP';

    protected static ?int $navigationSort = 40;

    /**
     * Ditentukan eksplisit karena nama kelas Indonesia ("Petugas") tidak bisa
     * diandalkan mengikuti aturan jamak otomatis Filament (lihat catatan di
     * App\Filament\Petugas\Resources\Permohonan\PermohonanResource).
     */
    protected static ?string $slug = 'petugas';

    protected static ?string $navigationLabel = 'Petugas';

    protected static ?string $modelLabel = 'Petugas';

    protected static ?string $pluralModelLabel = 'Petugas';

    /** Resource ini HANYA mengelola akun petugas — akun administrator tidak pernah muncul di sini. */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('role', User::ROLE_PETUGAS);
    }

    public static function form(Schema $schema): Schema
    {
        return PetugasForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PetugasTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPetugas::route('/'),
            'create' => CreatePetugas::route('/create'),
            'edit' => EditPetugas::route('/{record}/edit'),
        ];
    }
}

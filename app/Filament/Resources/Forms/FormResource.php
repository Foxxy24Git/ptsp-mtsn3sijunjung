<?php

namespace App\Filament\Resources\Forms;

use App\Filament\Resources\Forms\Pages\CreateForm;
use App\Filament\Resources\Forms\Pages\EditForm;
use App\Filament\Resources\Forms\Pages\ListForms;
use App\Filament\Resources\Forms\RelationManagers;
use App\Filament\Resources\Forms\Schemas\FormSchema;
use App\Filament\Resources\Forms\Tables\FormsTable;
use App\Models\Form;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FormResource extends Resource
{
    protected static ?string $model = Form::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|\UnitEnum|null $navigationGroup = 'PTSP';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Layanan';

    protected static ?string $modelLabel = 'Layanan';

    protected static ?string $pluralModelLabel = 'Layanan';

    /**
     * Form::getRouteKeyName() mengembalikan 'slug' untuk keperluan rute publik
     * (/layanan/{form:slug}). Tanpa baris ini, Filament ikut memakai 'slug'
     * untuk resolusi record di panel admin — padahal URL admin (tombol Edit,
     * dsb) selalu dibangun dari id numerik, sehingga setiap link Edit akan
     * 404 karena mencari baris dengan slug = "5" alih-alih id = 5.
     */
    protected static ?string $recordRouteKeyName = 'id';

    /**
     * Panel ini hanya mengurus layanan PTSP. Form biasa (is_service = false)
     * sengaja disembunyikan supaya operator tidak bingung melihat dua jenis
     * data dalam satu daftar.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('is_service', true);
    }

    public static function form(Schema $schema): Schema
    {
        return FormSchema::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FormsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\SubmissionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListForms::route('/'),
            'create' => CreateForm::route('/create'),
            'edit' => EditForm::route('/{record}/edit'),
        ];
    }
}

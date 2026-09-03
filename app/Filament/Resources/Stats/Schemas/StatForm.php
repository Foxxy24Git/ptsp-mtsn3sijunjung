<?php

namespace App\Filament\Resources\Stats\Schemas;

use App\Models\Stat;
use App\Settings\GeneralSettings;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class StatForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('icon')
                    ->label('Ikon')
                    ->options(Stat::iconHtmlOptions())
                    ->allowHtml()
                    ->searchable()
                    ->native(false)
                    ->required()
                    ->default('heroicon-o-users'),
                TextInput::make('label')
                    ->label('Judul / Keterangan')
                    ->placeholder('mis. Mahasiswa Aktif')
                    ->required()
                    ->maxLength(255),
                TextInput::make('value')
                    ->label('Angka / Teks')
                    ->placeholder('mis. 500 atau A')
                    ->helperText('Boleh angka (akan menghitung naik otomatis) atau teks seperti "A" / "Akreditasi".')
                    ->required()
                    ->maxLength(255),
                TextInput::make('suffix')
                    ->label('Akhiran (opsional)')
                    ->placeholder('mis. + atau %')
                    ->maxLength(10),
                ColorPicker::make('color')
                    ->label('Warna')
                    ->required()
                    ->default(fn (): string => app(GeneralSettings::class)->primary_color),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true)
                    ->helperText('Nonaktifkan untuk menyembunyikan tanpa menghapus.'),
            ]);
    }
}

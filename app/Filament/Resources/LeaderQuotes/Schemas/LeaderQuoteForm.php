<?php

namespace App\Filament\Resources\LeaderQuotes\Schemas;

use App\Models\LeaderQuote;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class LeaderQuoteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('placement')
                    ->label('Peletakan / Layout')
                    ->options(LeaderQuote::placementOptions())
                    ->default(LeaderQuote::PLACEMENT_PIMPINAN)
                    ->required()
                    ->native(false)
                    ->live()
                    ->helperText('Pimpinan tampil sebagai sambutan besar. "Di Bawah Pimpinan" tampil sebagai barisan foto (mis. waka), cukup nama & jabatan.')
                    ->columnSpanFull(),
                SpatieMediaLibraryFileUpload::make('photo')
                    ->label('Foto')
                    ->collection('photo')
                    ->disk('public')
                    ->image()
                    ->imageEditor()
                    ->required()
                    ->helperText('Bisa PNG atau JPG. Disarankan PNG dengan latar transparan agar hanya sosoknya yang tampil.')
                    ->columnSpanFull(),
                TextInput::make('name')
                    ->label('Nama')
                    ->placeholder('mis. Dr. H. Samsudin, M.Pd.')
                    ->required()
                    ->maxLength(255),
                TextInput::make('position')
                    ->label('Jabatan (opsional)')
                    ->placeholder('mis. Kepala Sekolah / Waka Akademik')
                    ->maxLength(255),
                TextInput::make('title')
                    ->label('Judul Besar (opsional)')
                    ->placeholder('mis. Sambutan Kepala Madrasah')
                    ->helperText('Tampil besar di atas quote. Bila kosong, nama yang ditampilkan besar.')
                    ->maxLength(255)
                    ->visible(fn (callable $get): bool => $get('placement') === LeaderQuote::PLACEMENT_PIMPINAN),
                Select::make('background_style')
                    ->label('Elemen / Bingkai Foto')
                    ->options(fn (callable $get): array => $get('placement') === LeaderQuote::PLACEMENT_PENDAMPING
                        ? [
                            'Foto menonjol keluar (cutout)' => LeaderQuote::backgroundStyleOptions(),
                            'Foto masuk ke dalam bingkai' => LeaderQuote::framedStyleOptions(),
                        ]
                        : LeaderQuote::backgroundStyleOptions())
                    ->default('arch')
                    ->required()
                    ->native(false)
                    ->helperText('Bentuk hijau di belakang foto. Untuk "Di Bawah Pimpinan" tersedia juga bingkai yang membuat foto seolah masuk ke dalam elemen.'),
                Textarea::make('quote')
                    ->label('Quote (opsional)')
                    ->helperText('Boleh dikosongkan bila hanya ingin menampilkan foto & nama.')
                    ->rows(4)
                    ->maxLength(1000)
                    ->columnSpanFull()
                    ->visible(fn (callable $get): bool => $get('placement') === LeaderQuote::PLACEMENT_PIMPINAN),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true)
                    ->helperText('Nonaktifkan untuk menyembunyikan tanpa menghapus.'),
            ]);
    }
}

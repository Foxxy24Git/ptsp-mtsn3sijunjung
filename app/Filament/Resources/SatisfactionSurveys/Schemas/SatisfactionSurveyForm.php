<?php

namespace App\Filament\Resources\SatisfactionSurveys\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class SatisfactionSurveyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Kepuasan Layanan')
                    ->columnSpanFull()
                    ->tabs([
                        Tabs\Tab::make('Informasi Survei')->schema([
                            TextInput::make('title')
                                ->label('Judul Kepuasan Layanan')
                                ->required()
                                ->maxLength(255)
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug((string) $state))),
                            TextInput::make('slug')
                                ->required()
                                ->maxLength(255)
                                ->unique(ignoreRecord: true)
                                ->helperText('Alamat survei: /kepuasan/{slug}. Slug ini yang dipilih saat membuat menu bertipe "Kepuasan Layanan".'),
                            Select::make('status')
                                ->label('Status')
                                ->options(['draft' => 'Draft', 'published' => 'Terbit'])
                                ->default('draft')
                                ->required()
                                ->helperText('Hanya survei berstatus Terbit yang bisa dibuka pengaju.'),
                            TextInput::make('sort_order')
                                ->label('Urutan')
                                ->numeric()
                                ->default(0),
                            Textarea::make('description')
                                ->label('Pengantar singkat (opsional)')
                                ->rows(3)
                                ->maxLength(1000)
                                ->helperText('Tampil di bawah judul pada halaman survei.')
                                ->columnSpanFull(),
                        ])->columns(2),

                        Tabs\Tab::make('Sub Kepuasan Layanan')->schema([
                            Repeater::make('aspects')
                                ->label('Sub kepuasan layanan yang dinilai')
                                ->relationship()
                                ->orderColumn('sort_order')
                                ->reorderable()
                                ->collapsible()
                                ->minItems(1)
                                ->defaultItems(1)
                                ->itemLabel(fn (array $state): ?string => $state['label'] ?? 'Sub baru')
                                ->addActionLabel('Tambah sub kepuasan layanan')
                                ->helperText('Tiap sub tampil sebagai satu baris wajah + slider geser di halaman survei. Minimal satu sub, jika tidak surveinya tidak bisa dibuka pengaju.')
                                ->columnSpanFull()
                                ->schema([
                                    TextInput::make('label')
                                        ->label('Nama sub')
                                        ->placeholder('Mis. Keramahan Petugas')
                                        ->required()
                                        ->maxLength(255),
                                    TextInput::make('help_text')
                                        ->label('Keterangan (opsional)')
                                        ->maxLength(255)
                                        ->helperText('Tampil sebagai teks kecil di bawah nama sub.'),
                                ])
                                ->columns(2),
                        ]),

                        Tabs\Tab::make('Pengaturan')->schema([
                            Toggle::make('collect_suggestion')
                                ->label('Tampilkan kolom Saran & Masukan')
                                ->helperText('Kolom teks bebas, opsional bagi pengisi survei.')
                                ->default(true),
                            TextInput::make('success_message')
                                ->label('Pesan setelah kirim (opsional)')
                                ->maxLength(255)
                                ->placeholder('Terima kasih! Penilaian Anda sudah kami terima.'),
                        ])->columns(1),
                    ]),
            ]);
    }
}

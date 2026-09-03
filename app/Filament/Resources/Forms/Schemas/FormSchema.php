<?php

namespace App\Filament\Resources\Forms\Schemas;

use App\Models\FormField;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class FormSchema
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Layanan')
                    ->columnSpanFull()
                    ->tabs([
                        Tabs\Tab::make('Informasi Layanan')->schema([
                            TextInput::make('title')
                                ->label('Nama Layanan')
                                ->required()
                                ->maxLength(255)
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug((string) $state))),
                            TextInput::make('slug')
                                ->required()
                                ->maxLength(255)
                                ->unique(ignoreRecord: true)
                                ->helperText('Alamat layanan: /layanan/{slug}. Otomatis dari nama, bisa diubah.'),
                            TextInput::make('organizer')
                                ->label('Penyelenggara')
                                ->default('PTSP')
                                ->required()
                                ->maxLength(100)
                                ->helperText('Bagian kiri label kartu, mis. "PTSP" atau "Panitia PPDB".'),
                            Select::make('work_unit_id')
                                ->label('Satuan Kerja')
                                ->relationship('workUnit', 'name')
                                ->searchable()
                                ->preload()
                                ->helperText('Bagian kanan label kartu, sekaligus dasar filter di katalog.'),
                            TextInput::make('duration_text')
                                ->label('Waktu Layanan')
                                ->maxLength(50)
                                ->placeholder('30 Menit')
                                ->helperText('Ditulis bebas karena satuannya bisa menit, jam, atau hari.'),
                            TextInput::make('fee_text')
                                ->label('Biaya')
                                ->default('Gratis')
                                ->required()
                                ->maxLength(50),
                            Textarea::make('description')
                                ->label('Ringkasan singkat (opsional)')
                                ->rows(2)
                                ->columnSpanFull(),
                        ])->columns(2),

                        Tabs\Tab::make('Rincian & Syarat')->schema([
                            RichEditor::make('requirements')
                                ->label('Persyaratan & Alur')
                                ->helperText('Tampil di halaman rincian layanan.')
                                ->columnSpanFull(),
                            RichEditor::make('legal_basis')
                                ->label('Dasar Hukum (opsional)')
                                ->columnSpanFull(),
                        ]),

                        Tabs\Tab::make('Field Formulir')->schema([
                            Repeater::make('fields')
                                ->label('Kelengkapan Berkas & Data Isian')
                                ->relationship()
                                ->orderColumn('sort_order')
                                ->reorderable()
                                ->collapsible()
                                ->itemLabel(fn (array $state): ?string => $state['label'] ?? 'Isian baru')
                                ->addActionLabel('Tambah isian / berkas')
                                ->columnSpanFull()
                                ->schema([
                                    TextInput::make('label')
                                        ->label('Nama isian')
                                        ->required()
                                        ->maxLength(255),
                                    Select::make('type')
                                        ->label('Tipe')
                                        ->options(FormField::TYPES)
                                        ->default('text')
                                        ->required()
                                        ->live(),
                                    TagsInput::make('options')
                                        ->label('Pilihan')
                                        ->helperText('Ketik lalu Enter untuk tiap pilihan.')
                                        ->visible(fn (Get $get): bool => in_array($get('type'), ['select', 'checkbox']))
                                        ->columnSpanFull(),
                                    TextInput::make('help_text')
                                        ->label('Keterangan (opsional)')
                                        ->maxLength(255)
                                        ->helperText('Tampil sebagai teks kecil di bawah isian.')
                                        ->columnSpanFull(),
                                    Toggle::make('required')
                                        ->label('Wajib diisi')
                                        ->default(false),
                                    Toggle::make('is_unique')
                                        ->label('Tidak boleh duplikat')
                                        ->helperText('Aktifkan untuk isian unik seperti NIS agar satu nilai hanya bisa dipakai sekali.')
                                        ->default(false),
                                ]),
                        ]),

                        Tabs\Tab::make('Pengaturan')->schema([
                            Select::make('status')
                                ->label('Status')
                                ->options(['draft' => 'Draft', 'published' => 'Terbit'])
                                ->default('draft')
                                ->required()
                                ->helperText('Hanya layanan berstatus Terbit yang muncul di katalog publik.'),
                            TextInput::make('sort_order')
                                ->label('Urutan')
                                ->numeric()
                                ->default(0)
                                ->helperText('Menentukan nomor kartu (#1, #2, ...) di katalog.'),
                            Textarea::make('success_message')
                                ->label('Pesan setelah kirim (opsional)')
                                ->rows(2)
                                ->columnSpanFull(),
                        ])->columns(2),
                    ]),
            ]);
    }
}

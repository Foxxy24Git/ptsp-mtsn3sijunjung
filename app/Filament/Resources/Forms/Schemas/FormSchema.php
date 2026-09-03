<?php

namespace App\Filament\Resources\Forms\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
                TextInput::make('title')
                    ->label('Judul Form')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug((string) $state))),
                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->helperText('Alamat form: /form/{slug}. Otomatis dari judul, bisa diubah.'),
                Textarea::make('description')
                    ->label('Deskripsi (opsional)')
                    ->rows(2)
                    ->columnSpanFull(),
                Textarea::make('success_message')
                    ->label('Pesan setelah kirim (opsional)')
                    ->rows(2)
                    ->placeholder('Terima kasih, jawaban Anda telah dikirim.')
                    ->columnSpanFull(),
                Select::make('status')
                    ->options(['draft' => 'Draft', 'published' => 'Published'])
                    ->default('draft')
                    ->required(),
                Repeater::make('fields')
                    ->label('Pertanyaan')
                    ->relationship()
                    ->orderColumn('sort_order')
                    ->reorderable()
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => $state['label'] ?? 'Pertanyaan baru')
                    ->addActionLabel('Tambah pertanyaan')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('label')
                            ->label('Pertanyaan')
                            ->required()
                            ->maxLength(255),
                        Select::make('type')
                            ->label('Tipe')
                            ->options([
                                'text' => 'Teks singkat',
                                'textarea' => 'Teks panjang',
                                'select' => 'Pilihan (dropdown)',
                                'checkbox' => 'Checkbox (pilih banyak)',
                                'file' => 'Upload file',
                            ])
                            ->default('text')
                            ->required()
                            ->live(),
                        TagsInput::make('options')
                            ->label('Pilihan')
                            ->helperText('Ketik lalu Enter untuk tiap pilihan.')
                            ->visible(fn (Get $get): bool => in_array($get('type'), ['select', 'checkbox']))
                            ->columnSpanFull(),
                        Toggle::make('required')
                            ->label('Wajib diisi')
                            ->default(false),
                        Toggle::make('is_unique')
                            ->label('Tidak boleh duplikat')
                            ->helperText('Aktifkan untuk field unik seperti Email/NIS agar satu nilai hanya bisa dipakai sekali. Biarkan mati untuk field yang boleh sama (Umur, Kelas, dll).')
                            ->default(false),
                    ]),
            ]);
    }
}

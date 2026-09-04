<?php

namespace App\Filament\Pages;

use App\Settings\GeneralSettings;
use BackedEnum;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ManageGeneralSettings extends SettingsPage
{
    protected static string $settings = GeneralSettings::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    /**
     * Urutan sidebar kelompok tanpa grup: Menus (10) → Pages (20) →
     * Kepuasan Layanan (30) → Pengaturan Situs (90). Nilainya ditulis
     * eksplisit supaya urutan tidak bergantung pada urutan penemuan file
     * oleh Filament.
     */
    protected static ?int $navigationSort = 90;

    protected static ?string $title = 'Pengaturan Situs';

    protected static ?string $navigationLabel = 'Pengaturan Situs';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identitas Situs')
                    ->description('Nama, logo, dan warna tema yang tampil di website.')
                    ->schema([
                        TextInput::make('site_name')
                            ->label('Nama Situs')
                            ->required()
                            ->maxLength(255),
                        FileUpload::make('logo')
                            ->label('Logo')
                            ->image()
                            ->directory('logos')
                            ->disk('public')
                            ->imageEditor(),
                        ColorPicker::make('primary_color')
                            ->label('Warna Tema')
                            ->required(),
                        ColorPicker::make('header_color')
                            ->label('Warna Header & Footer')
                            ->helperText('Warna latar bilah atas (header) sekaligus bagian bawah (footer). Teks otomatis menyesuaikan agar tetap terbaca.')
                            ->required(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Tata Letak Beranda')
                    ->description('Seret untuk mengubah urutan section, matikan tombol untuk menyembunyikan. Catatan: section tetap tersembunyi otomatis bila datanya masih kosong.')
                    ->schema([
                        Repeater::make('home_sections')
                            ->hiddenLabel()
                            ->addable(false)
                            ->deletable(false)
                            ->reorderableWithDragAndDrop()
                            ->itemLabel(fn (array $state): ?string => GeneralSettings::HOME_SECTIONS[$state['key'] ?? ''] ?? null)
                            ->schema([
                                Hidden::make('key'),
                                Toggle::make('visible')
                                    ->label('Tampilkan')
                                    ->default(true)
                                    ->inline(false),
                            ])
                            // Ditata menyamping supaya section ini tidak terlalu tinggi
                            // (sebelumnya 6 item bertumpuk bikin kolom sebelahnya kosong).
                            ->grid(['default' => 1, 'md' => 2, 'xl' => 3]),
                    ])
                    ->columnSpanFull(),
                Section::make('Section Hero (Beranda)')
                    ->description('Background hero di beranda. Hero ini tampil kalau belum ada Slide aktif -- kalau ada Slide aktif, yang tampil adalah slider bergambar.')
                    ->schema([
                        FileUpload::make('hero_bg_image')
                            ->label('Gambar Background Hero')
                            ->image()
                            ->directory('hero')
                            ->disk('public')
                            ->imageEditor()
                            ->helperText('Opsional. Kalau diisi, gambar ini dipakai sebagai latar (otomatis diberi lapisan gelap agar tulisan tetap terbaca). Kosongkan untuk pakai gradasi warna di bawah.')
                            ->columnSpanFull(),
                        ColorPicker::make('hero_bg_from')
                            ->label('Warna Gradasi Awal')
                            ->helperText('Dipakai kalau Gambar Background kosong. Kosongkan untuk mengikuti Warna Tema.'),
                        ColorPicker::make('hero_bg_to')
                            ->label('Warna Gradasi Akhir')
                            ->helperText('Kosongkan untuk otomatis memakai versi lebih gelap dari warna awal.'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Kontak')
                    ->description('Alamat dan kontak sekolah.')
                    ->schema([
                        Textarea::make('address')
                            ->label('Alamat')
                            ->rows(3)
                            ->columnSpanFull(),
                        TextInput::make('phone')
                            ->label('Telepon')
                            ->tel()
                            ->maxLength(50),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255),
                    ])
                    ->columns(2),
                Section::make('Sosial Media')
                    ->description('Tautan sosial media. Kosongkan untuk menyembunyikan ikonnya di website.')
                    ->schema([
                        TextInput::make('whatsapp')
                            ->label('WhatsApp')
                            ->tel()
                            ->helperText('Nomor diawali 0 atau 62 (mis. 081234567890). Otomatis menjadi tombol wa.me.')
                            ->maxLength(50),
                        TextInput::make('instagram')
                            ->label('Instagram')
                            ->url()
                            ->placeholder('https://instagram.com/namaakun')
                            ->maxLength(255),
                        TextInput::make('facebook')
                            ->label('Facebook')
                            ->url()
                            ->placeholder('https://facebook.com/namahalaman')
                            ->maxLength(255),
                        TextInput::make('youtube')
                            ->label('YouTube')
                            ->url()
                            ->placeholder('https://youtube.com/@namachannel')
                            ->maxLength(255),
                    ])
                    ->columns(2),
                Section::make('Section Zona Integritas (Beranda)')
                    ->description('Logo, background, dan tulisan section Zona Integritas di beranda. Tampil/urutan diatur di "Tata Letak Beranda" di atas.')
                    ->schema([
                        FileUpload::make('zi_logo')
                            ->label('Logo Zona Integritas')
                            ->image()
                            ->directory('zona-integritas')
                            ->disk('public')
                            ->imageEditor()
                            ->helperText('Opsional. Kosongkan untuk memakai ikon perisai bawaan.')
                            ->columnSpanFull(),
                        FileUpload::make('zi_bg_image')
                            ->label('Gambar Background')
                            ->image()
                            ->directory('zona-integritas')
                            ->disk('public')
                            ->imageEditor()
                            ->helperText('Opsional. Kalau diisi, gambar ini dipakai sebagai latar (otomatis diberi lapisan gelap agar tulisan tetap terbaca). Kosongkan untuk pakai gradasi warna di bawah.')
                            ->columnSpanFull(),
                        ColorPicker::make('zi_bg_from')
                            ->label('Warna Gradasi Awal')
                            ->required()
                            ->helperText('Dipakai kalau Gambar Background kosong.'),
                        ColorPicker::make('zi_bg_to')
                            ->label('Warna Gradasi Akhir')
                            ->required(),
                        TextInput::make('zi_eyebrow')
                            ->label('Label Kecil')
                            ->maxLength(255),
                        TextInput::make('zi_heading')
                            ->label('Judul')
                            ->maxLength(255),
                        Textarea::make('zi_description')
                            ->label('Deskripsi')
                            ->rows(3)
                            ->columnSpanFull(),
                        Repeater::make('zi_points')
                            ->label('Daftar Poin')
                            ->simple(
                                TextInput::make('point')
                                    ->required()
                                    ->maxLength(255)
                            )
                            ->addActionLabel('Tambah Poin')
                            ->reorderableWithDragAndDrop()
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['home_sections'] = GeneralSettings::normalizeSections($data['home_sections'] ?? []);

        return $data;
    }
}

<?php

namespace App\Filament\Petugas\Resources\Permohonan;

use App\Filament\Petugas\Resources\Permohonan\Pages\ListPermohonan;
use App\Filament\Resources\FormSubmissions\Tables\FormSubmissionsTable;
use App\Models\FormSubmission;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Tabelnya memakai ulang FormSubmissionsTable yang sama dengan panel admin
 * (kolom, filter, aksi Ubah Status & Detail identik) — satu sumber
 * kebenaran, lihat spec §2.4. Tidak ada halaman create/edit: satu-satunya
 * cara memproses permohonan adalah lewat aksi pada tabel ini.
 */
class PermohonanResource extends Resource
{
    protected static ?string $model = FormSubmission::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    /**
     * Wajib eksplisit: nama kelas ini akan dijamakkan Filament dengan aturan
     * bahasa Inggris ("Permohonans"), yang tidak cocok dengan nama folder
     * ("Permohonan") dan berakhir membuat URL dobel (`/permohonan/permohonans`)
     * kalau dibiarkan otomatis.
     */
    protected static ?string $slug = 'permohonan';

    protected static ?string $navigationLabel = 'Permohonan';

    protected static ?string $modelLabel = 'Permohonan';

    protected static ?string $pluralModelLabel = 'Permohonan';

    public static function table(Table $table): Table
    {
        return FormSubmissionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPermohonan::route('/'),
        ];
    }
}

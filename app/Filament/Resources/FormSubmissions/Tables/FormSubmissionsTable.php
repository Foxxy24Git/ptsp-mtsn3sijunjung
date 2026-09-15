<?php

namespace App\Filament\Resources\FormSubmissions\Tables;

use App\Actions\UpdateSubmissionStatus;
use App\Models\FormSubmission;
use App\Support\FormExcelExporter;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FormSubmissionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('receipt_code')
                    ->label('Kode Resi')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('applicant_name')
                    ->label('Pemohon')
                    ->searchable(),
                TextColumn::make('form.title')
                    ->label('Layanan')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Diajukan')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => FormSubmission::STATUSES[$state] ?? $state)
                    ->colors([
                        'gray' => 'diajukan',
                        'warning' => 'diproses',
                        'success' => 'selesai',
                        'danger' => 'ditolak',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(FormSubmission::STATUSES),
                SelectFilter::make('form_id')
                    ->label('Layanan')
                    ->relationship('form', 'title'),
            ])
            ->recordActions([
                Action::make('ubahStatus')
                    ->label('Ubah Status')
                    ->icon('heroicon-o-arrow-path')
                    ->modalHeading('Ubah Status Permohonan')
                    ->schema([
                        Select::make('status')
                            ->label('Status Baru')
                            ->options(FormSubmission::STATUSES)
                            ->live()
                            ->required(),
                        Textarea::make('admin_note')
                            ->label('Catatan untuk pemohon')
                            ->rows(3)
                            ->helperText('Tampil di halaman lacak, mis. "Ijazah siap diambil di TU".'),
                        FileUpload::make('result_document')
                            ->label('Dokumen Hasil (opsional)')
                            ->disk('local')
                            ->directory(fn (FormSubmission $record): string => 'permohonan/'.$record->form_id.'/hasil')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'application/pdf'])
                            ->maxSize(5120)
                            ->visible(fn (Get $get): bool => $get('status') === 'selesai')
                            ->helperText('Muncul untuk diunduh pemohon di halaman lacak begitu status disimpan sebagai Selesai.'),
                    ])
                    ->fillForm(fn (FormSubmission $record): array => [
                        'status' => $record->status,
                        'admin_note' => $record->admin_note,
                        'result_document' => $record->result_document_path,
                    ])
                    ->action(function (FormSubmission $record, array $data, UpdateSubmissionStatus $updater): void {
                        $updater->handle(
                            $record,
                            $data['status'],
                            $data['admin_note'] ?? null,
                            Auth::id(),
                            $data['result_document'] ?? null,
                        );
                    }),
                ViewAction::make()
                    ->label('Detail')
                    ->modalHeading('Detail Permohonan')
                    // ViewAction bawaan selalu ikut merender schema form
                    // resource (semua kolom mentah) setelah modalContent
                    // kecuali skemanya dikosongkan eksplisit di sini.
                    ->schema([])
                    ->modalContent(fn ($record): View => view(
                        'filament.form-submission-detail',
                        ['submission' => $record],
                    )),
            ])
            ->toolbarActions([
                Action::make('export')
                    ->label('Export Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(fn (): BinaryFileResponse => FormExcelExporter::downloadAll()),
            ]);
    }
}

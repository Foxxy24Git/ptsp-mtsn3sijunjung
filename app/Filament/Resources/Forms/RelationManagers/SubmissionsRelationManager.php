<?php
// app/Filament/Resources/Forms/RelationManagers/SubmissionsRelationManager.php
namespace App\Filament\Resources\Forms\RelationManagers;

use App\Support\FormCsvExporter;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubmissionsRelationManager extends RelationManager
{
    protected static string $relationship = 'submissions';

    protected static ?string $title = 'Jawaban';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('#'),
                TextColumn::make('created_at')
                    ->label('Dikirim')
                    ->dateTime('d M Y H:i'),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                Action::make('export')
                    ->label('Export CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(fn (): StreamedResponse => FormCsvExporter::download($this->getOwnerRecord())),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Lihat')
                    ->modalHeading('Detail Jawaban')
                    ->modalContent(fn ($record): View => view(
                        'filament.form-submission-detail',
                        ['submission' => $record],
                    )),
                DeleteAction::make(),
            ]);
    }
}

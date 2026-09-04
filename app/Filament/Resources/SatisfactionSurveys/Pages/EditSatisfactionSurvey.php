<?php

namespace App\Filament\Resources\SatisfactionSurveys\Pages;

use App\Filament\Resources\SatisfactionSurveys\SatisfactionSurveyResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSatisfactionSurvey extends EditRecord
{
    protected static string $resource = SatisfactionSurveyResource::class;

    protected static ?string $navigationLabel = 'Ubah Survei';

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

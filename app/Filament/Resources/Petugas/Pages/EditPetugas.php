<?php

namespace App\Filament\Resources\Petugas\Pages;

use App\Filament\Resources\Petugas\PetugasResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPetugas extends EditRecord
{
    protected static string $resource = PetugasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

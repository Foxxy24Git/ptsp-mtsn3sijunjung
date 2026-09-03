<?php

namespace App\Filament\Resources\Petugas\Pages;

use App\Filament\Resources\Petugas\PetugasResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPetugas extends ListRecords
{
    protected static string $resource = PetugasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\WorkUnits\Pages;

use App\Filament\Resources\WorkUnits\WorkUnitResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWorkUnits extends ListRecords
{
    protected static string $resource = WorkUnitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

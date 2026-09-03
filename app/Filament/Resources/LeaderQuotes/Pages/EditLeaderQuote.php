<?php

namespace App\Filament\Resources\LeaderQuotes\Pages;

use App\Filament\Resources\LeaderQuotes\LeaderQuoteResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLeaderQuote extends EditRecord
{
    protected static string $resource = LeaderQuoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

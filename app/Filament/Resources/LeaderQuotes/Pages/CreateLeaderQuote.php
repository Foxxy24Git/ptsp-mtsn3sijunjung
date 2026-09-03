<?php

namespace App\Filament\Resources\LeaderQuotes\Pages;

use App\Filament\Resources\LeaderQuotes\LeaderQuoteResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLeaderQuote extends CreateRecord
{
    protected static string $resource = LeaderQuoteResource::class;
}

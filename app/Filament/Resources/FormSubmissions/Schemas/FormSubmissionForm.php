<?php

namespace App\Filament\Resources\FormSubmissions\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class FormSubmissionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('form_id')
                    ->relationship('form', 'title')
                    ->required(),
                TextInput::make('data')
                    ->required(),
                TextInput::make('receipt_code'),
                TextInput::make('applicant_name'),
                TextInput::make('applicant_whatsapp'),
                TextInput::make('applicant_email')
                    ->email(),
                TextInput::make('status')
                    ->required()
                    ->default('diajukan'),
                Textarea::make('admin_note')
                    ->columnSpanFull(),
            ]);
    }
}

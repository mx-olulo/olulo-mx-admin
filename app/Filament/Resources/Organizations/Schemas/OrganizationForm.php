<?php

namespace App\Filament\Resources\Organizations\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class OrganizationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('contact_email')
                    ->email(),
                TextInput::make('contact_phone')
                    ->tel(),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}

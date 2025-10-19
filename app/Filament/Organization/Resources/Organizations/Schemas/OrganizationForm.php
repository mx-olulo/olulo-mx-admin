<?php

declare(strict_types=1);

// @CODE:I18N-001 | SPEC: SPEC-I18N-001.md | TEST: tests/Feature/Filament/OrganizationResourceI18nTest.php

namespace App\Filament\Organization\Resources\Organizations\Schemas;

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
                    ->label(__('filament.organizations.fields.name'))
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->label(__('filament.organizations.fields.description'))
                    ->maxLength(65535)
                    ->columnSpanFull(),
                TextInput::make('contact_email')
                    ->label(__('filament.organizations.fields.contact_email'))
                    ->email()
                    ->maxLength(255),
                TextInput::make('contact_phone')
                    ->label(__('filament.organizations.fields.contact_phone'))
                    ->tel()
                    ->maxLength(255),
                Toggle::make('is_active')
                    ->label(__('filament.organizations.fields.is_active'))
                    ->default(true)
                    ->required(),
            ]);
    }
}

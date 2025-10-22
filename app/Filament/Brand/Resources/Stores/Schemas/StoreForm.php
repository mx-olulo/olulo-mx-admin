<?php

declare(strict_types=1);

/**
 * @CODE:BRAND-STORE-MGMT-001 | SPEC: SPEC-BRAND-STORE-MGMT-001.md
 */

namespace App\Filament\Brand\Resources\Stores\Schemas;

use App\Enums\RelationshipType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class StoreForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('filament.stores.fields.name'))
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->label(__('filament.stores.fields.description'))
                    ->maxLength(65535)
                    ->columnSpanFull(),
                TextInput::make('address')
                    ->label(__('filament.stores.fields.address'))
                    ->maxLength(65535)
                    ->columnSpanFull(),
                TextInput::make('phone')
                    ->label(__('filament.stores.fields.phone'))
                    ->tel()
                    ->maxLength(255),
                Select::make('relationship_type')
                    ->label(__('filament.stores.fields.relationship_type'))
                    ->options([
                        RelationshipType::OWNED->value => RelationshipType::OWNED->label(),
                        RelationshipType::TENANT->value => RelationshipType::TENANT->label(),
                    ])
                    ->default(RelationshipType::OWNED->value)
                    ->required(),
                Toggle::make('is_active')
                    ->label(__('filament.stores.fields.is_active'))
                    ->default(true)
                    ->required(),
            ]);
    }
}

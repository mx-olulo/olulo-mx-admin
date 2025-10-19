<?php

declare(strict_types=1);

/**
 * @CODE:BRAND-STORE-MGMT-001 | SPEC: SPEC-BRAND-STORE-MGMT-001.md
 */

namespace App\Filament\Brand\Resources\Stores\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class StoreInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')
                    ->label(__('filament.stores.fields.name')),
                TextEntry::make('description')
                    ->label(__('filament.stores.fields.description'))
                    ->columnSpanFull(),
                TextEntry::make('address')
                    ->label(__('filament.stores.fields.address'))
                    ->columnSpanFull(),
                TextEntry::make('phone')
                    ->label(__('filament.stores.fields.phone')),
                TextEntry::make('relationship_type')
                    ->label(__('filament.stores.fields.relationship_type'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state->label())
                    ->color(fn ($state) => $state->color()),
                TextEntry::make('is_active')
                    ->label(__('filament.stores.fields.is_active'))
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? __('filament.common.status.active') : __('filament.common.status.inactive'))
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                TextEntry::make('created_at')
                    ->label(__('filament.stores.fields.created_at'))
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->label(__('filament.stores.fields.updated_at'))
                    ->dateTime(),
            ]);
    }
}

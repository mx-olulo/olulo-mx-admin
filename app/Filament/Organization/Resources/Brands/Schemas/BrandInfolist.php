<?php

declare(strict_types=1);

/**
 * @CODE:BRAND-STORE-MGMT-001 | SPEC: SPEC-BRAND-STORE-MGMT-001.md
 */

namespace App\Filament\Organization\Resources\Brands\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class BrandInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')
                    ->label(__('filament.brands.fields.name')),
                TextEntry::make('description')
                    ->label(__('filament.brands.fields.description'))
                    ->columnSpanFull(),
                TextEntry::make('relationship_type')
                    ->label(__('filament.brands.fields.relationship_type'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state->label())
                    ->color(fn ($state) => $state->color()),
                TextEntry::make('is_active')
                    ->label(__('filament.brands.fields.is_active'))
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? __('filament.common.status.active') : __('filament.common.status.inactive'))
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                TextEntry::make('created_at')
                    ->label(__('filament.brands.fields.created_at'))
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->label(__('filament.brands.fields.updated_at'))
                    ->dateTime(),
            ]);
    }
}

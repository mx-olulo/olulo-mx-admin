<?php

declare(strict_types=1);

/**
 * @CODE:BRAND-STORE-MGMT-001 | SPEC: SPEC-BRAND-STORE-MGMT-001.md
 */

namespace App\Filament\Brand\Resources\Stores\Pages;

use App\Filament\Brand\Resources\Stores\StoreResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewStore extends ViewRecord
{
    protected static string $resource = StoreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}

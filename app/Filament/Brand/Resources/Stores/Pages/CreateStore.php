<?php

declare(strict_types=1);

/**
 * @CODE:BRAND-STORE-MGMT-001 | SPEC: SPEC-BRAND-STORE-MGMT-001.md
 */

namespace App\Filament\Brand\Resources\Stores\Pages;

use App\Filament\Brand\Resources\Stores\StoreResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStore extends CreateRecord
{
    protected static string $resource = StoreResource::class;
}

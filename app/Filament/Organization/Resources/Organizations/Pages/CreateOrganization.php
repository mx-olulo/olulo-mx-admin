<?php

declare(strict_types=1);

namespace App\Filament\Organization\Resources\Organizations\Pages;

use App\Filament\Organization\Resources\Organizations\OrganizationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOrganization extends CreateRecord
{
    protected static string $resource = OrganizationResource::class;
}

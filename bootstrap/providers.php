<?php

declare(strict_types=1);

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\Filament\OrganizationPanelProvider::class,
    App\Providers\Filament\StorePanelProvider::class,
    App\Providers\Filament\BrandPanelProvider::class,
    App\Providers\Filament\PlatformPanelProvider::class,
    App\Providers\Filament\SystemPanelProvider::class,
    App\Providers\FirebaseAuthServiceProvider::class,
    App\Providers\NovaServiceProvider::class,
];

<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Enums\ScopeType;
use App\Models\Organization;
use App\Providers\Filament\Concerns\ConfiguresFilamentPanel;
use App\Http\Middleware\SetOrganizationContext;
use Filament\Panel;
use Filament\PanelProvider;

class OrganizationPanelProvider extends PanelProvider
{
    use ConfiguresFilamentPanel;

    public function panel(Panel $panel): Panel
    {
        $scopeType = ScopeType::ORGANIZATION;

        $panel = $this->applyCommonConfiguration($panel, $scopeType);

        return $panel
            ->id($scopeType->getPanelId())
            ->path($scopeType->getPanelId())
            ->tenant(Organization::class)
            ->tenantMiddleware([
                SetOrganizationContext::class,
            ], isPersistent: true)
            ->discoverResources(in: app_path('Filament/Organization/Resources'), for: 'App\\Filament\\Organization\\Resources')
            ->discoverPages(in: app_path('Filament/Organization/Pages'), for: 'App\\Filament\\Organization\\Pages')
            ->discoverWidgets(in: app_path('Filament/Organization/Widgets'), for: 'App\\Filament\\Organization\\Widgets');
    }
}

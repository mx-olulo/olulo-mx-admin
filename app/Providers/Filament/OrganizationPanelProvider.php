<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Enums\ScopeType;
use App\Filament\Organization\Pages\OnboardingWizard;
use App\Models\Organization;
use App\Providers\Filament\Concerns\ConfiguresFilamentPanel;
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
            ->tenant(Organization::class)
            ->tenantRegistration(OnboardingWizard::class)
            ->discoverResources(
                in: app_path('Filament/Organization/Resources'),
                for: "App\Filament\Organization\Resources",
            )
            ->pages([
                \App\Filament\Organization\Pages\Dashboard::class,
            ])
            ->discoverWidgets(
                in: app_path('Filament/Organization/Widgets'),
                for: "App\Filament\Organization\Widgets",
            );
    }
}

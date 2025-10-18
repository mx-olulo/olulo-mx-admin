<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Enums\ScopeType;
use App\Filament\Store\Pages\OnboardingWizard;
use App\Models\Store;
use App\Providers\Filament\Concerns\ConfiguresFilamentPanel;
use Filament\Panel;
use Filament\PanelProvider;

class StorePanelProvider extends PanelProvider
{
    use ConfiguresFilamentPanel;

    public function panel(Panel $panel): Panel
    {
        $scopeType = ScopeType::STORE;

        $panel = $this->applyCommonConfiguration($panel, $scopeType);

        return $panel
            ->default()
            ->tenant(Store::class)
            ->tenantRegistration(OnboardingWizard::class)
            ->discoverResources(
                in: app_path('Filament/Store/Resources'),
                for: "App\Filament\Store\Resources",
            )
            ->pages([
                \App\Filament\Store\Pages\Dashboard::class,
            ])
            ->discoverWidgets(
                in: app_path('Filament/Store/Widgets'),
                for: "App\Filament\Store\Widgets",
            );
    }
}

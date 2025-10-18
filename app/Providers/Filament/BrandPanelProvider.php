<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Enums\ScopeType;
use App\Models\Brand;
use App\Providers\Filament\Concerns\ConfiguresFilamentPanel;
use Filament\Panel;
use Filament\PanelProvider;

class BrandPanelProvider extends PanelProvider
{
    use ConfiguresFilamentPanel;

    public function panel(Panel $panel): Panel
    {
        $scopeType = ScopeType::BRAND;

        $panel = $this->applyCommonConfiguration($panel, $scopeType);

        return $panel
            ->tenant(Brand::class)
            ->discoverResources(
                in: app_path('Filament/Brand/Resources'),
                for: "App\Filament\Brand\Resources",
            )
            ->discoverPages(
                in: app_path('Filament/Brand/Pages'),
                for: "App\Filament\Brand\Pages",
            )
            ->discoverWidgets(
                in: app_path('Filament/Brand/Widgets'),
                for: "App\Filament\Brand\Widgets",
            );
    }
}

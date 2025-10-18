<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Enums\ScopeType;
use App\Providers\Filament\Concerns\ConfiguresFilamentPanel;
use Filament\Panel;
use Filament\PanelProvider;

class SystemPanelProvider extends PanelProvider
{
    use ConfiguresFilamentPanel;

    public function panel(Panel $panel): Panel
    {
        $scopeType = ScopeType::SYSTEM;

        $panel = $this->applyCommonConfiguration($panel, $scopeType);

        return $panel
            ->discoverResources(
                in: app_path('Filament/System/Resources'),
                for: "App\Filament\System\Resources",
            )
            ->discoverPages(
                in: app_path('Filament/System/Pages'),
                for: "App\Filament\System\Pages",
            )
            ->discoverWidgets(
                in: app_path('Filament/System/Widgets'),
                for: "App\Filament\System\Widgets",
            );
    }
}

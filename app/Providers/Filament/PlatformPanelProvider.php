<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Enums\ScopeType;
use App\Providers\Filament\Concerns\ConfiguresFilamentPanel;
use Filament\Panel;
use Filament\PanelProvider;

class PlatformPanelProvider extends PanelProvider
{
    use ConfiguresFilamentPanel;

    public function panel(Panel $panel): Panel
    {
        $scopeType = ScopeType::PLATFORM;

        $panel = $this->applyCommonConfiguration($panel, $scopeType);

        return $panel
            ->id($scopeType->getPanelId())
            ->path($scopeType->getPanelId())
            ->discoverResources(in: app_path('Filament/Platform/Resources'), for: 'App\Filament\Platform\Resources')
            ->discoverPages(in: app_path('Filament/Platform/Pages'), for: 'App\Filament\Platform\Pages')
            ->discoverWidgets(in: app_path('Filament/Platform/Widgets'), for: 'App\Filament\Platform\Widgets');
    }
}

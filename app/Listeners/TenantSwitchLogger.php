<?php

declare(strict_types=1);

namespace App\Listeners;

use Filament\Events\TenantSet;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * 테넌트 전환 이벤트 리스너
 *
 * Filament의 TenantSet 이벤트를 감지하여 Spatie Activity Log 기록
 * GDPR/컴플라이언스 요구사항 충족
 */
class TenantSwitchLogger implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(TenantSet $tenantSet): void
    {
        $user = auth()->user();
        $model = $tenantSet->getTenant();

        if (! $user) {
            return;
        }

        // Spatie Activity Log 기록
        activity()
            ->causedBy($user)
            ->performedOn($model)
            ->withProperties([
                'tenant_type' => $model::class,
                'tenant_id' => $model->getKey(),
                'tenant_name' => $model->name ?? 'Unknown',
                'panel_id' => \Filament\Facades\Filament::getCurrentPanel()?->getId(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('tenant_switched');
    }
}

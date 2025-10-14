<?php

declare(strict_types=1);

namespace App\Listeners;

use Filament\Events\TenantSet;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * 테넌트 전환 이벤트 리스너
 *
 * Filament의 TenantSet 이벤트를 감지하여 Spatie Activity Log 기록
 * GDPR/컴플라이언스 요구사항 충족
 */
class TenantSwitchLogger implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * 재시도 횟수
     */
    public int $tries = 3;

    /**
     * 재시도 대기 시간 (초)
     */
    public int $backoff = 60;

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

        try {
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
        } catch (\Throwable $e) {
            Log::error('테넌트 전환 로깅 실패', [
                'tenant_type' => $model::class,
                'tenant_id' => $model->getKey(),
                'user_id' => $user->getKey(),
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            // 재시도 초과 시 실패 처리
            if ($this->attempts() >= $this->tries) {
                $this->fail($e);
            }

            throw $e; // 재시도 트리거
        }
    }

    /**
     * 큐 작업 최종 실패 시 처리
     */
    public function failed(\Throwable $throwable): void
    {
        Log::critical('테넌트 전환 로깅 최종 실패 - 수동 확인 필요', [
            'exception' => $throwable->getMessage(),
            'trace' => $throwable->getTraceAsString(),
        ]);
    }
}

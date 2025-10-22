<?php

declare(strict_types=1);

namespace App\Providers;

use App\Listeners\TenantSwitchLogger;
use Filament\Events\TenantSet;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 다형 관계 타입 매핑 (Role scopeable 관계용)
        // ScopeType enum에서 중앙 관리
        /** @var array<string, class-string<\Illuminate\Database\Eloquent\Model>> */
        $morphMap = \App\Enums\ScopeType::getMorphMap();
        Relation::morphMap($morphMap);

        // Filament 테넌트 전환 이벤트 리스너 (감사 로그)
        Event::listen(TenantSet::class, TenantSwitchLogger::class);
    }
}

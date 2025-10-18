<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enums\ScopeType;
use App\Listeners\TenantSwitchLogger;
use App\Models\User;
use Filament\Events\TenantSet;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
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

        // Gate: PLATFORM/SYSTEM 스코프 사용자는 모든 권한 부여
        // CacheUserGlobalScope 미들웨어에서 요청 속성에 캐싱된 값 활용
        // 매 권한 체크마다 isAdminPanel() + hasGlobalScopeRole() 호출을 방지하여 성능 최적화
        Gate::before(function (User $user, string $ability) {
            // 미들웨어에서 캐싱한 글로벌 스코프 여부 확인 (DB 쿼리 없음)
            if (request()->attributes->get('user_has_global_scope')) {
                return true;
            }

            // 그 외에는 명시적 권한 체크로 위임 (return null)
        });
    }
}

<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enums\ScopeType;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
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

        // Gate: PLATFORM/SYSTEM 스코프 사용자는 모든 권한 부여
        // 주의: 관리자 패널(Filament/Nova)에서만 작동하도록 제한
        // 프론트엔드 고객 앱에서는 불필요한 DB 쿼리를 방지
        Gate::before(function (User $user, string $ability) {
            // 관리자 패널(Filament/Nova)에서만 글로벌 스코프 체크
            // 고객 앱 요청은 이 체크를 건너뛰어 성능 최적화
            // 글로벌 스코프(PLATFORM/SYSTEM) 역할 보유 시 모든 권한 허용
            if ($this->isAdminPanel() && $user->hasGlobalScopeRole()) {
                return true;
            }

            // 그 외에는 명시적 권한 체크로 위임 (return null)
        });
    }

    /**
     * 현재 요청이 관리자 패널(Filament/Nova)인지 확인
     *
     * Filament/Nova는 특정 URL prefix를 사용하므로 이를 기반으로 판단
     * 고객 앱은 이 체크를 통과하지 못하여 불필요한 DB 쿼리를 방지
     */
    protected function isAdminPanel(): bool
    {
        if (! app()->runningInConsole() && request()) {
            $path = request()->path();

            // Filament 패널 경로 확인 (org, brand, store, platform, system, admin)
            // Nova 패널 경로 확인 (nova)
            return str_starts_with($path, 'org/') ||
                   str_starts_with($path, 'brand/') ||
                   str_starts_with($path, 'store/') ||
                   str_starts_with($path, 'platform/') ||
                   str_starts_with($path, 'system/') ||
                   str_starts_with($path, 'admin/') ||
                   str_starts_with($path, 'nova/') ||
                   str_starts_with($path, 'nova-api/');
        }

        return false;
    }
}

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
        // User 모델의 hasGlobalScopeRole() 헬퍼 메서드 활용
        // - Eloquent 관계 활용으로 가독성 향상
        // - once() 헬퍼로 요청당 1회만 DB 쿼리 실행
        Gate::before(function (User $user, string $ability) {
            // 글로벌 스코프(PLATFORM/SYSTEM) 역할 보유 시 모든 권한 허용
            if ($user->hasGlobalScopeRole()) {
                return true;
            }

            // 그 외에는 명시적 권한 체크로 위임 (return null)
        });
    }
}

<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enums\ScopeType;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
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
        Gate::before(function (User $user, string $ability) {
            // Spatie Permission의 team context와 무관하게 사용자의 모든 역할 확인
            // DB 직접 쿼리로 team_id context 우회
            $hasGlobalScope = DB::table('model_has_roles')
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->where('model_has_roles.model_type', User::class)
                ->where('model_has_roles.model_id', $user->id)
                ->whereIn('roles.scope_type', [
                    ScopeType::PLATFORM->value,
                    ScopeType::SYSTEM->value,
                ])
                ->exists();

            // 글로벌 스코프가 있으면 모든 권한 허용
            if ($hasGlobalScope) {
                return true;
            }

            // 그 외에는 명시적 권한 체크로 위임 (return null)
        });
    }
}

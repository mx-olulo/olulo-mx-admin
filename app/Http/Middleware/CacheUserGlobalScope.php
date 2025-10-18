<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 글로벌 스코프 사용자 여부를 요청 속성에 캐싱
 *
 * Gate::before에서 매번 isAdminPanel() + hasGlobalScopeRole() 호출을 방지
 * 요청 시작 시 한 번만 계산하여 성능 최적화
 */
class CacheUserGlobalScope
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User) {
            // 관리자 패널 여부 체크 (한 번만)
            $isAdminPanel = $this->isAdminPanel($request);

            // 글로벌 스코프 여부 체크 (관리자 패널인 경우에만)
            $hasGlobalScope = $isAdminPanel && $user->hasGlobalScopeRole();

            // 요청 속성에 캐싱 (Gate::before에서 재사용)
            $request->attributes->set('user_has_global_scope', $hasGlobalScope);
        }

        return $next($request);
    }

    /**
     * 현재 요청이 관리자 패널(Filament/Nova)인지 확인
     *
     * Filament/Nova는 특정 URL prefix를 사용하므로 이를 기반으로 판단
     * 고객 앱은 이 체크를 통과하지 못하여 불필요한 DB 쿼리를 방지
     */
    protected function isAdminPanel(Request $request): bool
    {
        $path = $request->path();

        // Filament 패널 경로 확인 (org, brand, store, platform, system, admin)
        // Nova 패널 경로 확인 (nova)
        return str_starts_with($path, 'org/') ||
               str_starts_with($path, 'brand/') ||
               str_starts_with($path, 'store/') ||
               str_starts_with($path, 'platform/') ||
               str_starts_with($path, 'system/') ||
               str_starts_with($path, 'admin/') ||
               str_starts_with($path, 'nova/') ||
               $path === 'org' ||
               $path === 'brand' ||
               $path === 'store' ||
               $path === 'platform' ||
               $path === 'system' ||
               $path === 'admin' ||
               $path === 'nova';
    }
}

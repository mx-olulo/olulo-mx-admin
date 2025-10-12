<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Role;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SetSpatieTeamId Middleware
 *
 * Filament Tenancy와 Spatie Permission을 통합하고 스코프를 검증합니다.
 * 1. Filament가 관리하는 현재 테넌트(Role)의 team_id를 Spatie Permission에 설정
 * 2. Panel의 scope_type과 Role의 scope_type이 일치하는지 검증
 */
class SetSpatieTeamId
{
    /**
     * Handle an incoming request.
     *
     * @param  string|null  $scopeType  검증할 scope_type (선택적)
     */
    public function handle(Request $request, Closure $next, ?string $scopeType = null): Response
    {
        // Filament가 관리하는 현재 테넌트(Role) 가져오기
        $tenant = Filament::getTenant();

        if ($tenant instanceof Role) {
            // 1. Spatie Permission에 team_id 설정
            setPermissionsTeamId($tenant->team_id);

            // 2. 캐시된 관계 초기화 (Spatie 공식 권장)
            // 팀 전환 시 이전 팀의 roles/permissions 캐시를 제거하여
            // 새로운 팀의 권한이 올바르게 로드되도록 보장
            if ($user = $request->user()) {
                $user->unsetRelation('roles')->unsetRelation('permissions');
            }

            // 3. scope_type 검증 (파라미터가 제공된 경우)
            if ($scopeType && $tenant->scope_type !== $scopeType) {
                abort(403, "This panel requires {$scopeType} scope, but your role has {$tenant->scope_type} scope.");
            }
        }

        return $next($request);
    }
}

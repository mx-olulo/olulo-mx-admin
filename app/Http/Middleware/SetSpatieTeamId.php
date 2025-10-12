<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\ScopeType;
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
 * 2. Panel의 scope_type과 Role의 scope_type이 일치하는지 검증 (Enum 기반)
 *
 * @see \App\Enums\ScopeType 유효한 스코프 타입 정의
 */
class SetSpatieTeamId
{
    /**
     * Handle an incoming request.
     *
     * Filament 테넌트(Role)의 team_id를 Spatie Permission에 설정하고,
     * Panel에서 요구하는 scope_type과 Role의 scope_type이 일치하는지 검증합니다.
     *
     * @param  string|null  $scopeType  검증할 scope_type (ScopeType Enum 값, 예: 'STORE', 'BRAND')
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException 403 에러 (scope 불일치 시)
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
            if ($scopeType) {
                $this->validateScopeType($tenant, $scopeType);
            }
        }

        return $next($request);
    }

    /**
     * 테넌트의 scope_type이 요구되는 scope_type과 일치하는지 검증
     *
     * @param  \App\Models\Role  $tenant  현재 테넌트(Role)
     * @param  string  $requiredScopeType  요구되는 scope_type 문자열
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException 403 에러
     */
    private function validateScopeType(Role $tenant, string $requiredScopeType): void
    {
        // Enum으로 변환하여 타입 안정성 확보
        $expectedScope = ScopeType::tryFrom($requiredScopeType);
        $actualScope = $tenant->scope_type !== null ? ScopeType::tryFrom($tenant->scope_type) : null;

        // 1. 잘못된 scope_type이 미들웨어 파라미터로 전달된 경우
        if (! $expectedScope) {
            abort(500, "Invalid scope type parameter: {$requiredScopeType}. Valid types: " . implode(', ', ScopeType::values()));
        }

        // 2. Role의 scope_type이 유효하지 않은 경우 (데이터 정합성 문제)
        if (! $actualScope) {
            abort(500, "Invalid scope type in role: {$tenant->scope_type}. Please contact administrator.");
        }

        // 3. scope_type 불일치
        if ($expectedScope !== $actualScope) {
            abort(
                403,
                "Access denied. This panel requires {$expectedScope->value} scope, but your role has {$actualScope->value} scope."
            );
        }
    }
}

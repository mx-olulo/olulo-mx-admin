<?php

declare(strict_types=1);

/**
 * @CODE:AUTH-REDIRECT-001:DOMAIN | SPEC: .moai/specs/SPEC-AUTH-REDIRECT-001/spec.md
 *
 * 테넌트 계류페이지 서비스
 *
 * TDD History:
 * - REFACTOR (2025-10-19): TenantSelectorController에서 비즈니스 로직 분리 (LOC 제약 준수)
 *
 * 책임:
 * - 사용자 소속 테넌트 목록 조회 (Organization/Store/Brand)
 * - 테넌트 접근 권한 검증
 */

namespace App\Services;

use App\Enums\ScopeType;
use App\Models\User;

class TenantSelectorService
{
    /**
     * 사용자의 모든 테넌트 멤버십 조회
     *
     * Organization/Store/Brand별로 그룹화하여 반환
     *
     * @param  User  $user  인증된 사용자
     * @return array{organizations: \Illuminate\Database\Eloquent\Collection<int, \App\Models\Organization>, stores: \Illuminate\Database\Eloquent\Collection<int, \App\Models\Store>, brands: \Illuminate\Database\Eloquent\Collection<int, \App\Models\Brand>}
     */
    public function getUserTenants(User $user): array
    {
        // 사용자의 역할 조회 (Spatie Permission team_id 필터 우회)
        $roleIds = \DB::table('model_has_roles')
            ->where('model_id', $user->getKey())
            ->where('model_type', \App\Models\User::class)
            ->pluck('role_id');

        // 각 ScopeType별 테넌트 ID 목록 조회
        $orgIds = \DB::table('roles')
            ->whereIn('id', $roleIds)
            ->where('scope_type', ScopeType::ORGANIZATION->value)
            ->pluck('scope_ref_id')
            ->unique();
        $organizations = \App\Models\Organization::whereIn('id', $orgIds)->get();

        $storeIds = \DB::table('roles')
            ->whereIn('id', $roleIds)
            ->where('scope_type', ScopeType::STORE->value)
            ->pluck('scope_ref_id')
            ->unique();
        $stores = \App\Models\Store::whereIn('id', $storeIds)->get();

        $brandIds = \DB::table('roles')
            ->whereIn('id', $roleIds)
            ->where('scope_type', ScopeType::BRAND->value)
            ->pluck('scope_ref_id')
            ->unique();
        $brands = \App\Models\Brand::whereIn('id', $brandIds)->get();

        return [
            'organizations' => $organizations,
            'stores' => $stores,
            'brands' => $brands,
        ];
    }

    /**
     * 테넌트 접근 권한 검증
     *
     * 사용자가 선택한 테넌트에 대한 멤버십 권한을 확인합니다.
     *
     * @param  User  $user  인증된 사용자
     * @param  ScopeType  $scopeType  테넌트 타입
     * @param  int  $id  테넌트 ID
     * @return bool 접근 권한 여부
     */
    public function canAccessTenant(User $user, ScopeType $scopeType, int $id): bool
    {
        // 사용자의 역할 조회 (Spatie Permission team_id 필터 우회)
        $roleIds = \DB::table('model_has_roles')
            ->where('model_id', $user->getKey())
            ->where('model_type', \App\Models\User::class)
            ->pluck('role_id');

        return \DB::table('roles')
            ->whereIn('id', $roleIds)
            ->where('scope_type', $scopeType->value)
            ->where('scope_ref_id', $id)
            ->exists();
    }
}

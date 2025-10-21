<?php

declare(strict_types=1);

namespace App\Policies;

/**
 * @CODE:RBAC-001 | SPEC: SPEC-RBAC-001.md
 *
 * Brand Policy - tenant_users 기반 권한 체계
 *
 * 1. TenantUser 역할: owner, manager, viewer
 * 2. Filament Tenant: 리소스 소유권
 * 3. RelationshipType: tenant 관계 삭제 차단
 *
 * 역할별 권한:
 * - owner: 모든 작업 가능 (생성, 조회, 수정, 삭제)
 * - manager: 생성, 조회, 수정 가능
 * - viewer: 조회만 가능
 */

use App\Models\Brand;
use App\Models\User;

class BrandPolicy
{
    /**
     * Brand 목록 조회 권한 확인
     *
     * Organization에 대한 접근 권한이 있으면 Brand 목록 조회 가능
     */
    public function viewAny(User $user): bool
    {
        // Filament 컨텍스트에서 현재 테넌트 확인
        $tenant = \Filament\Facades\Filament::getTenant();

        if (! $tenant instanceof \App\Models\Organization) {
            return false;
        }

        return $user->canViewTenant($tenant);
    }

    /**
     * 특정 Brand 조회 권한 확인
     *
     * Organization에 대한 조회 권한이 있으면 Brand 조회 가능
     */
    public function view(User $user, Brand $brand): bool
    {
        if (! $brand->organization instanceof \App\Models\Organization) {
            return false;
        }

        return $user->canViewTenant($brand->organization);
    }

    /**
     * Brand 생성 권한 확인
     *
     * Organization에 대한 관리 권한이 있으면 Brand 생성 가능
     */
    public function create(User $user): bool
    {
        $tenant = \Filament\Facades\Filament::getTenant();

        if (! $tenant instanceof \App\Models\Organization) {
            return false;
        }

        return $user->canManageTenant($tenant);
    }

    /**
     * Brand 수정 권한 확인
     *
     * Organization에 대한 관리 권한이 있으면 Brand 수정 가능
     */
    public function update(User $user, Brand $brand): bool
    {
        if (! $brand->organization instanceof \App\Models\Organization) {
            return false;
        }

        return $user->canManageTenant($brand->organization);
    }

    /**
     * Brand 삭제 권한 확인
     *
     * - owner 역할만 삭제 가능
     * - tenant 관계: 삭제 불가
     * - 활성 Store 보유: 삭제 불가
     */
    public function delete(User $user, Brand $brand): bool
    {
        if (! $brand->organization instanceof \App\Models\Organization) {
            return false;
        }

        // owner 역할만 삭제 가능
        if (! $user->hasRoleForTenant($brand->organization, 'owner')) {
            return false;
        }

        // tenant 관계는 삭제 불가
        if (! $brand->relationship_type->isDeletable()) {
            return false;
        }

        // 활성 Store가 있으면 삭제 불가
        return ! $brand->hasActiveStores();
    }

    /**
     * Brand 복원 권한 확인
     *
     * Organization에 대한 관리 권한이 있으면 Brand 복원 가능
     */
    public function restore(User $user, Brand $brand): bool
    {
        if (! $brand->organization instanceof \App\Models\Organization) {
            return false;
        }

        return $user->canManageTenant($brand->organization);
    }

    /**
     * Brand 영구 삭제 권한 확인
     *
     * owner 역할만 영구 삭제 가능
     */
    public function forceDelete(User $user, Brand $brand): bool
    {
        if (! $brand->organization instanceof \App\Models\Organization) {
            return false;
        }

        return $user->hasRoleForTenant($brand->organization, 'owner');
    }
}

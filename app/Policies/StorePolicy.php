<?php

declare(strict_types=1);

namespace App\Policies;

/**
 * @CODE:RBAC-001 | SPEC: SPEC-RBAC-001.md
 *
 * Store Policy - tenant_users 기반 권한 체계
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

use App\Models\Store;
use App\Models\User;

class StorePolicy
{
    /**
     * Store 목록 조회 권한 확인
     *
     * Brand 또는 Organization에 대한 접근 권한이 있으면 Store 목록 조회 가능
     */
    public function viewAny(User $user): bool
    {
        // Filament 컨텍스트에서 현재 테넌트 확인
        $tenant = \Filament\Facades\Filament::getTenant();

        // Brand 패널인 경우
        if ($tenant instanceof \App\Models\Brand) {
            if (! $tenant->organization instanceof \App\Models\Organization) {
                return false;
            }

            return $user->canViewTenant($tenant->organization);
        }

        // Organization 패널인 경우 (직접 Store 관리)
        if ($tenant instanceof \App\Models\Organization) {
            return $user->canViewTenant($tenant);
        }

        return false;
    }

    /**
     * 특정 Store 조회 권한 확인
     *
     * Store의 소유 Organization에 대한 조회 권한이 있으면 Store 조회 가능
     */
    public function view(User $user, Store $store): bool
    {
        $ownerOrg = $store->getOwnerOrganization();

        if (! $ownerOrg instanceof \App\Models\Organization) {
            return false;
        }

        return $user->canViewTenant($ownerOrg);
    }

    /**
     * Store 생성 권한 확인
     *
     * Brand 또는 Organization에 대한 관리 권한이 있으면 Store 생성 가능
     */
    public function create(User $user): bool
    {
        $tenant = \Filament\Facades\Filament::getTenant();

        // Brand 패널인 경우
        if ($tenant instanceof \App\Models\Brand) {
            if (! $tenant->organization instanceof \App\Models\Organization) {
                return false;
            }

            return $user->canManageTenant($tenant->organization);
        }

        // Organization 패널인 경우
        if ($tenant instanceof \App\Models\Organization) {
            return $user->canManageTenant($tenant);
        }

        return false;
    }

    /**
     * Store 수정 권한 확인
     *
     * Store의 소유 Organization에 대한 관리 권한이 있으면 Store 수정 가능
     */
    public function update(User $user, Store $store): bool
    {
        $ownerOrg = $store->getOwnerOrganization();

        if (! $ownerOrg instanceof \App\Models\Organization) {
            return false;
        }

        return $user->canManageTenant($ownerOrg);
    }

    /**
     * Store 삭제 권한 확인
     *
     * - owner 역할만 삭제 가능
     * - tenant 관계: 삭제 불가
     */
    public function delete(User $user, Store $store): bool
    {
        $ownerOrg = $store->getOwnerOrganization();

        if (! $ownerOrg instanceof \App\Models\Organization) {
            return false;
        }

        // owner 역할만 삭제 가능
        if (! $user->hasRoleForTenant($ownerOrg, 'owner')) {
            return false;
        }

        // tenant 관계는 삭제 불가
        return (bool) $store->relationship_type->isDeletable();
    }

    /**
     * Store 복원 권한 확인
     *
     * Store의 소유 Organization에 대한 관리 권한이 있으면 Store 복원 가능
     */
    public function restore(User $user, Store $store): bool
    {
        $ownerOrg = $store->getOwnerOrganization();

        if (! $ownerOrg instanceof \App\Models\Organization) {
            return false;
        }

        return $user->canManageTenant($ownerOrg);
    }

    /**
     * Store 영구 삭제 권한 확인
     *
     * owner 역할만 영구 삭제 가능
     */
    public function forceDelete(User $user, Store $store): bool
    {
        $ownerOrg = $store->getOwnerOrganization();

        if (! $ownerOrg instanceof \App\Models\Organization) {
            return false;
        }

        return $user->hasRoleForTenant($ownerOrg, 'owner');
    }
}

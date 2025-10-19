<?php

declare(strict_types=1);

namespace App\Policies;

/**
 * @CODE:BRAND-STORE-MGMT-001 | SPEC: SPEC-BRAND-STORE-MGMT-001.md
 *
 * Store Policy - 3-Layer 권한 체계
 *
 * 1. Spatie Permission: 세밀한 권한 (view-stores, delete-stores)
 * 2. Filament Tenant: 리소스 소유권
 * 3. RelationshipType: tenant 관계 삭제 차단
 */

use App\Models\Store;
use App\Models\User;

class StorePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view-stores');
    }

    public function view(User $user, Store $store): bool
    {
        if (! $user->can('view-stores')) {
            return false;
        }

        // Brand를 통해 소속된 경우
        if ($store->brand_id && $store->brand && $store->brand->organization) {
            return $user->canAccessTenant($store->brand->organization);
        }

        // 직접 Organization에 소속된 경우
        if ($store->organization) {
            return $user->canAccessTenant($store->organization);
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->can('create-stores');
    }

    public function update(User $user, Store $store): bool
    {
        if (! $user->can('update-stores')) {
            return false;
        }

        $ownerOrg = $store->getOwnerOrganization();

        return $ownerOrg instanceof \App\Models\Organization && $user->canAccessTenant($ownerOrg);
    }

    /**
     * Store 삭제 권한 확인
     *
     * - tenant 관계: 삭제 불가
     * - 그 외: 권한 + 소유권 확인
     */
    public function delete(User $user, Store $store): bool
    {
        if (! $user->can('delete-stores')) {
            return false;
        }

        // tenant 관계는 삭제 불가
        if (! $store->relationship_type->isDeletable()) {
            return false;
        }

        $ownerOrg = $store->getOwnerOrganization();

        return $ownerOrg instanceof \App\Models\Organization && $user->canAccessTenant($ownerOrg);
    }

    public function restore(User $user, Store $store): bool
    {
        if (! $user->can('restore-stores')) {
            return false;
        }

        $ownerOrg = $store->getOwnerOrganization();

        return $ownerOrg instanceof \App\Models\Organization && $user->canAccessTenant($ownerOrg);
    }

    public function forceDelete(User $user, Store $store): bool
    {
        if (! $user->can('force-delete-stores')) {
            return false;
        }

        $ownerOrg = $store->getOwnerOrganization();

        return $ownerOrg instanceof \App\Models\Organization && $user->canAccessTenant($ownerOrg);
    }
}

<?php

declare(strict_types=1);

namespace App\Policies;

/**
 * @CODE:BRAND-STORE-MGMT-001 | SPEC: SPEC-BRAND-STORE-MGMT-001.md
 *
 * Brand Policy - 3-Layer 권한 체계
 *
 * 1. Spatie Permission: 세밀한 권한 (view-brands, delete-brands)
 * 2. Filament Tenant: 리소스 소유권
 * 3. RelationshipType: tenant 관계 삭제 차단
 */

use App\Models\Brand;
use App\Models\User;

class BrandPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view-brands');
    }

    public function view(User $user, Brand $brand): bool
    {
        return $user->can('view-brands') && ($brand->organization && $user->canAccessTenant($brand->organization));
    }

    public function create(User $user): bool
    {
        return $user->can('create-brands');
    }

    public function update(User $user, Brand $brand): bool
    {
        return $user->can('update-brands') && ($brand->organization && $user->canAccessTenant($brand->organization));
    }

    /**
     * Brand 삭제 권한 확인
     *
     * - tenant 관계: 삭제 불가
     * - 활성 Store 보유: 삭제 불가
     * - 그 외: 권한 + 소유권 확인
     */
    public function delete(User $user, Brand $brand): bool
    {
        if (! $user->can('delete-brands')) {
            return false;
        }

        // tenant 관계는 삭제 불가
        if (! $brand->relationship_type->isDeletable()) {
            return false;
        }

        // 활성 Store가 있으면 삭제 불가
        if ($brand->hasActiveStores()) {
            return false;
        }

        return $brand->organization && $user->canAccessTenant($brand->organization);
    }

    public function restore(User $user, Brand $brand): bool
    {
        return $user->can('restore-brands') && ($brand->organization && $user->canAccessTenant($brand->organization));
    }

    public function forceDelete(User $user, Brand $brand): bool
    {
        return $user->can('force-delete-brands') && ($brand->organization && $user->canAccessTenant($brand->organization));
    }
}

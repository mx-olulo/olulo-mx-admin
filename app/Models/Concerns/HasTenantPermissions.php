<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * @CODE:RBAC-001 | SPEC: SPEC-RBAC-001.md
 *
 * 사용자 테넌트 권한 확인 Trait
 *
 * TDD History:
 * - REFACTOR (2025-10-22): User 모델 복잡도 감소를 위해 권한 확인 로직 분리
 *
 * 책임:
 * - 테넌트별 역할 확인
 * - 테넌트 관리 권한 확인
 * - 테넌트 조회 권한 확인
 * - 글로벌 역할 확인
 */
trait HasTenantPermissions
{
    /**
     * @CODE:RBAC-001 | SPEC: SPEC-RBAC-001.md
     *
     * 특정 테넌트에서 특정 역할 보유 여부 확인
     *
     * @param  Model  $model  Organization, Brand, Store
     * @param  string  $role  'owner', 'manager', 'viewer'
     */
    public function hasRoleForTenant(Model $model, string $role): bool
    {
        return $this->getRoleForTenant($model) === $role;
    }

    /**
     * @CODE:RBAC-001 | SPEC: SPEC-RBAC-001.md
     *
     * 테넌트 관리 권한 확인 (owner 또는 manager)
     *
     * @param  Model  $model  Organization, Brand, Store
     */
    public function canManageTenant(Model $model): bool
    {
        $role = $this->getRoleForTenant($model);

        return in_array($role, ['owner', 'manager'], true);
    }

    /**
     * @CODE:RBAC-001 | SPEC: SPEC-RBAC-001.md
     *
     * 테넌트 조회 권한 확인 (모든 역할)
     *
     * @param  Model  $model  Organization, Brand, Store
     */
    public function canViewTenant(Model $model): bool
    {
        return $this->getRoleForTenant($model) !== null;
    }

    /**
     * @CODE:RBAC-001 | SPEC: SPEC-RBAC-001.md
     *
     * 글로벌 역할 확인 (User 타입만)
     *
     * @param  string  $role  'platform_admin', 'system_admin'
     */
    public function hasGlobalRole(string $role): bool
    {
        // User 타입만 글로벌 역할을 가질 수 있음
        if ($this->user_type !== \App\Enums\UserType::USER) {
            return false;
        }

        return $this->global_role === $role;
    }
}

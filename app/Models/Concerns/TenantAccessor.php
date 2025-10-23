<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Enums\TenantRole;
use Illuminate\Database\Eloquent\Model;

/**
 * @CODE:RBAC-001 | SPEC: SPEC-RBAC-001.md
 *
 * 테넌트 접근 체이닝을 위한 헬퍼 클래스
 *
 * TDD History:
 * - ADDED (2025-10-23): 메서드 체이닝 지원을 위한 Fluent API 추가
 *
 * 사용 예시:
 * ```php
 * $user->tenant($organization)->canManage();
 * $user->tenant($brand)->hasRole(TenantRole::OWNER);
 * $user->tenant($store)->canView();
 * ```
 */
class TenantAccessor
{
    public function __construct(
        private readonly mixed $user,
        private readonly Model $model
    ) {}

    /**
     * 테넌트에서의 역할 조회
     */
    public function role(): ?TenantRole
    {
        $roleString = $this->user->getRoleForTenant($this->model);

        if ($roleString === null) {
            return null;
        }

        return TenantRole::tryFrom($roleString);
    }

    /**
     * 특정 역할 보유 여부 확인
     */
    public function hasRole(TenantRole $tenantRole): bool
    {
        return $this->role() === $tenantRole;
    }

    /**
     * 관리 권한 확인 (owner 또는 manager)
     */
    public function canManage(): bool
    {
        $role = $this->role();

        return $role instanceof \App\Enums\TenantRole && $role->canManage();
    }

    /**
     * 조회 권한 확인 (모든 역할)
     */
    public function canView(): bool
    {
        return $this->role() instanceof \App\Enums\TenantRole;
    }

    /**
     * Owner 역할 여부
     */
    public function isOwner(): bool
    {
        return $this->hasRole(TenantRole::OWNER);
    }

    /**
     * Manager 역할 여부
     */
    public function isManager(): bool
    {
        return $this->hasRole(TenantRole::MANAGER);
    }

    /**
     * Viewer 역할 여부
     */
    public function isViewer(): bool
    {
        return $this->hasRole(TenantRole::VIEWER);
    }
}

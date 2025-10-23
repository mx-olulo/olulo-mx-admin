<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\TenantUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @CODE:RBAC-001 | SPEC: SPEC-RBAC-001.md
 *
 * 사용자 테넌트 관계 관리 Trait
 *
 * TDD History:
 * - REFACTOR (2025-10-22): User 모델 복잡도 감소를 위해 테넌트 관계 로직 분리
 *
 * 책임:
 * - TenantUser 관계 정의
 * - 테넌트 타입별 조회
 * - 테넌트별 역할 조회
 */
trait HasTenantRelations
{
    /**
     * @CODE:RBAC-001 | SPEC: SPEC-RBAC-001.md | TEST: tests/Feature/Tenancy/UserTenantRelationTest.php
     *
     * TenantUser 관계 (HasMany)
     *
     * @return HasMany<TenantUser, $this>
     */
    public function tenantUsers(): HasMany
    {
        return $this->hasMany(TenantUser::class);
    }

    /**
     * @CODE:RBAC-001 | SPEC: SPEC-RBAC-001.md
     *
     * 특정 타입의 테넌트 목록 조회
     *
     * @param  string  $tenantType  'ORG', 'BRD', 'STR'
     * @return Collection<int, Model>
     */
    public function getTenantsByType(string $tenantType): Collection
    {
        return $this->tenantUsers()
            ->where('tenant_type', $tenantType)
            ->with('tenant')
            ->get()
            ->pluck('tenant')
            ->filter() // null 제거
            ->values(); // 키 리인덱싱
    }

    /**
     * @CODE:RBAC-001 | SPEC: SPEC-RBAC-001.md
     *
     * 특정 테넌트에서의 역할 조회
     *
     * @param  Model  $model  Organization, Brand, Store
     * @return string|null 'owner', 'manager', 'viewer' 또는 null
     */
    public function getRoleForTenant(Model $model): ?string
    {
        $tenantType = array_search($model::class, \App\Enums\ScopeType::getMorphMap(), true);

        if ($tenantType === false) {
            return null;
        }

        $tenantUser = $this->tenantUsers()
            ->where('tenant_type', $tenantType)
            ->where('tenant_id', $model->getKey())
            ->first();

        return $tenantUser?->role;
    }
}

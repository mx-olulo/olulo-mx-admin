<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ScopeType;
use App\Models\Organization;
use App\Models\Store;
use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * @CODE:RBAC-001 | SPEC: SPEC-RBAC-001.md | TEST: tests/Feature/OnboardingServiceTest.php
 *
 * 온보딩 서비스: 신규 사용자의 조직/매장 생성 및 Owner 역할 부여
 * Spatie Permissions 제거 후 TenantUser 모델 직접 사용
 */
class OnboardingService
{
    /**
     * 조직 생성 및 Owner Role 부여
     *
     * @param  array<string, mixed>  $data
     */
    public function createOrganization(User $user, array $data): Organization
    {
        return DB::transaction(function () use ($user, $data): Organization {
            $organization = Organization::create([
                'name' => $data['name'],
            ]);

            // TenantUser 레코드 직접 생성 (Spatie Role 대신)
            TenantUser::create([
                'user_id' => $user->id,
                'tenant_type' => ScopeType::ORGANIZATION->value,
                'tenant_id' => $organization->id,
                'role' => 'owner',
            ]);

            return $organization;
        });
    }

    /**
     * 매장 생성 및 Owner Role 부여
     *
     * @param  array<string, mixed>  $data
     */
    public function createStore(User $user, array $data): Store
    {
        return DB::transaction(function () use ($user, $data): Store {
            $store = Store::create([
                'name' => $data['name'],
                'organization_id' => null, // 독립 매장
                'status' => 'pending',
            ]);

            // TenantUser 레코드 직접 생성 (Spatie Role 대신)
            TenantUser::create([
                'user_id' => $user->id,
                'tenant_type' => ScopeType::STORE->value,
                'tenant_id' => $store->id,
                'role' => 'owner',
            ]);

            return $store;
        });
    }
}

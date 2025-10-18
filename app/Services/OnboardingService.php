<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ScopeType;
use App\Models\Organization;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

/**
 * @CODE:ONBOARD-001 | SPEC: .moai/specs/SPEC-ONBOARD-001/spec.md | TEST: tests/Feature/Feature/OnboardingServiceTest.php
 *
 * 온보딩 서비스: 신규 사용자의 조직/매장 생성 및 Owner 역할 부여
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

            $ownerRole = Role::firstOrCreate([
                'name' => 'owner',
                'scope_type' => ScopeType::ORGANIZATION->value,
                'scope_ref_id' => $organization->id,
                'guard_name' => 'web',
                'team_id' => $organization->id,
            ]);

            // Set team context before assigning role
            setPermissionsTeamId($organization->id);
            $user->assignRole($ownerRole);

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

            $ownerRole = Role::firstOrCreate([
                'name' => 'owner',
                'scope_type' => ScopeType::STORE->value,
                'scope_ref_id' => $store->id,
                'guard_name' => 'web',
                'team_id' => $store->id,
            ]);

            // Set team context before assigning role
            setPermissionsTeamId($store->id);
            $user->assignRole($ownerRole);

            return $store;
        });
    }
}

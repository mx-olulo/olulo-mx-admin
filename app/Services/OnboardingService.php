<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ScopeType;
use App\Models\Organization;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

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
            ]);

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
            ]);

            $user->assignRole($ownerRole);

            return $store;
        });
    }
}

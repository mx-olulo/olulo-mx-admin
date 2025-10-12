<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Organization;
use App\Models\Platform;
use App\Models\Role;
use App\Models\Store;
use App\Models\System;
use App\Models\Team;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // 1. Platform 엔터티 생성
        $platform = Platform::firstOrCreate(
            ['name' => 'Olulo Platform'],
            ['description' => 'Olulo 플랫폼 운영사', 'is_active' => true]
        );

        // 2. System 엔터티 생성
        $system = System::firstOrCreate(
            ['name' => 'Olulo System'],
            ['description' => 'Olulo 시스템 관리', 'is_active' => true]
        );

        // 3. Platform Admin 역할
        $platformAdminRole = Role::firstOrCreate(
            ['name' => 'platform_admin', 'guard_name' => 'web', 'team_id' => $platform->id],
            ['scope_type' => \App\Enums\ScopeType::PLATFORM->value, 'scope_ref_id' => $platform->id]
        );

        // 4. System Admin 역할
        $systemAdminRole = Role::firstOrCreate(
            ['name' => 'system_admin', 'guard_name' => 'web', 'team_id' => $system->id + 1000],
            ['scope_type' => \App\Enums\ScopeType::SYSTEM->value, 'scope_ref_id' => $system->id]
        );

        // 5. Platform/System 팀 생성
        $platformTeam = Team::firstOrCreate(
            [
                'tenant_type' => Platform::class,
                'tenant_id' => $platform->id,
                'scope_type' => \App\Enums\ScopeType::PLATFORM->value,
            ],
            ['name' => $platform->name]
        );

        $systemTeam = Team::firstOrCreate(
            [
                'tenant_type' => System::class,
                'tenant_id' => $system->id,
                'scope_type' => \App\Enums\ScopeType::SYSTEM->value,
            ],
            ['name' => $system->name]
        );

        // 6. 샘플 Organization, Brand, Store 생성 (local 환경만)
        if (app()->environment('local')) {
            $this->createSampleEntities();
        }

        // 7. 테스트 사용자 생성 (local 환경만)
        if (app()->environment('local')) {
            $this->createTestUsers($platformAdminRole, $systemAdminRole, $platformTeam, $systemTeam);
        }
    }

    /**
     * 샘플 엔터티 생성
     */
    private function createSampleEntities(): void
    {
        // Organization 1
        $org1 = Organization::firstOrCreate(
            ['name' => 'Sample Organization 1'],
            [
                'description' => '샘플 조직 1',
                'contact_email' => 'org1@example.com',
                'is_active' => true,
            ]
        );

        // Organization 1 팀 생성
        $org1Team = Team::firstOrCreate(
            [
                'tenant_type' => Organization::class,
                'tenant_id' => $org1->id,
                'scope_type' => \App\Enums\ScopeType::ORGANIZATION->value,
            ],
            ['name' => $org1->name]
        );

        // Brand 1-1 (Organization 1 소속)
        $brand11 = Brand::firstOrCreate(
            ['organization_id' => $org1->id, 'name' => 'Sample Brand 1-1'],
            ['description' => '샘플 브랜드 1-1', 'is_active' => true]
        );

        // Brand 1-1 팀 생성
        $brand11Team = Team::firstOrCreate(
            [
                'tenant_type' => Brand::class,
                'tenant_id' => $brand11->id,
                'scope_type' => \App\Enums\ScopeType::BRAND->value,
            ],
            ['name' => $brand11->name]
        );

        // Store 1-1-1 (Brand 1-1 소속)
        $store111 = Store::firstOrCreate(
            ['brand_id' => $brand11->id, 'name' => 'Sample Store 1-1-1'],
            [
                'description' => '샘플 매장 1-1-1',
                'address' => '서울시 강남구',
                'phone' => '02-1234-5678',
                'is_active' => true,
            ]
        );

        // Store 1-1-1 팀 생성
        $store111Team = Team::firstOrCreate(
            [
                'tenant_type' => Store::class,
                'tenant_id' => $store111->id,
                'scope_type' => \App\Enums\ScopeType::STORE->value,
            ],
            ['name' => $store111->name]
        );

        // Store 1-2 (Organization 1 직접 소속, Brand 없음)
        $store12 = Store::firstOrCreate(
            ['organization_id' => $org1->id, 'name' => 'Sample Store 1-2'],
            [
                'description' => '샘플 매장 1-2 (조직 직속)',
                'address' => '서울시 서초구',
                'phone' => '02-2345-6789',
                'is_active' => true,
            ]
        );

        // Store 1-2 팀 생성
        $store12Team = Team::firstOrCreate(
            [
                'tenant_type' => Store::class,
                'tenant_id' => $store12->id,
                'scope_type' => \App\Enums\ScopeType::STORE->value,
            ],
            ['name' => $store12->name]
        );

        // Store 독립 (소속 없음)
        $independentStore = Store::firstOrCreate(
            ['name' => 'Independent Store'],
            [
                'description' => '독립 매장',
                'address' => '서울시 마포구',
                'phone' => '02-3456-7890',
                'is_active' => true,
            ]
        );

        // Independent Store 팀 생성
        $independentStoreTeam = Team::firstOrCreate(
            [
                'tenant_type' => Store::class,
                'tenant_id' => $independentStore->id,
                'scope_type' => \App\Enums\ScopeType::STORE->value,
            ],
            ['name' => $independentStore->name]
        );

        $this->command->info('✓ Sample entities and teams created');
    }

    /**
     * 테스트 사용자 생성
     */
    private function createTestUsers(Role $platformAdminRole, Role $systemAdminRole, Team $platformTeam, Team $systemTeam): void
    {
        // Platform Admin
        $platformAdmin = User::firstOrCreate(
            ['email' => 'platform@example.com'],
            [
                'name' => 'Platform Admin',
                'password' => bcrypt('password'),
            ]
        );
        setPermissionsTeamId($platformAdminRole->team_id);
        $platformAdmin->assignRole($platformAdminRole);

        // Platform Admin membership
        TenantMembership::firstOrCreate(
            [
                'user_id' => $platformAdmin->id,
                'tenant_type' => Platform::class,
                'tenant_id' => 1,
            ],
            [
                'team_id' => $platformTeam->id,
                'scope_type' => \App\Enums\ScopeType::PLATFORM->value,
                'is_owner' => true,
                'status' => 'active',
            ]
        );

        // System Admin
        $systemAdmin = User::firstOrCreate(
            ['email' => 'system@example.com'],
            [
                'name' => 'System Admin',
                'password' => bcrypt('password'),
            ]
        );
        setPermissionsTeamId($systemAdminRole->team_id);
        $systemAdmin->assignRole($systemAdminRole);

        // System Admin membership
        TenantMembership::firstOrCreate(
            [
                'user_id' => $systemAdmin->id,
                'tenant_type' => System::class,
                'tenant_id' => 1,
            ],
            [
                'team_id' => $systemTeam->id,
                'scope_type' => \App\Enums\ScopeType::SYSTEM->value,
                'is_owner' => true,
                'status' => 'active',
            ]
        );

        // Organization 테스트 사용자 생성
        $org1 = Organization::where('name', 'Sample Organization 1')->first();
        if ($org1) {
            $orgUser = User::firstOrCreate(
                ['email' => 'org@example.com'],
                [
                    'name' => 'Organization Manager',
                    'password' => bcrypt('password'),
                ]
            );

            $orgTeam = Team::where('tenant_type', Organization::class)
                ->where('tenant_id', $org1->id)
                ->first();

            if ($orgTeam) {
                TenantMembership::firstOrCreate(
                    [
                        'user_id' => $orgUser->id,
                        'tenant_type' => Organization::class,
                        'tenant_id' => $org1->id,
                    ],
                    [
                        'team_id' => $orgTeam->id,
                        'scope_type' => \App\Enums\ScopeType::ORGANIZATION->value,
                        'is_owner' => true,
                        'status' => 'active',
                    ]
                );

                // Organization 스코프 역할 생성 및 부여
                setPermissionsTeamId($orgTeam->id);
                $orgManagerRole = Role::firstOrCreate(
                    [
                        'name' => 'organization_manager',
                        'guard_name' => 'web',
                        'team_id' => $orgTeam->id,
                    ],
                    [
                        'scope_type' => \App\Enums\ScopeType::ORGANIZATION->value,
                        'scope_ref_id' => $org1->id,
                    ]
                );
                $orgUser->assignRole($orgManagerRole);
            }
        }

        $this->command->info('✓ Admin users and memberships created');
        $this->command->info('  - Platform Admin: platform@example.com / password');
        $this->command->info('  - System Admin: system@example.com / password');
        $this->command->info('  - Organization Manager: org@example.com / password');
    }
}

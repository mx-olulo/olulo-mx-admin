<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Organization;
use App\Models\Platform;
use App\Models\Role;
use App\Models\Store;
use App\Models\System;
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

        // 5. 샘플 Organization, Brand, Store 생성 (local 환경만)
        if (app()->environment('local')) {
            $this->createSampleEntities();
        }

        // 6. 테스트 사용자 생성 (local 환경만)
        if (app()->environment('local')) {
            $this->createTestUsers($platformAdminRole, $systemAdminRole);
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

        // Brand 1-1 (Organization 1 소속)
        $brand11 = Brand::firstOrCreate(
            ['organization_id' => $org1->id, 'name' => 'Sample Brand 1-1'],
            ['description' => '샘플 브랜드 1-1', 'is_active' => true]
        );

        // Store 1-1-1 (Brand 1-1 소속)
        Store::firstOrCreate(
            ['brand_id' => $brand11->id, 'name' => 'Sample Store 1-1-1'],
            [
                'description' => '샘플 매장 1-1-1',
                'address' => '서울시 강남구',
                'phone' => '02-1234-5678',
                'is_active' => true,
            ]
        );

        // Store 1-2 (Organization 1 직접 소속, Brand 없음)
        Store::firstOrCreate(
            ['organization_id' => $org1->id, 'name' => 'Sample Store 1-2'],
            [
                'description' => '샘플 매장 1-2 (조직 직속)',
                'address' => '서울시 서초구',
                'phone' => '02-2345-6789',
                'is_active' => true,
            ]
        );

        // Store 독립 (소속 없음)
        Store::firstOrCreate(
            ['name' => 'Independent Store'],
            [
                'description' => '독립 매장',
                'address' => '서울시 마포구',
                'phone' => '02-3456-7890',
                'is_active' => true,
            ]
        );

        $this->command->info('✓ Sample entities created');
    }

    /**
     * 테스트 사용자 생성
     */
    private function createTestUsers(Role $platformAdminRole, Role $systemAdminRole): void
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

        $this->command->info('✓ Admin users created');
        $this->command->info('  - Platform Admin: platform@example.com / password');
        $this->command->info('  - System Admin: system@example.com / password');
    }
}

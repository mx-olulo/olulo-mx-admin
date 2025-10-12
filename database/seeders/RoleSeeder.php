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
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Spatie Permission 캐시 초기화 (권장)
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // 1. Platform 생성
        $platform = Platform::firstOrCreate(
            ['name' => 'Olulo Platform'],
            ['description' => 'Main Platform']
        );

        // 2. System 생성
        $system = System::firstOrCreate(
            ['name' => 'Olulo System'],
            ['description' => 'System Management']
        );

        // 3. Platform Admin 역할
        $platformAdminRole = Role::firstOrCreate(
            ['name' => 'platform_admin', 'guard_name' => 'web', 'team_id' => null],
            ['scope_type' => \App\Enums\ScopeType::PLATFORM->value, 'scope_ref_id' => $platform->id]
        );

        // 4. System Admin 역할
        $systemAdminRole = Role::firstOrCreate(
            ['name' => 'system_admin', 'guard_name' => 'web', 'team_id' => null],
            ['scope_type' => \App\Enums\ScopeType::SYSTEM->value, 'scope_ref_id' => $system->id]
        );

        // 5. 샘플 데이터 (local 환경만)
        if (app()->environment('local')) {
            $this->createSampleData($platformAdminRole, $systemAdminRole);
        }

        // 롤/권한 변경 후 캐시 재무효화
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function createSampleData(Role $platformAdminRole, Role $systemAdminRole): void
    {
        // Organization 생성
        $org1 = Organization::firstOrCreate(
            ['name' => 'Sample Organization 1'],
            [
                'description' => '샘플 조직 1',
                'contact_email' => 'org1@example.com',
                'is_active' => true,
            ]
        );

        // Brand 생성
        $brand11 = Brand::firstOrCreate(
            ['organization_id' => $org1->id, 'name' => 'Sample Brand 1-1'],
            ['description' => '샘플 브랜드 1-1', 'is_active' => true]
        );

        // Store 생성
        $store111 = Store::firstOrCreate(
            ['brand_id' => $brand11->id, 'name' => 'Sample Store 1-1-1'],
            [
                'description' => '샘플 매장 1-1-1',
                'address' => '서울시 강남구',
                'phone' => '02-1234-5678',
                'is_active' => true,
            ]
        );

        // Organization 역할 생성
        $orgManagerRole = Role::firstOrCreate(
            [
                'name' => 'organization_manager',
                'guard_name' => 'web',
                'team_id' => $org1->id,
            ],
            [
                'scope_type' => \App\Enums\ScopeType::ORGANIZATION->value,
                'scope_ref_id' => $org1->id,
            ]
        );

        // 테스트 사용자 생성
        $this->createTestUsers($platformAdminRole, $systemAdminRole, $orgManagerRole);

        $this->command->info('✓ Sample data created');
    }

    private function createTestUsers(Role $platformAdminRole, Role $systemAdminRole, Role $orgManagerRole): void
    {
        // Platform Admin
        $platformAdmin = User::firstOrCreate(
            ['email' => 'platform@example.com'],
            ['name' => 'Platform Admin', 'password' => bcrypt('password')]
        );
        // 글로벌 역할은 팀 컨텍스트 없음 (TeamResolver가 null 반환)
        setPermissionsTeamId(null);
        $platformAdmin->assignRole($platformAdminRole);

        // System Admin
        $systemAdmin = User::firstOrCreate(
            ['email' => 'system@example.com'],
            ['name' => 'System Admin', 'password' => bcrypt('password')]
        );
        // 글로벌 역할은 팀 컨텍스트 없음
        setPermissionsTeamId(null);
        $systemAdmin->assignRole($systemAdminRole);

        // Organization Manager
        $orgUser = User::firstOrCreate(
            ['email' => 'org@example.com'],
            ['name' => 'Organization Manager', 'password' => bcrypt('password')]
        );
        // 테넌트 역할 컨텍스트 설정 (TeamResolver와 동일 스키마: team_id = tenant id)
        setPermissionsTeamId($orgManagerRole->team_id);
        $orgUser->assignRole($orgManagerRole);

        $this->command->info('✓ Test users created');
        $this->command->info('  - platform@example.com / password');
        $this->command->info('  - system@example.com / password');
        $this->command->info('  - org@example.com / password');
    }
}

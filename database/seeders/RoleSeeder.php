<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Platform Admin 역할 (플랫폼 운영사)
        $platformAdminRole = Role::firstOrCreate(
            ['name' => 'platform_admin', 'guard_name' => 'web', 'team_id' => 1],
            ['scope_type' => Role::TYPE_PLATFORM, 'scope_ref_id' => 1]
        );

        // System Admin 역할 (시스템 관리자)
        $systemAdminRole = Role::firstOrCreate(
            ['name' => 'system_admin', 'guard_name' => 'web', 'team_id' => 2],
            ['scope_type' => Role::TYPE_SYSTEM, 'scope_ref_id' => 1]
        );

        // 테스트 사용자 생성
        $this->createTestUsers($platformAdminRole, $systemAdminRole);
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

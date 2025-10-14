<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalPanelAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_access_platform_panel(): void
    {
        // Create platform admin role (global scope, team_id = 0)
        $platformRole = Role::create([
            'name' => 'platform_admin',
            'guard_name' => 'web',
            // 글로벌 역할은 team_id = 0 (Seeder와 일치)
            'team_id' => 0,
            'scope_type' => 'PLATFORM',
            'scope_ref_id' => 1,
        ]);

        // Create user
        $user = User::create([
            'name' => 'Platform Admin',
            'email' => 'platform@example.com',
            'password' => bcrypt('password'),
        ]);

        // Assign role with explicit team context
        setPermissionsTeamId(0);
        $user->assignRole($platformRole);

        // Check panel access
        $panel = \Filament\Facades\Filament::getPanel('platform');
        $this->assertTrue($user->canAccessPanel($panel));
    }

    public function test_system_admin_can_access_system_panel(): void
    {
        // Create system admin role (global scope, team_id = 0)
        $systemRole = Role::create([
            'name' => 'system_admin',
            'guard_name' => 'web',
            // 글로벌 역할은 team_id = 0 (Seeder와 일치)
            'team_id' => 0,
            'scope_type' => 'SYSTEM',
            'scope_ref_id' => 1,
        ]);

        // Create user
        $user = User::create([
            'name' => 'System Admin',
            'email' => 'system@example.com',
            'password' => bcrypt('password'),
        ]);

        // Assign role with explicit team context
        setPermissionsTeamId(0);
        $user->assignRole($systemRole);

        // Check panel access
        $panel = \Filament\Facades\Filament::getPanel('system');
        $this->assertTrue($user->canAccessPanel($panel));
    }

    public function test_user_without_platform_role_cannot_access_platform_panel(): void
    {
        $user = User::create([
            'name' => 'Regular User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        $panel = \Filament\Facades\Filament::getPanel('platform');
        $this->assertFalse($user->canAccessPanel($panel));
    }

    public function test_user_without_system_role_cannot_access_system_panel(): void
    {
        $user = User::create([
            'name' => 'Regular User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        $panel = \Filament\Facades\Filament::getPanel('system');
        $this->assertFalse($user->canAccessPanel($panel));
    }

    public function test_platform_and_system_do_not_require_tenancy(): void
    {
        $platformPanel = \Filament\Facades\Filament::getPanel('platform');
        $systemPanel = \Filament\Facades\Filament::getPanel('system');

        // Platform and System panels should not have tenant configuration
        $this->assertFalse($platformPanel->hasTenancy());
        $this->assertFalse($systemPanel->hasTenancy());
    }
}

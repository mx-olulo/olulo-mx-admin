<?php

declare(strict_types=1);

use App\Enums\ScopeType;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

/**
 * Organization Permission 테스트
 *
 * 테스트 전략:
 * - 각 테스트마다 필요한 데이터를 직접 생성하여 독립성 확보
 * - RefreshDatabase 트랜잭션 문제 해결
 * - PLATFORM/SYSTEM 스코프: Role의 scope_type 확인
 * - ORGANIZATION 스코프: Spatie Permission 직접 확인
 */
describe('Organization Permission', function (): void {
    /**
     * 테스트용 Platform/System 사용자 및 Role 생성 헬퍼
     */
    function createPlatformUser(): User
    {
        $role = Role::create([
            'name' => 'platform-admin-' . uniqid(),
            'guard_name' => 'web',
            'team_id' => null,
            'scope_type' => ScopeType::PLATFORM->value,
            'scope_ref_id' => 1,
        ]);

        $user = User::factory()->create(['name' => 'Platform Admin']);

        // 글로벌 역할은 팀 컨텍스트 없음
        setPermissionsTeamId(null);
        $user->assignRole($role);

        return $user;
    }

    function createSystemUser(): User
    {
        $role = Role::create([
            'name' => 'system-admin-' . uniqid(),
            'guard_name' => 'web',
            'team_id' => null,
            'scope_type' => ScopeType::SYSTEM->value,
            'scope_ref_id' => 1,
        ]);

        $user = User::factory()->create(['name' => 'System Admin']);

        // 글로벌 역할은 팀 컨텍스트 없음
        setPermissionsTeamId(null);
        $user->assignRole($role);

        return $user;
    }

    function createOrganizationUser(array $permissions = []): array
    {
        $organization = Organization::create([
            'name' => 'Test Organization ' . uniqid(),
            'description' => 'Test organization',
            'contact_email' => 'org@example.com',
            'is_active' => true,
        ]);

        $role = Role::create([
            'name' => 'org-admin-' . uniqid(),
            'guard_name' => 'web',
            'team_id' => random_int(1000, 9999),
            'scope_type' => ScopeType::ORGANIZATION->value,
            'scope_ref_id' => $organization->id,
        ]);

        $user = User::factory()->create(['name' => 'Org Admin']);

        setPermissionsTeamId($role->team_id);
        $user->assignRole($role);

        if ($permissions !== []) {
            foreach ($permissions as $permissionName) {
                $permission = Permission::firstOrCreate([
                    'name' => $permissionName,
                    'guard_name' => 'web',
                ]);
            }
            $role->syncPermissions($permissions);
        }

        setPermissionsTeamId(null);

        return ['user' => $user, 'organization' => $organization, 'role' => $role];
    }

    function ensurePermissionExists(string $permissionName): void
    {
        Permission::firstOrCreate([
            'name' => $permissionName,
            'guard_name' => 'web',
        ]);
    }

    describe('PLATFORM Scope', function (): void {
        test('PLATFORM 사용자는 PLATFORM scope_type Role을 가짐', function (): void {
            $user = createPlatformUser();

            // DB에서 직접 조회하여 Role 할당 확인
            // model_has_roles 테이블에서 user_id와 role의 scope_type 확인
            $platformRoleCount = Role::where('scope_type', ScopeType::PLATFORM->value)
                ->whereHas('users', function ($query) use ($user): void {
                    $query->where('model_id', $user->id);
                })
                ->count();

            expect($platformRoleCount)->toBeGreaterThan(0);
        })->group('organization-permission', 'security', 'multitenancy');
    });

    describe('SYSTEM Scope', function (): void {
        test('SYSTEM 사용자는 SYSTEM scope_type Role을 가짐', function (): void {
            $user = createSystemUser();

            // DB에서 직접 조회하여 Role 할당 확인
            // model_has_roles 테이블에서 user_id와 role의 scope_type 확인
            $systemRoleCount = Role::where('scope_type', ScopeType::SYSTEM->value)
                ->whereHas('users', function ($query) use ($user): void {
                    $query->where('model_id', $user->id);
                })
                ->count();

            expect($systemRoleCount)->toBeGreaterThan(0);
        })->group('organization-permission', 'security', 'multitenancy');
    });

    describe('ORGANIZATION Scope - view-activities', function (): void {
        test('ORGANIZATION 사용자는 부여된 view-activities 권한을 가짐', function (): void {
            ensurePermissionExists('view-activities');

            ['user' => $user, 'role' => $role] = createOrganizationUser(['view-activities']);

            setPermissionsTeamId($role->team_id);
            $hasPermission = $user->can('view-activities');
            setPermissionsTeamId(null);

            expect($hasPermission)->toBeTrue();
        })->group('organization-permission', 'security', 'multitenancy');

        test('권한이 없는 사용자는 view-activities 권한이 없음', function (): void {
            ensurePermissionExists('view-activities');
            $user = User::factory()->create(['name' => 'No Role User']);

            expect($user->can('view-activities'))->toBeFalse();
        })->group('organization-permission', 'security', 'multitenancy');
    });

    describe('ORGANIZATION Scope - view-organizations', function (): void {
        test('ORGANIZATION 사용자는 부여된 view-organizations 권한을 가짐', function (): void {
            ensurePermissionExists('view-organizations');

            ['user' => $user, 'role' => $role] = createOrganizationUser(['view-organizations']);

            setPermissionsTeamId($role->team_id);
            $hasPermission = $user->can('view-organizations');
            setPermissionsTeamId(null);

            expect($hasPermission)->toBeTrue();
        })->group('organization-permission', 'security', 'multitenancy');

        test('권한이 없는 사용자는 view-organizations 권한이 없음', function (): void {
            ensurePermissionExists('view-organizations');
            $user = User::factory()->create(['name' => 'No Role User']);

            expect($user->can('view-organizations'))->toBeFalse();
        })->group('organization-permission', 'security', 'multitenancy');
    });

    describe('ORGANIZATION Scope - create-organizations', function (): void {
        test('ORGANIZATION 사용자는 create-organizations 권한이 부여되지 않음', function (): void {
            ensurePermissionExists('create-organizations');

            ['user' => $user, 'role' => $role] = createOrganizationUser(['view-organizations']);

            setPermissionsTeamId($role->team_id);
            $hasPermission = $user->can('create-organizations');
            setPermissionsTeamId(null);

            expect($hasPermission)->toBeFalse();
        })->group('organization-permission', 'security', 'multitenancy');

        test('권한이 없는 사용자는 create-organizations 권한이 없음', function (): void {
            ensurePermissionExists('create-organizations');
            $user = User::factory()->create(['name' => 'No Role User']);

            expect($user->can('create-organizations'))->toBeFalse();
        })->group('organization-permission', 'security', 'multitenancy');
    });

    describe('ORGANIZATION Scope - update-organizations', function (): void {
        test('ORGANIZATION 사용자는 부여된 update-organizations 권한을 가짐', function (): void {
            ensurePermissionExists('update-organizations');

            ['user' => $user, 'role' => $role] = createOrganizationUser(['update-organizations']);

            setPermissionsTeamId($role->team_id);
            $hasPermission = $user->can('update-organizations');
            setPermissionsTeamId(null);

            expect($hasPermission)->toBeTrue();
        })->group('organization-permission', 'security', 'multitenancy');

        test('권한이 없는 사용자는 update-organizations 권한이 없음', function (): void {
            ensurePermissionExists('update-organizations');
            $user = User::factory()->create(['name' => 'No Role User']);

            expect($user->can('update-organizations'))->toBeFalse();
        })->group('organization-permission', 'security', 'multitenancy');
    });

    describe('ORGANIZATION Scope - delete-organizations', function (): void {
        test('ORGANIZATION 사용자는 delete-organizations 권한이 부여되지 않음', function (): void {
            ensurePermissionExists('delete-organizations');

            ['user' => $user, 'role' => $role] = createOrganizationUser(['view-organizations']);

            setPermissionsTeamId($role->team_id);
            $hasPermission = $user->can('delete-organizations');
            setPermissionsTeamId(null);

            expect($hasPermission)->toBeFalse();
        })->group('organization-permission', 'security', 'multitenancy');

        test('권한이 없는 사용자는 delete-organizations 권한이 없음', function (): void {
            ensurePermissionExists('delete-organizations');
            $user = User::factory()->create(['name' => 'No Role User']);

            expect($user->can('delete-organizations'))->toBeFalse();
        })->group('organization-permission', 'security', 'multitenancy');
    });

    describe('ORGANIZATION Scope - restore-organizations', function (): void {
        test('ORGANIZATION 사용자는 restore-organizations 권한이 부여되지 않음', function (): void {
            ensurePermissionExists('restore-organizations');

            ['user' => $user, 'role' => $role] = createOrganizationUser(['view-organizations']);

            setPermissionsTeamId($role->team_id);
            $hasPermission = $user->can('restore-organizations');
            setPermissionsTeamId(null);

            expect($hasPermission)->toBeFalse();
        })->group('organization-permission', 'security', 'multitenancy');
    });

    describe('ORGANIZATION Scope - force-delete-organizations', function (): void {
        test('ORGANIZATION 사용자는 force-delete-organizations 권한이 부여되지 않음', function (): void {
            ensurePermissionExists('force-delete-organizations');

            ['user' => $user, 'role' => $role] = createOrganizationUser(['view-organizations']);

            setPermissionsTeamId($role->team_id);
            $hasPermission = $user->can('force-delete-organizations');
            setPermissionsTeamId(null);

            expect($hasPermission)->toBeFalse();
        })->group('organization-permission', 'security', 'multitenancy');
    });
});

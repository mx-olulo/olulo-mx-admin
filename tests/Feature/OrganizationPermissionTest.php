<?php

declare(strict_types=1);

use App\Enums\ScopeType;
use App\Models\Organization;
use App\Models\Platform;
use App\Models\Role;
use App\Models\System;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

/**
 * Organization Permission 테스트
 *
 * Spatie Permission을 활용한 Organization 권한 체크를 테스트합니다.
 * PLATFORM/SYSTEM 스코프는 Gate::before를 통해 모든 권한을 가지며,
 * ORGANIZATION 스코프는 명시적으로 부여된 권한만 가집니다.
 */
describe('Organization Permission', function () {
    beforeEach(function () {
        // Platform, System 초기 데이터 생성 (ID=1 고정)
        $this->platform = Platform::firstOrCreate(
            ['id' => 1],
            [
                'name' => 'Olulo Platform',
                'description' => 'Global Platform',
                'is_active' => true,
            ]
        );

        $this->system = System::firstOrCreate(
            ['id' => 1],
            [
                'name' => 'Olulo System',
                'description' => 'System Level',
                'is_active' => true,
            ]
        );

        // Organization 생성
        $this->organization1 = Organization::create([
            'name' => 'Test Organization 1',
            'description' => 'First test organization',
            'contact_email' => 'org1@example.com',
            'is_active' => true,
        ]);

        $this->organization2 = Organization::create([
            'name' => 'Test Organization 2',
            'description' => 'Second test organization',
            'contact_email' => 'org2@example.com',
            'is_active' => true,
        ]);

        // Permission 캐시 초기화
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Permission 생성
        $permissions = [
            'view-organizations',
            'create-organizations',
            'update-organizations',
            'delete-organizations',
            'restore-organizations',
            'force-delete-organizations',
            'view-activities',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        // Platform Role 생성 (team_id=1, scope_ref_id=1)
        $this->platformRole = Role::firstOrCreate(
            [
                'name' => 'platform-admin',
                'guard_name' => 'web',
                'team_id' => 1,
                'scope_type' => ScopeType::PLATFORM->value,
                'scope_ref_id' => 1,
            ]
        );

        // System Role 생성 (team_id=2, scope_ref_id=1)
        $this->systemRole = Role::firstOrCreate(
            [
                'name' => 'system-admin',
                'guard_name' => 'web',
                'team_id' => 2,
                'scope_type' => ScopeType::SYSTEM->value,
                'scope_ref_id' => 1,
            ]
        );

        // Organization Roles 생성 (각 Organization별)
        $this->org1Role = Role::firstOrCreate(
            [
                'name' => 'org-admin-1',
                'guard_name' => 'web',
                'team_id' => 10 + $this->organization1->id,
                'scope_type' => ScopeType::ORGANIZATION->value,
                'scope_ref_id' => $this->organization1->id,
            ]
        );

        $this->org2Role = Role::firstOrCreate(
            [
                'name' => 'org-admin-2',
                'guard_name' => 'web',
                'team_id' => 10 + $this->organization2->id,
                'scope_type' => ScopeType::ORGANIZATION->value,
                'scope_ref_id' => $this->organization2->id,
            ]
        );

        // 권한 할당
        setPermissionsTeamId($this->platformRole->team_id);
        $this->platformRole->givePermissionTo($permissions);

        setPermissionsTeamId($this->systemRole->team_id);
        $this->systemRole->givePermissionTo($permissions);

        setPermissionsTeamId($this->org1Role->team_id);
        $this->org1Role->givePermissionTo(['view-organizations', 'update-organizations', 'view-activities']);

        setPermissionsTeamId($this->org2Role->team_id);
        $this->org2Role->givePermissionTo(['view-organizations', 'update-organizations', 'view-activities']);

        // 테스트 사용자 생성 및 Role 할당
        $this->platformUser = User::factory()->create(['name' => 'Platform Admin']);
        setPermissionsTeamId($this->platformRole->team_id);
        $this->platformUser->assignRole($this->platformRole);

        $this->systemUser = User::factory()->create(['name' => 'System Admin']);
        setPermissionsTeamId($this->systemRole->team_id);
        $this->systemUser->assignRole($this->systemRole);

        $this->org1User = User::factory()->create(['name' => 'Org1 User']);
        setPermissionsTeamId($this->org1Role->team_id);
        $this->org1User->assignRole($this->org1Role);

        $this->org2User = User::factory()->create(['name' => 'Org2 User']);
        setPermissionsTeamId($this->org2Role->team_id);
        $this->org2User->assignRole($this->org2Role);

        // Spatie Permission 캐시 초기화
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // User 인스턴스를 fresh()로 다시 로드
        $this->platformUser = $this->platformUser->fresh();
        $this->systemUser = $this->systemUser->fresh();
        $this->org1User = $this->org1User->fresh();
        $this->org2User = $this->org2User->fresh();
    });

    /**
     * 각 테스트 전에 team context 초기화
     */
    beforeEach(function () {
        setPermissionsTeamId(null);
    });

    describe('view-activities', function () {
        test('PLATFORM 사용자는 view-activities 권한을 가짐', function () {
            $this->actingAs($this->platformUser);

            // Gate::before로 인해 모든 권한 자동 부여
            expect($this->platformUser->can('view-activities'))->toBeTrue();
        })->group('organization-permission', 'security', 'multitenancy');

        test('SYSTEM 사용자는 view-activities 권한을 가짐', function () {
            $this->actingAs($this->systemUser);

            // Gate::before로 인해 모든 권한 자동 부여
            expect($this->systemUser->can('view-activities'))->toBeTrue();
        })->group('organization-permission', 'security', 'multitenancy');

        test('ORGANIZATION 사용자는 명시적으로 부여된 view-activities 권한을 가짐', function () {
            $this->actingAs($this->org1User);
            setPermissionsTeamId($this->org1Role->team_id);

            expect($this->org1User->can('view-activities'))->toBeTrue();
        })->group('organization-permission', 'security', 'multitenancy');

        test('권한이 없는 사용자는 view-activities 권한이 없음', function () {
            $userWithoutRole = User::factory()->create(['name' => 'No Role User']);
            $this->actingAs($userWithoutRole);

            expect($userWithoutRole->can('view-activities'))->toBeFalse();
        })->group('organization-permission', 'security', 'multitenancy');
    });

    describe('view-organizations', function () {
        test('PLATFORM 사용자는 view-organizations 권한을 가짐', function () {
            $this->actingAs($this->platformUser);

            expect($this->platformUser->can('view-organizations'))->toBeTrue();
        })->group('organization-permission', 'security', 'multitenancy');

        test('SYSTEM 사용자는 view-organizations 권한을 가짐', function () {
            $this->actingAs($this->systemUser);

            expect($this->systemUser->can('view-organizations'))->toBeTrue();
        })->group('organization-permission', 'security', 'multitenancy');

        test('ORGANIZATION 사용자는 view-organizations 권한을 가짐', function () {
            $this->actingAs($this->org1User);
            setPermissionsTeamId($this->org1Role->team_id);

            expect($this->org1User->can('view-organizations'))->toBeTrue();
        })->group('organization-permission', 'security', 'multitenancy');

        test('권한이 없는 사용자는 view-organizations 권한이 없음', function () {
            $userWithoutRole = User::factory()->create(['name' => 'No Role User']);
            $this->actingAs($userWithoutRole);

            expect($userWithoutRole->can('view-organizations'))->toBeFalse();
        })->group('organization-permission', 'security', 'multitenancy');
    });

    describe('create-organizations', function () {
        test('PLATFORM 사용자는 create-organizations 권한을 가짐', function () {
            $this->actingAs($this->platformUser);

            expect($this->platformUser->can('create-organizations'))->toBeTrue();
        })->group('organization-permission', 'security', 'multitenancy');

        test('SYSTEM 사용자는 create-organizations 권한을 가짐', function () {
            $this->actingAs($this->systemUser);

            expect($this->systemUser->can('create-organizations'))->toBeTrue();
        })->group('organization-permission', 'security', 'multitenancy');

        test('ORGANIZATION 사용자는 create-organizations 권한이 없음', function () {
            $this->actingAs($this->org1User);
            setPermissionsTeamId($this->org1Role->team_id);

            expect($this->org1User->can('create-organizations'))->toBeFalse();
        })->group('organization-permission', 'security', 'multitenancy');

        test('권한이 없는 사용자는 create-organizations 권한이 없음', function () {
            $userWithoutRole = User::factory()->create(['name' => 'No Role User']);
            $this->actingAs($userWithoutRole);

            expect($userWithoutRole->can('create-organizations'))->toBeFalse();
        })->group('organization-permission', 'security', 'multitenancy');
    });

    describe('update-organizations', function () {
        test('PLATFORM 사용자는 update-organizations 권한을 가짐', function () {
            $this->actingAs($this->platformUser);

            expect($this->platformUser->can('update-organizations'))->toBeTrue();
        })->group('organization-permission', 'security', 'multitenancy');

        test('SYSTEM 사용자는 update-organizations 권한을 가짐', function () {
            $this->actingAs($this->systemUser);

            expect($this->systemUser->can('update-organizations'))->toBeTrue();
        })->group('organization-permission', 'security', 'multitenancy');

        test('ORGANIZATION 사용자는 update-organizations 권한을 가짐', function () {
            $this->actingAs($this->org1User);
            setPermissionsTeamId($this->org1Role->team_id);

            expect($this->org1User->can('update-organizations'))->toBeTrue();
        })->group('organization-permission', 'security', 'multitenancy');

        test('권한이 없는 사용자는 update-organizations 권한이 없음', function () {
            $userWithoutRole = User::factory()->create(['name' => 'No Role User']);
            $this->actingAs($userWithoutRole);

            expect($userWithoutRole->can('update-organizations'))->toBeFalse();
        })->group('organization-permission', 'security', 'multitenancy');
    });

    describe('delete-organizations', function () {
        test('PLATFORM 사용자는 delete-organizations 권한을 가짐', function () {
            $this->actingAs($this->platformUser);

            expect($this->platformUser->can('delete-organizations'))->toBeTrue();
        })->group('organization-permission', 'security', 'multitenancy');

        test('SYSTEM 사용자는 delete-organizations 권한을 가짐', function () {
            $this->actingAs($this->systemUser);

            expect($this->systemUser->can('delete-organizations'))->toBeTrue();
        })->group('organization-permission', 'security', 'multitenancy');

        test('ORGANIZATION 사용자는 delete-organizations 권한이 없음', function () {
            $this->actingAs($this->org1User);
            setPermissionsTeamId($this->org1Role->team_id);

            expect($this->org1User->can('delete-organizations'))->toBeFalse();
        })->group('organization-permission', 'security', 'multitenancy');

        test('권한이 없는 사용자는 delete-organizations 권한이 없음', function () {
            $userWithoutRole = User::factory()->create(['name' => 'No Role User']);
            $this->actingAs($userWithoutRole);

            expect($userWithoutRole->can('delete-organizations'))->toBeFalse();
        })->group('organization-permission', 'security', 'multitenancy');
    });

    describe('restore-organizations', function () {
        test('PLATFORM 사용자는 restore-organizations 권한을 가짐', function () {
            $this->actingAs($this->platformUser);

            expect($this->platformUser->can('restore-organizations'))->toBeTrue();
        })->group('organization-permission', 'security', 'multitenancy');

        test('SYSTEM 사용자는 restore-organizations 권한을 가짐', function () {
            $this->actingAs($this->systemUser);

            expect($this->systemUser->can('restore-organizations'))->toBeTrue();
        })->group('organization-permission', 'security', 'multitenancy');

        test('ORGANIZATION 사용자는 restore-organizations 권한이 없음', function () {
            $this->actingAs($this->org1User);
            setPermissionsTeamId($this->org1Role->team_id);

            expect($this->org1User->can('restore-organizations'))->toBeFalse();
        })->group('organization-permission', 'security', 'multitenancy');
    });

    describe('force-delete-organizations', function () {
        test('PLATFORM 사용자는 force-delete-organizations 권한을 가짐', function () {
            $this->actingAs($this->platformUser);

            expect($this->platformUser->can('force-delete-organizations'))->toBeTrue();
        })->group('organization-permission', 'security', 'multitenancy');

        test('SYSTEM 사용자는 force-delete-organizations 권한을 가짐', function () {
            $this->actingAs($this->systemUser);

            expect($this->systemUser->can('force-delete-organizations'))->toBeTrue();
        })->group('organization-permission', 'security', 'multitenancy');

        test('ORGANIZATION 사용자는 force-delete-organizations 권한이 없음', function () {
            $this->actingAs($this->org1User);
            setPermissionsTeamId($this->org1Role->team_id);

            expect($this->org1User->can('force-delete-organizations'))->toBeFalse();
        })->group('organization-permission', 'security', 'multitenancy');
    });
});

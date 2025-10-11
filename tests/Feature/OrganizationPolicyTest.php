<?php

declare(strict_types=1);

use App\Enums\ScopeType;
use App\Models\Organization;
use App\Models\Platform;
use App\Models\Role;
use App\Models\System;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Organization Policy 테스트
 *
 * 멀티테넌시 환경에서 OrganizationPolicy를 통한 권한 검증을 테스트합니다.
 * PLATFORM/SYSTEM 스코프는 모든 Organization에 접근 가능하며,
 * ORGANIZATION 스코프는 자신의 Organization만 접근 가능합니다.
 */
describe('Organization Policy', function () {
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

        // 테스트 사용자 생성 및 Role 할당 (Spatie Permission 방식)
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
     * 사용자의 team_id context를 설정하는 헬퍼
     */
    beforeEach(function () {
        // 각 테스트 전에 team context 초기화
        setPermissionsTeamId(null);
    });

    describe('viewActivities', function () {
        test('PLATFORM 사용자는 모든 Organization의 Activity Log 접근 가능', function () {
            $this->actingAs($this->platformUser);

            // Policy를 통한 권한 검증
            expect($this->platformUser->can('viewActivities', $this->organization1))->toBeTrue();
            expect($this->platformUser->can('viewActivities', $this->organization2))->toBeTrue();
        })->group('organization-policy', 'security', 'multitenancy');

        test('SYSTEM 사용자는 모든 Organization의 Activity Log 접근 가능', function () {
            $this->actingAs($this->systemUser);

            // Policy를 통한 권한 검증
            expect($this->systemUser->can('viewActivities', $this->organization1))->toBeTrue();
            expect($this->systemUser->can('viewActivities', $this->organization2))->toBeTrue();
        })->group('organization-policy', 'security', 'multitenancy');

        test('ORGANIZATION 사용자는 자신의 Organization Activity Log만 접근 가능', function () {
            $this->actingAs($this->org1User);

            // 자신의 Organization Activity Log는 접근 가능
            expect($this->org1User->can('viewActivities', $this->organization1))->toBeTrue();
            expect($this->org1User->can('viewActivities', $this->organization2))->toBeFalse();
        })->group('organization-policy', 'security', 'multitenancy');

        test('권한 없는 Organization의 Activity Log 접근 시 false 반환', function () {
            $this->actingAs($this->org2User);

            // 다른 Organization의 Activity Log는 접근 불가
            expect($this->org2User->can('viewActivities', $this->organization1))->toBeFalse();
            expect($this->org2User->can('viewActivities', $this->organization2))->toBeTrue();
        })->group('organization-policy', 'security', 'multitenancy');
    });

    describe('view', function () {
        test('PLATFORM 사용자는 모든 Organization 조회 가능', function () {
            $this->actingAs($this->platformUser);

            expect($this->platformUser->can('view', $this->organization1))->toBeTrue();
            expect($this->platformUser->can('view', $this->organization2))->toBeTrue();
        })->group('organization-policy', 'security', 'multitenancy');

        test('SYSTEM 사용자는 모든 Organization 조회 가능', function () {
            $this->actingAs($this->systemUser);

            expect($this->systemUser->can('view', $this->organization1))->toBeTrue();
            expect($this->systemUser->can('view', $this->organization2))->toBeTrue();
        })->group('organization-policy', 'security', 'multitenancy');

        test('ORGANIZATION 사용자는 자신의 Organization만 조회 가능', function () {
            $this->actingAs($this->org1User);

            expect($this->org1User->can('view', $this->organization1))->toBeTrue();
            expect($this->org1User->can('view', $this->organization2))->toBeFalse();
        })->group('organization-policy', 'security', 'multitenancy');

        test('권한 없는 Organization 조회 시 false 반환', function () {
            $this->actingAs($this->org2User);

            expect($this->org2User->can('view', $this->organization1))->toBeFalse();
            expect($this->org2User->can('view', $this->organization2))->toBeTrue();
        })->group('organization-policy', 'security', 'multitenancy');
    });

    describe('viewAny', function () {
        test('PLATFORM 사용자는 Organization 목록 조회 가능', function () {
            $this->actingAs($this->platformUser);

            expect($this->platformUser->can('viewAny', Organization::class))->toBeTrue();
        })->group('organization-policy', 'security', 'multitenancy');

        test('SYSTEM 사용자는 Organization 목록 조회 가능', function () {
            $this->actingAs($this->systemUser);

            expect($this->systemUser->can('viewAny', Organization::class))->toBeTrue();
        })->group('organization-policy', 'security', 'multitenancy');

        test('ORGANIZATION 사용자는 Organization 목록 조회 가능 (자신의 것만)', function () {
            $this->actingAs($this->org1User);

            // viewAny는 true를 반환하지만, 실제 쿼리는 스코프로 필터링됨
            expect($this->org1User->can('viewAny', Organization::class))->toBeTrue();
        })->group('organization-policy', 'security', 'multitenancy');

        test('권한이 없는 사용자는 Organization 목록 조회 불가', function () {
            $userWithoutRole = User::factory()->create(['name' => 'No Role User']);
            $this->actingAs($userWithoutRole);

            expect($userWithoutRole->can('viewAny', Organization::class))->toBeFalse();
        })->group('organization-policy', 'security', 'multitenancy');
    });

    describe('create', function () {
        test('PLATFORM 사용자는 Organization 생성 가능', function () {
            $this->actingAs($this->platformUser);

            expect($this->platformUser->can('create', Organization::class))->toBeTrue();
        })->group('organization-policy', 'security', 'multitenancy');

        test('SYSTEM 사용자는 Organization 생성 가능', function () {
            $this->actingAs($this->systemUser);

            expect($this->systemUser->can('create', Organization::class))->toBeTrue();
        })->group('organization-policy', 'security', 'multitenancy');

        test('ORGANIZATION 사용자는 Organization 생성 불가', function () {
            $this->actingAs($this->org1User);

            expect($this->org1User->can('create', Organization::class))->toBeFalse();
        })->group('organization-policy', 'security', 'multitenancy');

        test('권한이 없는 사용자는 Organization 생성 불가', function () {
            $userWithoutRole = User::factory()->create(['name' => 'No Role User']);
            $this->actingAs($userWithoutRole);

            expect($userWithoutRole->can('create', Organization::class))->toBeFalse();
        })->group('organization-policy', 'security', 'multitenancy');
    });

    describe('update', function () {
        test('PLATFORM 사용자는 모든 Organization 수정 가능', function () {
            $this->actingAs($this->platformUser);

            expect($this->platformUser->can('update', $this->organization1))->toBeTrue();
            expect($this->platformUser->can('update', $this->organization2))->toBeTrue();
        })->group('organization-policy', 'security', 'multitenancy');

        test('SYSTEM 사용자는 모든 Organization 수정 가능', function () {
            $this->actingAs($this->systemUser);

            expect($this->systemUser->can('update', $this->organization1))->toBeTrue();
            expect($this->systemUser->can('update', $this->organization2))->toBeTrue();
        })->group('organization-policy', 'security', 'multitenancy');

        test('ORGANIZATION 사용자는 자신의 Organization만 수정 가능', function () {
            $this->actingAs($this->org1User);

            expect($this->org1User->can('update', $this->organization1))->toBeTrue();
            expect($this->org1User->can('update', $this->organization2))->toBeFalse();
        })->group('organization-policy', 'security', 'multitenancy');

        test('권한 없는 Organization 수정 시 false 반환', function () {
            $this->actingAs($this->org2User);

            expect($this->org2User->can('update', $this->organization1))->toBeFalse();
            expect($this->org2User->can('update', $this->organization2))->toBeTrue();
        })->group('organization-policy', 'security', 'multitenancy');
    });

    describe('delete', function () {
        test('PLATFORM 사용자는 모든 Organization 삭제 가능', function () {
            $this->actingAs($this->platformUser);

            expect($this->platformUser->can('delete', $this->organization1))->toBeTrue();
            expect($this->platformUser->can('delete', $this->organization2))->toBeTrue();
        })->group('organization-policy', 'security', 'multitenancy');

        test('SYSTEM 사용자는 모든 Organization 삭제 가능', function () {
            $this->actingAs($this->systemUser);

            expect($this->systemUser->can('delete', $this->organization1))->toBeTrue();
            expect($this->systemUser->can('delete', $this->organization2))->toBeTrue();
        })->group('organization-policy', 'security', 'multitenancy');

        test('ORGANIZATION 사용자는 Organization 삭제 불가', function () {
            $this->actingAs($this->org1User);

            expect($this->org1User->can('delete', $this->organization1))->toBeFalse();
            expect($this->org1User->can('delete', $this->organization2))->toBeFalse();
        })->group('organization-policy', 'security', 'multitenancy');

        test('권한이 없는 사용자는 Organization 삭제 불가', function () {
            $userWithoutRole = User::factory()->create(['name' => 'No Role User']);
            $this->actingAs($userWithoutRole);

            expect($userWithoutRole->can('delete', $this->organization1))->toBeFalse();
        })->group('organization-policy', 'security', 'multitenancy');
    });

    describe('restore', function () {
        test('PLATFORM 사용자는 Organization 복원 가능', function () {
            $this->actingAs($this->platformUser);

            expect($this->platformUser->can('restore', $this->organization1))->toBeTrue();
        })->group('organization-policy', 'security', 'multitenancy');

        test('SYSTEM 사용자는 Organization 복원 가능', function () {
            $this->actingAs($this->systemUser);

            expect($this->systemUser->can('restore', $this->organization1))->toBeTrue();
        })->group('organization-policy', 'security', 'multitenancy');

        test('ORGANIZATION 사용자는 Organization 복원 불가', function () {
            $this->actingAs($this->org1User);

            expect($this->org1User->can('restore', $this->organization1))->toBeFalse();
        })->group('organization-policy', 'security', 'multitenancy');
    });

    describe('forceDelete', function () {
        test('PLATFORM 사용자는 Organization 영구 삭제 가능', function () {
            $this->actingAs($this->platformUser);

            expect($this->platformUser->can('forceDelete', $this->organization1))->toBeTrue();
        })->group('organization-policy', 'security', 'multitenancy');

        test('SYSTEM 사용자는 Organization 영구 삭제 가능', function () {
            $this->actingAs($this->systemUser);

            expect($this->systemUser->can('forceDelete', $this->organization1))->toBeTrue();
        })->group('organization-policy', 'security', 'multitenancy');

        test('ORGANIZATION 사용자는 Organization 영구 삭제 불가', function () {
            $this->actingAs($this->org1User);

            expect($this->org1User->can('forceDelete', $this->organization1))->toBeFalse();
        })->group('organization-policy', 'security', 'multitenancy');
    });
});

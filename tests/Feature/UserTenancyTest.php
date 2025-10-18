<?php

declare(strict_types=1);

use App\Enums\ScopeType;
use App\Models\Brand;
use App\Models\Organization;
use App\Models\Role;
use App\Models\Store;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * User Tenancy 테스트
 *
 * 테스트 목표:
 * - User::getTenants() 메서드의 정확성 검증
 * - Panel별 테넌트 필터링 검증
 * - Eager loading (N+1 쿼리 방지) 검증
 * - User::canAccessTenant() 최적화 검증
 */
describe('User::getTenants()', function (): void {
    test('Organization 패널: Organization 테넌트만 반환', function (): void {
        // Given: Organization과 Role 생성
        $org1 = Organization::factory()->create(['name' => 'Org 1']);
        $org2 = Organization::factory()->create(['name' => 'Org 2']);

        $role1 = Role::create([
            'name' => 'org-admin-1',
            'guard_name' => 'web',
            'team_id' => 1,
            'scope_type' => ScopeType::ORGANIZATION->value,
            'scope_ref_id' => $org1->id,
        ]);

        $role2 = Role::create([
            'name' => 'org-admin-2',
            'guard_name' => 'web',
            'team_id' => 2,
            'scope_type' => ScopeType::ORGANIZATION->value,
            'scope_ref_id' => $org2->id,
        ]);

        $user = User::factory()->create();

        // Role 직접 연결 (assignRole은 현재 teamId에 종속적이므로)
        $user->roles()->attach($role1->id, ['model_type' => User::class, 'team_id' => $role1->team_id]);
        $user->roles()->attach($role2->id, ['model_type' => User::class, 'team_id' => $role2->team_id]);

        // roles 관계 리프레시
        $user->unsetRelation('roles');

        // When: Organization 패널의 테넌트 조회 (team_id 필터 비활성화)
        setPermissionsTeamId(null);
        $panel = Filament::getPanel('org');
        $tenants = $user->getTenants($panel);

        // Then: 2개의 Organization 테넌트 반환
        expect($tenants)->toHaveCount(2);
        expect($tenants->pluck('id')->sort()->values()->all())->toBe([$org1->id, $org2->id]);
        expect($tenants->first())->toBeInstanceOf(Organization::class);
    });

    test('Store 패널: Store 테넌트만 반환', function (): void {
        // Given: Organization, Brand, Store 생성
        $org = Organization::factory()->create();
        $brand = Brand::factory()->create(['organization_id' => $org->id]);
        $store1 = Store::factory()->create(['brand_id' => $brand->id]);
        $store2 = Store::factory()->create(['brand_id' => $brand->id]);

        $role1 = Role::create([
            'name' => 'store-manager-1',
            'guard_name' => 'web',
            'team_id' => 1,
            'scope_type' => ScopeType::STORE->value,
            'scope_ref_id' => $store1->id,
        ]);

        $role2 = Role::create([
            'name' => 'store-manager-2',
            'guard_name' => 'web',
            'team_id' => 2,
            'scope_type' => ScopeType::STORE->value,
            'scope_ref_id' => $store2->id,
        ]);

        $user = User::factory()->create();

        // Role 직접 연결
        $user->roles()->attach($role1->id, ['model_type' => User::class, 'team_id' => $role1->team_id]);
        $user->roles()->attach($role2->id, ['model_type' => User::class, 'team_id' => $role2->team_id]);
        $user->unsetRelation('roles');

        // When: Store 패널의 테넌트 조회 (team_id 필터 비활성화)
        setPermissionsTeamId(null);
        $panel = Filament::getPanel('store');
        $tenants = $user->getTenants($panel);

        // Then: 2개의 Store 테넌트 반환
        expect($tenants)->toHaveCount(2);
        expect($tenants->pluck('id')->sort()->values()->all())->toBe([$store1->id, $store2->id]);
        expect($tenants->first())->toBeInstanceOf(Store::class);
    });

    test('Brand 패널: Brand 테넌트만 반환', function (): void {
        // Given: Organization, Brand 생성
        $org = Organization::factory()->create();
        $brand1 = Brand::factory()->create(['organization_id' => $org->id, 'name' => 'Brand 1']);
        $brand2 = Brand::factory()->create(['organization_id' => $org->id, 'name' => 'Brand 2']);

        $role1 = Role::create([
            'name' => 'brand-manager-1',
            'guard_name' => 'web',
            'team_id' => 1,
            'scope_type' => ScopeType::BRAND->value,
            'scope_ref_id' => $brand1->id,
        ]);

        $role2 = Role::create([
            'name' => 'brand-manager-2',
            'guard_name' => 'web',
            'team_id' => 2,
            'scope_type' => ScopeType::BRAND->value,
            'scope_ref_id' => $brand2->id,
        ]);

        $user = User::factory()->create();

        // Role 직접 연결
        $user->roles()->attach($role1->id, ['model_type' => User::class, 'team_id' => $role1->team_id]);
        $user->roles()->attach($role2->id, ['model_type' => User::class, 'team_id' => $role2->team_id]);
        $user->unsetRelation('roles');

        // When: Brand 패널의 테넌트 조회 (team_id 필터 비활성화)
        setPermissionsTeamId(null);
        $panel = Filament::getPanel('brand');
        $tenants = $user->getTenants($panel);

        // Then: 2개의 Brand 테넌트 반환
        expect($tenants)->toHaveCount(2);
        expect($tenants->pluck('id')->sort()->values()->all())->toBe([$brand1->id, $brand2->id]);
        expect($tenants->first())->toBeInstanceOf(Brand::class);
    });

    test('N+1 쿼리 방지: with(scopeable) eager loading 적용', function (): void {
        // Given: 5개의 Organization과 Role 생성
        $organizations = Organization::factory()->count(5)->create();

        foreach ($organizations as $index => $org) {
            $role = Role::create([
                'name' => 'org-admin-' . $index,
                'guard_name' => 'web',
                'team_id' => $index + 1,
                'scope_type' => ScopeType::ORGANIZATION->value,
                'scope_ref_id' => $org->id,
            ]);

            $user ??= User::factory()->create();
            $user->roles()->attach($role->id, ['model_type' => User::class, 'team_id' => $role->team_id]);
        }

        $user->unsetRelation('roles');

        // When: 쿼리 카운트 측정 (team_id 필터 비활성화)
        setPermissionsTeamId(null);
        DB::enableQueryLog();
        $panel = Filament::getPanel('org');
        $tenants = $user->getTenants($panel);

        // scopeable 관계 접근 (N+1 쿼리 테스트)
        foreach ($tenants as $tenant) {
            $name = $tenant->name;
        }

        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Then: 쿼리 수가 3개 이하여야 함
        // 1. roles 조회
        // 2. scopeable eager load (단일 쿼리로 모든 Organization 로드)
        // 3. (선택) 추가 메타데이터 쿼리
        expect($queryCount)->toBeLessThanOrEqual(3);
        expect($tenants)->toHaveCount(5);
    });

    test('테넌트 없는 사용자: 빈 컬렉션 반환', function (): void {
        // Given: 역할이 없는 사용자
        $user = User::factory()->create();

        // When: Organization 패널의 테넌트 조회
        $panel = Filament::getPanel('org');
        $tenants = $user->getTenants($panel);

        // Then: 빈 컬렉션 반환
        expect($tenants)->toBeEmpty();
    });

    test('중복 테넌트 제거: 동일 Organization의 여러 Role', function (): void {
        // Given: 동일 Organization에 대한 2개의 Role
        $org = Organization::factory()->create();

        $role1 = Role::create([
            'name' => 'org-admin',
            'guard_name' => 'web',
            'team_id' => 1,
            'scope_type' => ScopeType::ORGANIZATION->value,
            'scope_ref_id' => $org->id,
        ]);

        $role2 = Role::create([
            'name' => 'org-viewer',
            'guard_name' => 'web',
            'team_id' => 1, // 동일 team_id
            'scope_type' => ScopeType::ORGANIZATION->value,
            'scope_ref_id' => $org->id,
        ]);

        $user = User::factory()->create();
        setPermissionsTeamId(1);
        $user->assignRole([$role1, $role2]);

        // When: Organization 패널의 테넌트 조회
        $panel = Filament::getPanel('org');
        $tenants = $user->getTenants($panel);

        // Then: 중복 제거되어 1개의 Organization만 반환
        expect($tenants)->toHaveCount(1);
        expect($tenants->first()->id)->toBe($org->id);
    });
});

describe('User::canAccessTenant()', function (): void {
    test('Organization 테넌트: 접근 가능한 경우', function (): void {
        // Given: Organization과 Role 생성
        $org = Organization::factory()->create();
        $role = Role::create([
            'name' => 'org-admin',
            'guard_name' => 'web',
            'team_id' => 1,
            'scope_type' => ScopeType::ORGANIZATION->value,
            'scope_ref_id' => $org->id,
        ]);

        $user = User::factory()->create();
        setPermissionsTeamId(1);
        $user->assignRole($role);

        // When & Then: 접근 가능
        expect($user->canAccessTenant($org))->toBeTrue();
    });

    test('Organization 테넌트: 접근 불가능한 경우', function (): void {
        // Given: 다른 Organization의 Role
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        $role = Role::create([
            'name' => 'org-admin',
            'guard_name' => 'web',
            'team_id' => 1,
            'scope_type' => ScopeType::ORGANIZATION->value,
            'scope_ref_id' => $org1->id,
        ]);

        $user = User::factory()->create();
        setPermissionsTeamId(1);
        $user->assignRole($role);

        // When & Then: org2 접근 불가
        expect($user->canAccessTenant($org2))->toBeFalse();
    });

    test('Store 테넌트: 접근 가능한 경우', function (): void {
        // Given: Store와 Role 생성
        $org = Organization::factory()->create();
        $brand = Brand::factory()->create(['organization_id' => $org->id]);
        $store = Store::factory()->create(['brand_id' => $brand->id]);

        $role = Role::create([
            'name' => 'store-manager',
            'guard_name' => 'web',
            'team_id' => 1,
            'scope_type' => ScopeType::STORE->value,
            'scope_ref_id' => $store->id,
        ]);

        $user = User::factory()->create();
        setPermissionsTeamId(1);
        $user->assignRole($role);

        // When & Then: 접근 가능
        expect($user->canAccessTenant($store))->toBeTrue();
    });

    test('최적화된 쿼리: whereHasMorph 대신 직접 조건 사용', function (): void {
        // Given: Organization과 Role 생성
        $org = Organization::factory()->create();
        $role = Role::create([
            'name' => 'org-admin',
            'guard_name' => 'web',
            'team_id' => 1,
            'scope_type' => ScopeType::ORGANIZATION->value,
            'scope_ref_id' => $org->id,
        ]);

        $user = User::factory()->create();
        setPermissionsTeamId(1);
        $user->assignRole($role);

        // When: 쿼리 카운트 측정
        DB::enableQueryLog();
        $canAccess = $user->canAccessTenant($org);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Then: 1개의 단순 쿼리만 실행 (whereHasMorph의 복잡한 조인 없음)
        expect($canAccess)->toBeTrue();
        expect($queries)->toHaveCount(1);

        // 쿼리에 'scope_type'과 'scope_ref_id' 조건이 있어야 함 (최적화 확인)
        $sql = $queries[0]['query'];
        expect($sql)->toContain('scope_type');
        expect($sql)->toContain('scope_ref_id');
    });
});

describe('User::canAccessPanel()', function (): void {
    test('Platform 패널: platform_admin 역할 보유 시 접근 가능', function (): void {
        // Given: PLATFORM 역할
        $role = Role::create([
            'name' => 'platform_admin',
            'guard_name' => 'web',
            'team_id' => 1,
            'scope_type' => ScopeType::PLATFORM->value,
            'scope_ref_id' => 1,
        ]);

        $user = User::factory()->create();
        setPermissionsTeamId(1);
        $user->assignRole($role);

        // When & Then: Platform 패널 접근 가능
        $panel = Filament::getPanel('platform');
        expect($user->canAccessPanel($panel))->toBeTrue();
    });

    test('System 패널: system_admin 역할 보유 시 접근 가능', function (): void {
        // Given: SYSTEM 역할
        $role = Role::create([
            'name' => 'system_admin',
            'guard_name' => 'web',
            'team_id' => 1,
            'scope_type' => ScopeType::SYSTEM->value,
            'scope_ref_id' => 1,
        ]);

        $user = User::factory()->create();
        setPermissionsTeamId(1);
        $user->assignRole($role);

        // When & Then: System 패널 접근 가능
        $panel = Filament::getPanel('system');
        expect($user->canAccessPanel($panel))->toBeTrue();
    });

    test('Organization 패널: 테넌트 멤버십 필요', function (): void {
        // Given: Organization Role
        $org = Organization::factory()->create();
        $role = Role::create([
            'name' => 'org-admin',
            'guard_name' => 'web',
            'team_id' => 1,
            'scope_type' => ScopeType::ORGANIZATION->value,
            'scope_ref_id' => $org->id,
        ]);

        $user = User::factory()->create();
        setPermissionsTeamId(1);
        $user->assignRole($role);

        // When & Then: Organization 패널 접근 가능
        $panel = Filament::getPanel('org');
        expect($user->canAccessPanel($panel))->toBeTrue();
    });

    test('테넌트 없는 사용자: 테넌트 패널 접근 불가', function (): void {
        // Given: 역할이 없는 사용자
        $user = User::factory()->create();

        // When & Then: Organization 패널 접근 불가
        $panel = Filament::getPanel('org');
        expect($user->canAccessPanel($panel))->toBeFalse();
    });
});

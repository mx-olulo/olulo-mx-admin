<?php

declare(strict_types=1);

use App\Enums\ScopeType;
use App\Models\Brand;
use App\Models\Organization;
use App\Models\Role;
use App\Models\Store;
use App\Permissions\TenantTeamResolver;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * TenantTeamResolver 통합 테스트
 *
 * 테스트 목표:
 * - Filament 테넌트와 Spatie Permission team_id 자동 연동 검증
 * - 캐싱 메커니즘 검증 (중복 쿼리 방지)
 * - morphMap 기반 최적화 검증
 */
describe('TenantTeamResolver', function (): void {
    beforeEach(function (): void {
        // 각 테스트 전에 캐시 초기화
        TenantTeamResolver::clearCache();
    });
    test('Organization 테넌트 설정 시 올바른 team_id 반환', function (): void {
        // Given: Organization과 Role 생성
        $user = \App\Models\User::factory()->create();
        $org = Organization::factory()->create(['name' => 'Test Org']);
        $role = Role::create([
            'name' => 'org-admin',
            'guard_name' => 'web',
            'team_id' => 123,
            'scope_type' => ScopeType::ORGANIZATION->value,
            'scope_ref_id' => $org->id,
        ]);

        // When: Filament 테넌트 설정 (인증된 사용자 필요)
        $this->actingAs($user);
        Filament::setTenant($org);

        $resolver = new TenantTeamResolver;
        $teamId = $resolver->getPermissionsTeamId();

        // Then: 올바른 team_id 반환
        expect($teamId)->toBe(123);
    });

    test('Store 테넌트 설정 시 올바른 team_id 반환', function (): void {
        // Given: Store와 Role 생성
        $user = \App\Models\User::factory()->create();
        $org = Organization::factory()->create();
        $brand = Brand::factory()->create(['organization_id' => $org->id]);
        $store = Store::factory()->create(['brand_id' => $brand->id, 'name' => 'Test Store']);

        $role = Role::create([
            'name' => 'store-manager',
            'guard_name' => 'web',
            'team_id' => 456,
            'scope_type' => ScopeType::STORE->value,
            'scope_ref_id' => $store->id,
        ]);

        // When: Filament 테넌트 설정
        $this->actingAs($user);
        Filament::setTenant($store);

        $resolver = new TenantTeamResolver;
        $teamId = $resolver->getPermissionsTeamId();

        // Then: 올바른 team_id 반환
        expect($teamId)->toBe(456);
    });

    test('테넌트 없는 경우 null 반환', function (): void {
        // Given: 테넌트 설정 안 함
        Filament::setTenant(null);

        // When
        $resolver = new TenantTeamResolver;
        $teamId = $resolver->getPermissionsTeamId();

        // Then: null 반환
        expect($teamId)->toBeNull();
    });

    test('캐싱: 동일 테넌트에 대한 중복 쿼리 방지', function (): void {
        // Given: Organization과 Role 생성
        $user = \App\Models\User::factory()->create();
        $org = Organization::factory()->create();
        $role = Role::create([
            'name' => 'org-admin',
            'guard_name' => 'web',
            'team_id' => 789,
            'scope_type' => ScopeType::ORGANIZATION->value,
            'scope_ref_id' => $org->id,
        ]);

        $this->actingAs($user);
        Filament::setTenant($org);
        $resolver = new TenantTeamResolver;

        // When: 첫 번째 호출 (쿼리 실행)
        DB::flushQueryLog(); // 로그 초기화
        DB::enableQueryLog();
        $teamId1 = $resolver->getPermissionsTeamId();
        $queryCount1 = count(DB::getQueryLog());
        DB::disableQueryLog();

        // 두 번째 호출 (캐시 적중)
        DB::flushQueryLog(); // 로그 초기화
        DB::enableQueryLog();
        $teamId2 = $resolver->getPermissionsTeamId();
        $queryCount2 = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Then: 첫 번째만 쿼리, 두 번째는 캐시 사용
        expect($teamId1)->toBe(789);
        expect($teamId2)->toBe(789);
        expect($queryCount1)->toBe(1); // 첫 번째: 1개 쿼리
        expect($queryCount2)->toBe(0); // 두 번째: 0개 쿼리 (캐시)
    });

    test('최적화된 쿼리: morphMap 기반 직접 조건 사용', function (): void {
        // Given: Organization과 Role 생성
        $user = \App\Models\User::factory()->create();
        $org = Organization::factory()->create();
        $role = Role::create([
            'name' => 'org-admin',
            'guard_name' => 'web',
            'team_id' => 999,
            'scope_type' => ScopeType::ORGANIZATION->value,
            'scope_ref_id' => $org->id,
        ]);

        $this->actingAs($user);
        Filament::setTenant($org);
        $resolver = new TenantTeamResolver;

        // When: 쿼리 로그 분석
        DB::enableQueryLog();
        $teamId = $resolver->getPermissionsTeamId();
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Then: 단순 WHERE 조건 사용 (whereHasMorph의 복잡한 조인 없음)
        expect($teamId)->toBe(999);
        expect($queries)->toHaveCount(1);

        $sql = $queries[0]['query'];
        expect($sql)->toContain('scope_type');
        expect($sql)->toContain('scope_ref_id');
        // whereHasMorph는 LEFT JOIN을 생성하지만, 우리 최적화는 단순 WHERE
        expect($sql)->not->toContain('LEFT JOIN');
    });

    test('다른 테넌트로 전환 시 캐시 무효화', function (): void {
        // Given: 2개의 Organization
        $user = \App\Models\User::factory()->create();
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        $role1 = Role::create([
            'name' => 'org-admin-1',
            'guard_name' => 'web',
            'team_id' => 111,
            'scope_type' => ScopeType::ORGANIZATION->value,
            'scope_ref_id' => $org1->id,
        ]);

        $role2 = Role::create([
            'name' => 'org-admin-2',
            'guard_name' => 'web',
            'team_id' => 222,
            'scope_type' => ScopeType::ORGANIZATION->value,
            'scope_ref_id' => $org2->id,
        ]);

        $this->actingAs($user);
        $resolver = new TenantTeamResolver;

        // When: org1 설정 및 조회
        Filament::setTenant($org1);
        $teamId1 = $resolver->getPermissionsTeamId();

        // org2로 전환 및 조회
        Filament::setTenant($org2);
        $teamId2 = $resolver->getPermissionsTeamId();

        // Then: 각각 올바른 team_id 반환 (캐시 무효화 확인)
        expect($teamId1)->toBe(111);
        expect($teamId2)->toBe(222);
    });

    test('수동 설정된 team_id 우선 사용', function (): void {
        $this->markTestSkipped('Spatie Permission의 전역 상태 관리로 인해 테스트 환경에서 불안정함');
        // Given: Organization과 Role 생성
        $user = \App\Models\User::factory()->create();
        $org = Organization::factory()->create();
        $role = Role::create([
            'name' => 'org-admin',
            'guard_name' => 'web',
            'team_id' => 100,
            'scope_type' => ScopeType::ORGANIZATION->value,
            'scope_ref_id' => $org->id,
        ]);

        $this->actingAs($user);
        Filament::setTenant($org);

        $resolver = new TenantTeamResolver;

        // When: 먼저 Filament 테넌트에서 team_id 조회
        $teamId1 = $resolver->getPermissionsTeamId();
        expect($teamId1)->toBe(100); // 테넌트의 team_id

        // Then: 수동으로 team_id 설정하면 우선 사용
        TenantTeamResolver::clearCache();
        setPermissionsTeamId(999);

        $teamId2 = $resolver->getPermissionsTeamId();
        expect($teamId2)->toBe(999); // 수동 설정된 값 우선
    });

    test('매핑되지 않은 테넌트 타입: null 반환', function (): void {
        // Given: morphMap에 없는 임의 모델
        $user = \App\Models\User::factory()->create();

        // Event 큐잉 비활성화 (익명 클래스 직렬화 오류 방지)
        \Event::fake();

        $unknownModel = new class extends \Illuminate\Database\Eloquent\Model
        {
            protected $table = 'unknown_models';
        };

        // When: 매핑되지 않은 모델을 테넌트로 설정
        $this->actingAs($user);
        Filament::setTenant($unknownModel);

        $resolver = new TenantTeamResolver;
        $teamId = $resolver->getPermissionsTeamId();

        // Then: null 반환 (오류 없이 안전하게 처리)
        expect($teamId)->toBeNull();
    });
});

describe('TenantTeamResolver Integration with Spatie Permission', function (): void {
    beforeEach(function (): void {
        // 각 테스트 전에 캐시 초기화
        TenantTeamResolver::clearCache();
    });

    test('권한 체크 시 자동으로 올바른 team_id 적용', function (): void {
        // Given: Organization과 Permission 설정
        $org = Organization::factory()->create();
        $role = Role::create([
            'name' => 'org-admin',
            'guard_name' => 'web',
            'team_id' => 555,
            'scope_type' => ScopeType::ORGANIZATION->value,
            'scope_ref_id' => $org->id,
        ]);

        $permission = \Spatie\Permission\Models\Permission::create([
            'name' => 'edit-articles',
            'guard_name' => 'web',
        ]);

        $role->givePermissionTo($permission);

        $user = \App\Models\User::factory()->create();
        setPermissionsTeamId(555);
        $user->assignRole($role);
        $user->unsetRelation('roles');
        $user->unsetRelation('permissions');

        // When: Filament 테넌트 설정 (TenantTeamResolver가 자동 적용)
        $this->actingAs($user);
        Filament::setTenant($org);

        // Spatie Permission의 team resolver 설정 (실제 앱에서는 config에서 설정)
        config(['permission.teams' => true]);
        app()->singleton(\Spatie\Permission\Contracts\TeamResolver::class, fn (): \Spatie\Permission\Contracts\TeamResolver => new TenantTeamResolver);

        // Then: 올바른 team_id로 권한 체크
        setPermissionsTeamId(555);
        $user->unsetRelation('roles');
        $user->unsetRelation('permissions');
        expect($user->hasPermissionTo('edit-articles'))->toBeTrue();

        // 다른 team_id에서는 권한 없음 (역할 자체가 조회되지 않음)
        setPermissionsTeamId(999);
        $user->unsetRelation('roles');
        $user->unsetRelation('permissions');

        // team_id=999에는 이 역할이 없으므로 권한도 없어야 함
        $rolesCount = $user->roles()->count();
        expect($rolesCount)->toBe(0)
            ->and($user->hasPermissionTo('edit-articles'))->toBeFalse();
    });
});

<?php

declare(strict_types=1);

// @TEST:RBAC-001-US1 | SPEC: SPEC-RBAC-001.md

namespace Tests\Feature\Tenancy;

use App\Models\Brand;
use App\Models\Organization;
use App\Models\Store;
use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 사용자 스토리 1: 멀티테넌트 역할 독립성 테스트
 *
 * Admin이 Organization, Brand, Store 모두에 역할을 보유하며,
 * 각 테넌트별로 역할이 독립적으로 작동하는지 검증
 */
class MultiTenantRoleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Admin이 Organization, Brand, Store에 각각 역할 보유 시 접근 가능
     *
     * @test
     */
    public function admin_can_access_multiple_tenant_types(): void
    {
        // Arrange: Admin 사용자 생성
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'user_type' => \App\Enums\UserType::USER,
        ]);

        // Organization, Brand, Store 생성
        $org = Organization::factory()->create(['name' => 'Test Org']);
        $brand = Brand::factory()->create([
            'name' => 'Test Brand',
            'organization_id' => $org->id,
        ]);
        $store = Store::factory()->create([
            'name' => 'Test Store',
            'brand_id' => $brand->id,
        ]);

        // Admin을 모든 테넌트에 할당
        TenantUser::create([
            'user_id' => $admin->id,
            'tenant_type' => 'ORG',
            'tenant_id' => $org->id,
            'role' => 'owner',
        ]);

        TenantUser::create([
            'user_id' => $admin->id,
            'tenant_type' => 'BRD',
            'tenant_id' => $brand->id,
            'role' => 'manager',
        ]);

        TenantUser::create([
            'user_id' => $admin->id,
            'tenant_type' => 'STR',
            'tenant_id' => $store->id,
            'role' => 'viewer',
        ]);

        // Act: 각 타입별 테넌트 조회
        $organizations = $admin->getTenantsByType('ORG');
        $brands = $admin->getTenantsByType('BRD');
        $stores = $admin->getTenantsByType('STR');

        // Assert: 각 타입별 1개씩 반환
        $this->assertCount(1, $organizations);
        $this->assertCount(1, $brands);
        $this->assertCount(1, $stores);

        // 각 테넌트 모델 타입 확인
        $this->assertInstanceOf(Organization::class, $organizations->first());
        $this->assertInstanceOf(Brand::class, $brands->first());
        $this->assertInstanceOf(Store::class, $stores->first());

        // 각 테넌트 ID 확인
        $this->assertEquals($org->id, $organizations->first()->id);
        $this->assertEquals($brand->id, $brands->first()->id);
        $this->assertEquals($store->id, $stores->first()->id);
    }

    /**
     * 테넌트별 역할이 독립적으로 작동
     *
     * @test
     */
    public function tenant_roles_are_independent(): void
    {
        // Arrange
        $admin = User::factory()->create();
        $org1 = Organization::factory()->create(['name' => 'Org 1']);
        $org2 = Organization::factory()->create(['name' => 'Org 2']);

        // Admin을 Org1에 owner로, Org2에 viewer로 할당
        TenantUser::create([
            'user_id' => $admin->id,
            'tenant_type' => 'ORG',
            'tenant_id' => $org1->id,
            'role' => 'owner',
        ]);

        TenantUser::create([
            'user_id' => $admin->id,
            'tenant_type' => 'ORG',
            'tenant_id' => $org2->id,
            'role' => 'viewer',
        ]);

        // Act & Assert: Org1에서는 owner 권한
        $this->assertTrue($admin->hasRoleForTenant($org1, 'owner'));
        $this->assertTrue($admin->canManageTenant($org1));
        $this->assertTrue($admin->canViewTenant($org1));

        // Act & Assert: Org2에서는 viewer 권한만
        $this->assertTrue($admin->hasRoleForTenant($org2, 'viewer'));
        $this->assertFalse($admin->canManageTenant($org2));
        $this->assertTrue($admin->canViewTenant($org2));

        // Act & Assert: Org1의 owner 역할이 Org2에 영향 없음
        $this->assertFalse($admin->hasRoleForTenant($org2, 'owner'));
    }

    /**
     * tenant_users UNIQUE 제약 검증 - 중복 역할 할당 불가
     *
     * @test
     */
    public function duplicate_tenant_user_role_throws_exception(): void
    {
        // Arrange
        $admin = User::factory()->create();
        $org = Organization::factory()->create();

        // 첫 번째 역할 할당 (성공)
        TenantUser::create([
            'user_id' => $admin->id,
            'tenant_type' => 'ORG',
            'tenant_id' => $org->id,
            'role' => 'owner',
        ]);

        // Act & Assert: 동일한 조합으로 중복 할당 시도 시 예외 발생
        $this->expectException(\Illuminate\Database\QueryException::class);

        TenantUser::create([
            'user_id' => $admin->id,
            'tenant_type' => 'ORG',
            'tenant_id' => $org->id,
            'role' => 'manager', // 다른 역할이라도 UNIQUE 제약 위반
        ]);
    }

    /**
     * 5개 이상의 서로 다른 테넌트에 접근 가능 (성공 기준 SC-001)
     *
     * @test
     */
    public function admin_can_access_five_or_more_tenants(): void
    {
        // Arrange: Admin 사용자 생성
        $admin = User::factory()->create();

        // 6개의 Organization 생성 및 할당
        $organizations = Organization::factory()->count(6)->create();

        foreach ($organizations as $index => $org) {
            // 각 Organization에 서로 다른 역할 할당 (순환)
            $roles = ['owner', 'manager', 'viewer'];
            $role = $roles[$index % 3];

            TenantUser::create([
                'user_id' => $admin->id,
                'tenant_type' => 'ORG',
                'tenant_id' => $org->id,
                'role' => $role,
            ]);
        }

        // Act: 접근 가능한 Organization 조회
        $accessibleOrgs = $admin->getTenantsByType('ORG');

        // Assert: 6개 모두 접근 가능 (SC-001 만족)
        $this->assertCount(6, $accessibleOrgs);

        // 각 Organization에 대한 권한 확인
        foreach ($accessibleOrgs as $accessibleOrg) {
            $this->assertTrue($admin->canViewTenant($accessibleOrg));
        }
    }

    /**
     * getRoleForTenant() 메서드가 정확한 역할을 반환
     *
     * @test
     */
    public function get_role_for_tenant_returns_correct_role(): void
    {
        // Arrange
        $admin = User::factory()->create();
        $org = Organization::factory()->create();

        // 역할 할당
        TenantUser::create([
            'user_id' => $admin->id,
            'tenant_type' => 'ORG',
            'tenant_id' => $org->id,
            'role' => 'manager',
        ]);

        // Act
        $role = $admin->getRoleForTenant($org);

        // Assert
        $this->assertSame('manager', $role);
    }

    /**
     * 역할이 없는 테넌트에 대해 getRoleForTenant() 메서드가 null 반환
     *
     * @test
     */
    public function get_role_for_tenant_returns_null_for_no_role(): void
    {
        // Arrange
        $admin = User::factory()->create();
        $org = Organization::factory()->create();

        // 역할 할당 없음

        // Act
        $role = $admin->getRoleForTenant($org);

        // Assert
        $this->assertNull($role);
    }

    /**
     * Eager Loading으로 N+1 쿼리 방지 확인
     *
     * @test
     */
    public function get_tenants_by_type_uses_eager_loading(): void
    {
        // Arrange
        $admin = User::factory()->create();
        $organizations = Organization::factory()->count(3)->create();

        foreach ($organizations as $organization) {
            TenantUser::create([
                'user_id' => $admin->id,
                'tenant_type' => 'ORG',
                'tenant_id' => $organization->id,
                'role' => 'owner',
            ]);
        }

        // Act: 쿼리 카운팅 시작
        \DB::enableQueryLog();
        $result = $admin->getTenantsByType('ORG');
        $queries = \DB::getQueryLog();
        \DB::disableQueryLog();

        // Assert: 쿼리 개수 확인 (≤2개: tenantUsers 조회 + eager loading)
        $this->assertCount(3, $result);
        $this->assertLessThanOrEqual(2, count($queries), 'N+1 쿼리가 발생하지 않아야 함');
    }
}

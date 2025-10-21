<?php

declare(strict_types=1);

// @TEST:RBAC-001-US1 | SPEC: SPEC-RBAC-001.md

namespace Tests\Feature\Tenancy;

use App\Models\Organization;
use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 사용자 스토리 1: Admin 멀티테넌트 접근 테스트
 *
 * Admin 사용자가 여러 Organization/Brand/Store에 동시 접근하고
 * 각 테넌트별로 다른 역할을 수행하는 시나리오 검증
 */
class TenantUserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Admin이 여러 Organization에 서로 다른 역할로 할당됨
     *
     * @test
     */
    public function admin_can_have_different_roles_in_multiple_organizations(): void
    {
        // Arrange: Admin 사용자 생성
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'user_type' => \App\Enums\UserType::USER,
        ]);

        // Organization 2개 생성
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

        // Act: getTenantsByType로 Organization 목록 조회
        $organizations = $admin->getTenantsByType('ORG');

        // Assert: 2개의 Organization이 반환됨
        $this->assertCount(2, $organizations);

        // Organization ID 확인
        $orgIds = $organizations->pluck('id')->sort()->values();
        $this->assertEquals([$org1->id, $org2->id], $orgIds->toArray());

        // 각 Organization의 이름 확인
        $orgNames = $organizations->pluck('name')->sort()->values();
        $this->assertEquals(['Org 1', 'Org 2'], $orgNames->toArray());
    }

    /**
     * getTenantsByType() 메서드 검증 - 타입별 필터링
     *
     * @test
     */
    public function get_tenants_by_type_filters_by_tenant_type(): void
    {
        // Arrange
        $admin = User::factory()->create();
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        // Admin을 2개 Organization에 할당
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
            'role' => 'manager',
        ]);

        // Act
        $organizations = $admin->getTenantsByType('ORG');
        $brands = $admin->getTenantsByType('BRD');
        $stores = $admin->getTenantsByType('STR');

        // Assert
        $this->assertCount(2, $organizations, 'ORG 타입은 2개 반환되어야 함');
        $this->assertCount(0, $brands, 'BRD 타입은 0개 반환되어야 함');
        $this->assertCount(0, $stores, 'STR 타입은 0개 반환되어야 함');
    }

    /**
     * hasRoleForTenant() 메서드 검증 - 역할 확인
     *
     * @test
     */
    public function has_role_for_tenant_checks_specific_role(): void
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

        // Act & Assert: Org1에서 owner 역할 확인
        $this->assertTrue($admin->hasRoleForTenant($org1, 'owner'));
        $this->assertFalse($admin->hasRoleForTenant($org1, 'manager'));
        $this->assertFalse($admin->hasRoleForTenant($org1, 'viewer'));

        // Act & Assert: Org2에서 viewer 역할 확인
        $this->assertTrue($admin->hasRoleForTenant($org2, 'viewer'));
        $this->assertFalse($admin->hasRoleForTenant($org2, 'owner'));
        $this->assertFalse($admin->hasRoleForTenant($org2, 'manager'));
    }

    /**
     * canManageTenant() 메서드 검증 - 관리 권한 확인
     *
     * @test
     */
    public function can_manage_tenant_returns_true_for_owner_and_manager(): void
    {
        // Arrange
        $admin = User::factory()->create();
        $org1 = Organization::factory()->create(['name' => 'Org 1']);
        $org2 = Organization::factory()->create(['name' => 'Org 2']);
        $org3 = Organization::factory()->create(['name' => 'Org 3']);

        // Admin을 Org1에 owner로, Org2에 manager로, Org3에 viewer로 할당
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
            'role' => 'manager',
        ]);

        TenantUser::create([
            'user_id' => $admin->id,
            'tenant_type' => 'ORG',
            'tenant_id' => $org3->id,
            'role' => 'viewer',
        ]);

        // Act & Assert
        $this->assertTrue($admin->canManageTenant($org1), 'Owner는 관리 가능해야 함');
        $this->assertTrue($admin->canManageTenant($org2), 'Manager는 관리 가능해야 함');
        $this->assertFalse($admin->canManageTenant($org3), 'Viewer는 관리 불가능해야 함');
    }

    /**
     * canViewTenant() 메서드 검증 - 조회 권한 확인
     *
     * @test
     */
    public function can_view_tenant_returns_true_for_all_roles(): void
    {
        // Arrange
        $admin = User::factory()->create();
        $org1 = Organization::factory()->create(['name' => 'Org 1']);
        $org2 = Organization::factory()->create(['name' => 'Org 2']);
        $org3 = Organization::factory()->create(['name' => 'Org 3']);
        $org4 = Organization::factory()->create(['name' => 'Org 4']);

        // Admin을 Org1~3에 각각 다른 역할로 할당
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
            'role' => 'manager',
        ]);

        TenantUser::create([
            'user_id' => $admin->id,
            'tenant_type' => 'ORG',
            'tenant_id' => $org3->id,
            'role' => 'viewer',
        ]);

        // Act & Assert: 모든 역할이 조회 가능
        $this->assertTrue($admin->canViewTenant($org1), 'Owner는 조회 가능해야 함');
        $this->assertTrue($admin->canViewTenant($org2), 'Manager는 조회 가능해야 함');
        $this->assertTrue($admin->canViewTenant($org3), 'Viewer는 조회 가능해야 함');

        // 역할이 없는 Organization은 조회 불가능
        $this->assertFalse($admin->canViewTenant($org4), '역할이 없으면 조회 불가능해야 함');
    }

    /**
     * 역할이 없는 테넌트에는 접근 불가
     *
     * @test
     */
    public function user_cannot_access_tenant_without_role(): void
    {
        // Arrange
        $admin = User::factory()->create();
        $org1 = Organization::factory()->create(['name' => 'Org 1']);
        $org2 = Organization::factory()->create(['name' => 'Org 2']);

        // Admin을 Org1에만 할당 (Org2는 미할당)
        TenantUser::create([
            'user_id' => $admin->id,
            'tenant_type' => 'ORG',
            'tenant_id' => $org1->id,
            'role' => 'owner',
        ]);

        // Act & Assert
        $this->assertTrue($admin->canViewTenant($org1), 'Org1은 접근 가능해야 함');
        $this->assertFalse($admin->canViewTenant($org2), 'Org2는 접근 불가능해야 함');
        $this->assertFalse($admin->canManageTenant($org2), 'Org2는 관리 불가능해야 함');
        $this->assertNull($admin->getRoleForTenant($org2), 'Org2의 역할은 null이어야 함');
    }
}

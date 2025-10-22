<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\TenantRole;
use App\Enums\UserType;
use App\Models\Brand;
use App\Models\Organization;
use App\Models\Store;
use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @TEST:RBAC-001-FOUNDATION | SPEC: SPEC-RBAC-001.md
 *
 * User 모델의 Tenant 관계 메서드 테스트
 * - getTenants() 메서드
 * - getRoleForTenant() 메서드
 * - hasRoleForTenant() 메서드
 * - canManageTenant() 메서드
 * - canViewTenant() 메서드
 */
class UserTenantRelationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * User가 여러 TenantUser 관계를 가짐
     */
    public function user_has_many_tenant_users(): void
    {
        $user = User::factory()->create(['user_type' => UserType::ADMIN]);
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        TenantUser::create([
            'user_id' => $user->id,
            'tenant_type' => 'ORG',
            'tenant_id' => $org1->id,
            'role' => TenantRole::OWNER->value,
        ]);

        TenantUser::create([
            'user_id' => $user->id,
            'tenant_type' => 'ORG',
            'tenant_id' => $org2->id,
            'role' => TenantRole::VIEWER->value,
        ]);

        $this->assertCount(2, $user->tenantUsers);
    }

    /**
     * @test
     * getTenantsByType() 메서드가 특정 타입의 테넌트만 반환
     */
    public function get_tenants_returns_only_specific_type(): void
    {
        $user = User::factory()->create(['user_type' => UserType::ADMIN]);
        $org = Organization::factory()->create();
        $brand = Brand::factory()->create(['organization_id' => $org->id]);
        $store = Store::factory()->create(['organization_id' => $org->id]);

        TenantUser::create([
            'user_id' => $user->id,
            'tenant_type' => 'ORG',
            'tenant_id' => $org->id,
            'role' => TenantRole::OWNER->value,
        ]);

        TenantUser::create([
            'user_id' => $user->id,
            'tenant_type' => 'BRD',
            'tenant_id' => $brand->id,
            'role' => TenantRole::MANAGER->value,
        ]);

        TenantUser::create([
            'user_id' => $user->id,
            'tenant_type' => 'STR',
            'tenant_id' => $store->id,
            'role' => TenantRole::VIEWER->value,
        ]);

        $orgs = $user->getTenantsByType('ORG');
        $brands = $user->getTenantsByType('BRD');
        $stores = $user->getTenantsByType('STR');

        $this->assertCount(1, $orgs);
        $this->assertCount(1, $brands);
        $this->assertCount(1, $stores);
        $this->assertInstanceOf(Organization::class, $orgs->first());
        $this->assertInstanceOf(Brand::class, $brands->first());
        $this->assertInstanceOf(Store::class, $stores->first());
    }

    /**
     * @test
     * getRoleForTenant() 메서드가 역할 반환
     */
    public function get_role_for_tenant_returns_role(): void
    {
        $user = User::factory()->create(['user_type' => UserType::ADMIN]);
        $org = Organization::factory()->create();

        TenantUser::create([
            'user_id' => $user->id,
            'tenant_type' => 'ORG',
            'tenant_id' => $org->id,
            'role' => TenantRole::MANAGER->value,
        ]);

        $role = $user->getRoleForTenant($org);

        $this->assertSame(TenantRole::MANAGER->value, $role);
    }

    /**
     * @test
     * hasRoleForTenant() 메서드가 역할 보유 여부 확인
     */
    public function has_role_for_tenant_checks_role(): void
    {
        $user = User::factory()->create(['user_type' => UserType::ADMIN]);
        $org = Organization::factory()->create();

        TenantUser::create([
            'user_id' => $user->id,
            'tenant_type' => 'ORG',
            'tenant_id' => $org->id,
            'role' => TenantRole::OWNER->value,
        ]);

        $this->assertTrue($user->hasRoleForTenant($org, TenantRole::OWNER->value));
        $this->assertFalse($user->hasRoleForTenant($org, TenantRole::MANAGER->value));
        $this->assertFalse($user->hasRoleForTenant($org, TenantRole::VIEWER->value));
    }

    /**
     * @test
     * canManageTenant() 메서드가 owner와 manager만 true 반환
     */
    public function can_manage_tenant_returns_true_for_owner_and_manager(): void
    {
        $user = User::factory()->create(['user_type' => UserType::ADMIN]);
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();
        $org3 = Organization::factory()->create();

        TenantUser::create([
            'user_id' => $user->id,
            'tenant_type' => 'ORG',
            'tenant_id' => $org1->id,
            'role' => TenantRole::OWNER->value,
        ]);

        TenantUser::create([
            'user_id' => $user->id,
            'tenant_type' => 'ORG',
            'tenant_id' => $org2->id,
            'role' => TenantRole::MANAGER->value,
        ]);

        TenantUser::create([
            'user_id' => $user->id,
            'tenant_type' => 'ORG',
            'tenant_id' => $org3->id,
            'role' => TenantRole::VIEWER->value,
        ]);

        $this->assertTrue($user->canManageTenant($org1));
        $this->assertTrue($user->canManageTenant($org2));
        $this->assertFalse($user->canManageTenant($org3));
    }

    /**
     * @test
     * canViewTenant() 메서드가 모든 역할에서 true 반환
     */
    public function can_view_tenant_returns_true_for_all_roles(): void
    {
        $user = User::factory()->create(['user_type' => UserType::ADMIN]);
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();
        $org3 = Organization::factory()->create();

        TenantUser::create([
            'user_id' => $user->id,
            'tenant_type' => 'ORG',
            'tenant_id' => $org1->id,
            'role' => TenantRole::OWNER->value,
        ]);

        TenantUser::create([
            'user_id' => $user->id,
            'tenant_type' => 'ORG',
            'tenant_id' => $org2->id,
            'role' => TenantRole::MANAGER->value,
        ]);

        TenantUser::create([
            'user_id' => $user->id,
            'tenant_type' => 'ORG',
            'tenant_id' => $org3->id,
            'role' => TenantRole::VIEWER->value,
        ]);

        $this->assertTrue($user->canViewTenant($org1));
        $this->assertTrue($user->canViewTenant($org2));
        $this->assertTrue($user->canViewTenant($org3));
    }

    /**
     * @test
     * 역할이 없는 테넌트에 대해 canManageTenant()와 canViewTenant()가 false 반환
     */
    public function permission_methods_return_false_for_non_member(): void
    {
        $user = User::factory()->create(['user_type' => UserType::ADMIN]);
        $org = Organization::factory()->create();

        // 역할 할당 없음

        $this->assertFalse($user->canManageTenant($org));
        $this->assertFalse($user->canViewTenant($org));
    }
}

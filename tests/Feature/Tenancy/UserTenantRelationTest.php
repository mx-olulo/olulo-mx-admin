<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

// @TEST:RBAC-001-FOUNDATION | SPEC: SPEC-RBAC-001.md

use App\Enums\UserType;
use App\Models\Organization;
use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * User 모델의 TenantUser 관계 및 권한 체크 메서드 테스트
 *
 * 테스트 대상:
 * - tenantUsers() HasMany 관계
 * - getTenants() 메서드 (특정 타입의 테넌트 컬렉션 반환)
 * - getRoleForTenant() 메서드 (특정 테넌트에서의 역할 조회)
 * - hasRoleForTenant() 메서드 (특정 역할 보유 여부)
 * - canManageTenant() 메서드 (owner/manager 권한 확인)
 * - canViewTenant() 메서드 (viewer 이상 권한 확인)
 * - hasGlobalRole() 메서드 (글로벌 역할 확인)
 * - isFirebaseUser() 메서드 (Firebase 사용자 여부 확인)
 */
class UserTenantRelationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * tenantUsers() 관계가 HasMany로 작동하는지 확인
     */
    public function test_user_has_many_tenant_users(): void
    {
        $user = User::factory()->create(['user_type' => UserType::USER]);
        $org = Organization::factory()->create();

        TenantUser::create([
            'user_id' => $user->id,
            'tenant_type' => 'ORG',
            'tenant_id' => $org->id,
            'role' => 'owner',
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $user->tenantUsers());
        $this->assertCount(1, $user->tenantUsers);
        $this->assertEquals('owner', $user->tenantUsers->first()->role);
    }

    /**
     * getTenantsByType() 메서드가 Organization 컬렉션을 반환하는지 확인
     */
    public function test_get_tenants_by_type_returns_organization_collection(): void
    {
        $user = User::factory()->create(['user_type' => UserType::USER]);
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        TenantUser::create([
            'user_id' => $user->id,
            'tenant_type' => 'ORG',
            'tenant_id' => $org1->id,
            'role' => 'owner',
        ]);

        TenantUser::create([
            'user_id' => $user->id,
            'tenant_type' => 'ORG',
            'tenant_id' => $org2->id,
            'role' => 'manager',
        ]);

        $tenants = $user->getTenantsByType('ORG');

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $tenants);
        $this->assertCount(2, $tenants);
        $this->assertTrue($tenants->contains(fn ($tenant): bool => $tenant->id === $org1->id));
        $this->assertTrue($tenants->contains(fn ($tenant): bool => $tenant->id === $org2->id));
    }

    /**
     * getRoleForTenant() 메서드가 역할 문자열을 반환하는지 확인
     */
    public function test_get_role_for_tenant_returns_role_string(): void
    {
        $user = User::factory()->create(['user_type' => UserType::USER]);
        $org = Organization::factory()->create();

        TenantUser::create([
            'user_id' => $user->id,
            'tenant_type' => 'ORG',
            'tenant_id' => $org->id,
            'role' => 'manager',
        ]);

        $role = $user->getRoleForTenant($org);

        $this->assertEquals('manager', $role);
    }

    /**
     * getRoleForTenant() 메서드가 역할이 없으면 null을 반환하는지 확인
     */
    public function test_get_role_for_tenant_returns_null_when_no_role(): void
    {
        $user = User::factory()->create(['user_type' => UserType::USER]);
        $org = Organization::factory()->create();

        $role = $user->getRoleForTenant($org);

        $this->assertNull($role);
    }

    /**
     * hasRoleForTenant() 메서드가 boolean을 반환하는지 확인
     */
    public function test_has_role_for_tenant_returns_boolean(): void
    {
        $user = User::factory()->create(['user_type' => UserType::USER]);
        $org = Organization::factory()->create();

        TenantUser::create([
            'user_id' => $user->id,
            'tenant_type' => 'ORG',
            'tenant_id' => $org->id,
            'role' => 'owner',
        ]);

        $this->assertTrue($user->hasRoleForTenant($org, 'owner'));
        $this->assertFalse($user->hasRoleForTenant($org, 'manager'));
    }

    /**
     * canManageTenant() 메서드가 owner/manager일 때만 true 반환하는지 확인
     */
    public function test_can_manage_tenant_returns_true_for_owner_and_manager(): void
    {
        $user = User::factory()->create(['user_type' => UserType::USER]);
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();
        $org3 = Organization::factory()->create();

        TenantUser::create([
            'user_id' => $user->id,
            'tenant_type' => 'ORG',
            'tenant_id' => $org1->id,
            'role' => 'owner',
        ]);

        TenantUser::create([
            'user_id' => $user->id,
            'tenant_type' => 'ORG',
            'tenant_id' => $org2->id,
            'role' => 'manager',
        ]);

        TenantUser::create([
            'user_id' => $user->id,
            'tenant_type' => 'ORG',
            'tenant_id' => $org3->id,
            'role' => 'viewer',
        ]);

        $this->assertTrue($user->canManageTenant($org1));
        $this->assertTrue($user->canManageTenant($org2));
        $this->assertFalse($user->canManageTenant($org3));
    }

    /**
     * canViewTenant() 메서드가 모든 역할에서 true 반환하는지 확인
     */
    public function test_can_view_tenant_returns_true_for_all_roles(): void
    {
        $user = User::factory()->create(['user_type' => UserType::USER]);
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();
        $org3 = Organization::factory()->create();

        TenantUser::create([
            'user_id' => $user->id,
            'tenant_type' => 'ORG',
            'tenant_id' => $org1->id,
            'role' => 'owner',
        ]);

        TenantUser::create([
            'user_id' => $user->id,
            'tenant_type' => 'ORG',
            'tenant_id' => $org2->id,
            'role' => 'manager',
        ]);

        TenantUser::create([
            'user_id' => $user->id,
            'tenant_type' => 'ORG',
            'tenant_id' => $org3->id,
            'role' => 'viewer',
        ]);

        $this->assertTrue($user->canViewTenant($org1));
        $this->assertTrue($user->canViewTenant($org2));
        $this->assertTrue($user->canViewTenant($org3));
    }

    /**
     * hasGlobalRole() 메서드가 User 타입에서만 작동하는지 확인
     */
    public function test_has_global_role_works_for_user_type(): void
    {
        $admin = User::factory()->create(['user_type' => UserType::USER, 'global_role' => 'platform_admin']);
        $customer = User::factory()->create(['user_type' => UserType::CUSTOMER, 'global_role' => null]);

        $this->assertTrue($admin->hasGlobalRole('platform_admin'));
        $this->assertFalse($admin->hasGlobalRole('system_admin'));
        $this->assertFalse($customer->hasGlobalRole('platform_admin'));
    }

    /**
     * isFirebaseUser() 메서드가 정상 작동하는지 확인
     */
    public function test_is_firebase_user_returns_correct_boolean(): void
    {
        $admin = User::factory()->create(['user_type' => UserType::USER]);
        $firebaseUser = User::factory()->create(['user_type' => UserType::CUSTOMER, 'firebase_uid' => 'test-uid']);

        $this->assertFalse($admin->isFirebaseUser());
        $this->assertTrue($firebaseUser->isFirebaseUser());
    }
}

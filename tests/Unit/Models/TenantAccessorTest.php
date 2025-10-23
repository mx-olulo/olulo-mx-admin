<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\TenantRole;
use App\Enums\UserType;
use App\Models\Concerns\TenantAccessor;
use App\Models\Organization;
use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @TEST:RBAC-001-FLUENT-API | SPEC: SPEC-RBAC-001.md
 *
 * TenantAccessor 메서드 체이닝 테스트
 */
class TenantAccessorTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * tenant() 메서드가 TenantAccessor를 반환함
     */
    public function tenant_method_returns_accessor(): void
    {
        $user = User::factory()->create(['user_type' => UserType::ADMIN]);
        $org = Organization::factory()->create();

        $tenantAccessor = $user->tenant($org);

        $this->assertInstanceOf(TenantAccessor::class, $tenantAccessor);
    }

    /**
     * @test
     * role() 메서드가 TenantRole Enum을 반환함
     */
    public function role_method_returns_enum(): void
    {
        $user = User::factory()->create(['user_type' => UserType::ADMIN]);
        $org = Organization::factory()->create();

        TenantUser::create([
            'user_id' => $user->id,
            'tenant_type' => 'ORG',
            'tenant_id' => $org->id,
            'role' => TenantRole::OWNER->value,
        ]);

        $role = $user->tenant($org)->role();

        $this->assertInstanceOf(TenantRole::class, $role);
        $this->assertSame(TenantRole::OWNER, $role);
    }

    /**
     * @test
     * hasRole() 메서드가 Enum으로 역할을 확인함
     */
    public function has_role_checks_with_enum(): void
    {
        $user = User::factory()->create(['user_type' => UserType::ADMIN]);
        $org = Organization::factory()->create();

        TenantUser::create([
            'user_id' => $user->id,
            'tenant_type' => 'ORG',
            'tenant_id' => $org->id,
            'role' => TenantRole::MANAGER->value,
        ]);

        $this->assertTrue($user->tenant($org)->hasRole(TenantRole::MANAGER));
        $this->assertFalse($user->tenant($org)->hasRole(TenantRole::OWNER));
    }

    /**
     * @test
     * 메서드 체이닝으로 canManage 확인
     */
    public function can_manage_via_chaining(): void
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

        $this->assertTrue($user->tenant($org1)->canManage());
        $this->assertFalse($user->tenant($org2)->canManage());
    }

    /**
     * @test
     * 메서드 체이닝으로 canView 확인
     */
    public function can_view_via_chaining(): void
    {
        $user = User::factory()->create(['user_type' => UserType::ADMIN]);
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        TenantUser::create([
            'user_id' => $user->id,
            'tenant_type' => 'ORG',
            'tenant_id' => $org1->id,
            'role' => TenantRole::VIEWER->value,
        ]);

        $this->assertTrue($user->tenant($org1)->canView());
        $this->assertFalse($user->tenant($org2)->canView());
    }

    /**
     * @test
     * 편의 메서드 (isOwner, isManager, isViewer) 테스트
     */
    public function convenience_methods_work(): void
    {
        $user = User::factory()->create(['user_type' => UserType::ADMIN]);
        $org = Organization::factory()->create();

        TenantUser::create([
            'user_id' => $user->id,
            'tenant_type' => 'ORG',
            'tenant_id' => $org->id,
            'role' => TenantRole::MANAGER->value,
        ]);

        $this->assertFalse($user->tenant($org)->isOwner());
        $this->assertTrue($user->tenant($org)->isManager());
        $this->assertFalse($user->tenant($org)->isViewer());
    }

    /**
     * @test
     * 역할이 없을 때 null 반환
     */
    public function role_returns_null_when_no_role(): void
    {
        $user = User::factory()->create(['user_type' => UserType::ADMIN]);
        $org = Organization::factory()->create();

        $role = $user->tenant($org)->role();

        $this->assertNull($role);
        $this->assertFalse($user->tenant($org)->canManage());
        $this->assertFalse($user->tenant($org)->canView());
    }
}

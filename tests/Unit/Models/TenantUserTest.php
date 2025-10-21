<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\TenantRole;
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
 * TenantUser 피벗 모델 테스트
 * - M:N 관계 검증
 * - Polymorphic 관계 검증
 * - UNIQUE 제약 검증
 */
class TenantUserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * TenantUser가 User에게 속함
     */
    public function it_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();

        $tenantUser = TenantUser::create([
            'user_id' => $user->id,
            'tenant_type' => 'ORG',
            'tenant_id' => $org->id,
            'role' => TenantRole::OWNER->value,
        ]);

        $this->assertInstanceOf(User::class, $tenantUser->user);
        $this->assertEquals($user->id, $tenantUser->user->id);
    }

    /**
     * @test
     * TenantUser가 Polymorphic 관계로 Tenant 모델에 연결됨
     */
    public function it_morph_to_tenant(): void
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();

        $tenantUser = TenantUser::create([
            'user_id' => $user->id,
            'tenant_type' => 'ORG',
            'tenant_id' => $org->id,
            'role' => TenantRole::MANAGER->value,
        ]);

        $this->assertInstanceOf(Organization::class, $tenantUser->tenant);
        $this->assertEquals($org->id, $tenantUser->tenant->id);
    }

    /**
     * @test
     * Organization, Brand, Store 모두 Tenant로 사용 가능
     */
    public function it_supports_all_tenant_types(): void
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        $brand = Brand::factory()->create(['organization_id' => $org->id]);
        $store = Store::factory()->create(['organization_id' => $org->id]);

        $orgTenant = TenantUser::create([
            'user_id' => $user->id,
            'tenant_type' => 'ORG',
            'tenant_id' => $org->id,
            'role' => TenantRole::OWNER->value,
        ]);

        $brandTenant = TenantUser::create([
            'user_id' => $user->id,
            'tenant_type' => 'BRD',
            'tenant_id' => $brand->id,
            'role' => TenantRole::MANAGER->value,
        ]);

        $storeTenant = TenantUser::create([
            'user_id' => $user->id,
            'tenant_type' => 'STR',
            'tenant_id' => $store->id,
            'role' => TenantRole::VIEWER->value,
        ]);

        $this->assertInstanceOf(Organization::class, $orgTenant->tenant);
        $this->assertInstanceOf(Brand::class, $brandTenant->tenant);
        $this->assertInstanceOf(Store::class, $storeTenant->tenant);
    }

    /**
     * @test
     * UNIQUE 제약 위반 시 예외 발생
     * (user_id, tenant_type, tenant_id) 조합이 고유해야 함
     */
    public function it_enforces_unique_constraint(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        $user = User::factory()->create();
        $org = Organization::factory()->create();

        TenantUser::create([
            'user_id' => $user->id,
            'tenant_type' => 'ORG',
            'tenant_id' => $org->id,
            'role' => TenantRole::OWNER->value,
        ]);

        // 동일한 user_id, tenant_type, tenant_id 조합으로 중복 생성 시도
        TenantUser::create([
            'user_id' => $user->id,
            'tenant_type' => 'ORG',
            'tenant_id' => $org->id,
            'role' => TenantRole::MANAGER->value, // 역할만 다름
        ]);
    }

    /**
     * @test
     * TenantUser가 Activity Log를 기록함
     */
    public function it_logs_activity(): void
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();

        $tenantUser = TenantUser::create([
            'user_id' => $user->id,
            'tenant_type' => 'ORG',
            'tenant_id' => $org->id,
            'role' => TenantRole::OWNER->value,
        ]);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => TenantUser::class,
            'subject_id' => $tenantUser->id,
            'event' => 'created',
        ]);
    }
}

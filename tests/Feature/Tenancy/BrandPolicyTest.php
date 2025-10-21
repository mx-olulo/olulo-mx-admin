<?php

declare(strict_types=1);

// @TEST:RBAC-001-US1 | SPEC: SPEC-RBAC-001.md

namespace Tests\Feature\Tenancy;

use App\Models\Brand;
use App\Models\Organization;
use App\Models\TenantUser;
use App\Models\User;
use App\Policies\BrandPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Brand Policy 테스트 - tenant_users 기반 권한 체크
 */
class BrandPolicyTest extends TestCase
{
    use RefreshDatabase;

    private BrandPolicy $brandPolicy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->brandPolicy = new BrandPolicy;
    }

    /**
     * Owner 역할은 Brand 조회 가능
     *
     * @test
     */
    public function owner_can_view_brand(): void
    {
        $admin = User::factory()->create();
        $org = Organization::factory()->create();
        $brand = Brand::factory()->create(['organization_id' => $org->id]);

        TenantUser::create([
            'user_id' => $admin->id,
            'tenant_type' => 'ORG',
            'tenant_id' => $org->id,
            'role' => 'owner',
        ]);

        $this->assertTrue($this->brandPolicy->view($admin, $brand), 'Owner는 Brand를 조회할 수 있어야 함');
    }

    /**
     * Manager 역할도 Brand 조회 가능
     *
     * @test
     */
    public function manager_can_view_brand(): void
    {
        $admin = User::factory()->create();
        $org = Organization::factory()->create();
        $brand = Brand::factory()->create(['organization_id' => $org->id]);

        TenantUser::create([
            'user_id' => $admin->id,
            'tenant_type' => 'ORG',
            'tenant_id' => $org->id,
            'role' => 'manager',
        ]);

        $this->assertTrue($this->brandPolicy->view($admin, $brand), 'Manager는 Brand를 조회할 수 있어야 함');
    }

    /**
     * Viewer 역할도 Brand 조회 가능
     *
     * @test
     */
    public function viewer_can_view_brand(): void
    {
        $admin = User::factory()->create();
        $org = Organization::factory()->create();
        $brand = Brand::factory()->create(['organization_id' => $org->id]);

        TenantUser::create([
            'user_id' => $admin->id,
            'tenant_type' => 'ORG',
            'tenant_id' => $org->id,
            'role' => 'viewer',
        ]);

        $this->assertTrue($this->brandPolicy->view($admin, $brand), 'Viewer는 Brand를 조회할 수 있어야 함');
    }

    /**
     * 역할이 없으면 Brand 조회 불가
     *
     * @test
     */
    public function user_without_role_cannot_view_brand(): void
    {
        $admin = User::factory()->create();
        $org = Organization::factory()->create();
        $brand = Brand::factory()->create(['organization_id' => $org->id]);

        // 역할 할당 없음

        $this->assertFalse($this->brandPolicy->view($admin, $brand), '역할이 없으면 Brand를 조회할 수 없어야 함');
    }

    /**
     * Owner와 Manager는 Brand 수정 가능
     *
     * @test
     */
    public function owner_and_manager_can_update_brand(): void
    {
        $owner = User::factory()->create();
        $manager = User::factory()->create();
        $org = Organization::factory()->create();
        $brand = Brand::factory()->create(['organization_id' => $org->id]);

        TenantUser::create([
            'user_id' => $owner->id,
            'tenant_type' => 'ORG',
            'tenant_id' => $org->id,
            'role' => 'owner',
        ]);

        TenantUser::create([
            'user_id' => $manager->id,
            'tenant_type' => 'ORG',
            'tenant_id' => $org->id,
            'role' => 'manager',
        ]);

        $this->assertTrue($this->brandPolicy->update($owner, $brand), 'Owner는 Brand를 수정할 수 있어야 함');
        $this->assertTrue($this->brandPolicy->update($manager, $brand), 'Manager는 Brand를 수정할 수 있어야 함');
    }

    /**
     * Viewer는 Brand 수정 불가
     *
     * @test
     */
    public function viewer_cannot_update_brand(): void
    {
        $viewer = User::factory()->create();
        $org = Organization::factory()->create();
        $brand = Brand::factory()->create(['organization_id' => $org->id]);

        TenantUser::create([
            'user_id' => $viewer->id,
            'tenant_type' => 'ORG',
            'tenant_id' => $org->id,
            'role' => 'viewer',
        ]);

        $this->assertFalse($this->brandPolicy->update($viewer, $brand), 'Viewer는 Brand를 수정할 수 없어야 함');
    }

    /**
     * Owner만 Brand 삭제 가능
     *
     * @test
     */
    public function only_owner_can_delete_brand(): void
    {
        $owner = User::factory()->create();
        $manager = User::factory()->create();
        $org = Organization::factory()->create();
        $brand = Brand::factory()->create([
            'organization_id' => $org->id,
            'relationship_type' => \App\Enums\RelationshipType::OWNED,
        ]);

        TenantUser::create([
            'user_id' => $owner->id,
            'tenant_type' => 'ORG',
            'tenant_id' => $org->id,
            'role' => 'owner',
        ]);

        TenantUser::create([
            'user_id' => $manager->id,
            'tenant_type' => 'ORG',
            'tenant_id' => $org->id,
            'role' => 'manager',
        ]);

        $this->assertTrue($this->brandPolicy->delete($owner, $brand), 'Owner는 Brand를 삭제할 수 있어야 함');
        $this->assertFalse($this->brandPolicy->delete($manager, $brand), 'Manager는 Brand를 삭제할 수 없어야 함');
    }
}

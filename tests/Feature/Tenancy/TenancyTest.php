<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Models\Brand;
use App\Models\Organization;
use App\Models\Role;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenancyTest extends TestCase
{
    use RefreshDatabase;

    protected User $orgUser;

    protected Organization $org;

    protected Brand $brand;

    protected Store $store;

    protected Role $orgRole;

    protected function setUp(): void
    {
        parent::setUp();

        // Create organization
        $this->org = Organization::create([
            'name' => 'Sample Organization 1',
            'description' => 'Test Organization',
            'contact_email' => 'org@example.com',
            'is_active' => true,
        ]);

        // Create brand
        $this->brand = Brand::create([
            'organization_id' => $this->org->id,
            'name' => 'Sample Brand 1-1',
            'description' => 'Test Brand',
            'is_active' => true,
        ]);

        // Create store
        $this->store = Store::create([
            'brand_id' => $this->brand->id,
            'name' => 'Sample Store 1-1-1',
            'description' => 'Test Store',
            'address' => 'Test Address',
            'phone' => '02-1234-5678',
            'is_active' => true,
        ]);

        // Create user
        $this->orgUser = User::create([
            'name' => 'Organization User',
            'email' => 'org@example.com',
            'password' => bcrypt('password'),
        ]);

        // Create organization role
        $this->orgRole = Role::create([
            'name' => 'org_manager',
            'guard_name' => 'web',
            'team_id' => $this->org->id,
            'scope_type' => 'ORG', // morphMap 사용
            'scope_ref_id' => $this->org->id,
        ]);

        // Assign role to user
        setPermissionsTeamId($this->orgRole->team_id);
        $this->orgUser->assignRole($this->orgRole);
    }

    public function test_organization_role_is_created_correctly(): void
    {
        $this->assertNotNull($this->org);
        $this->assertNotNull($this->orgRole);
        $this->assertEquals('ORG', $this->orgRole->scope_type);
        $this->assertEquals($this->org->id, $this->orgRole->scope_ref_id);
    }

    public function test_user_has_organization_role(): void
    {
        $this->assertTrue($this->orgUser->hasRole($this->orgRole));
    }

    public function test_user_can_access_tenant(): void
    {
        $this->assertTrue($this->orgUser->canAccessTenant($this->org));
    }

    public function test_user_cannot_access_tenant_without_role(): void
    {
        $otherOrg = Organization::create([
            'name' => 'Other Organization',
            'description' => 'No access',
            'contact_email' => 'other@example.com',
            'is_active' => true,
        ]);

        $this->assertFalse($this->orgUser->canAccessTenant($otherOrg));
    }

    public function test_get_tenants_returns_correct_organizations(): void
    {
        $panel = \Filament\Facades\Filament::getPanel('org');

        $tenants = $this->orgUser->getTenants($panel);

        $this->assertCount(1, $tenants);
        $this->assertInstanceOf(Organization::class, $tenants->first());
        $this->assertEquals('Sample Organization 1', $tenants->first()->name);
    }

    public function test_get_tenants_returns_empty_for_wrong_scope(): void
    {
        $brandPanel = \Filament\Facades\Filament::getPanel('brand');

        $tenants = $this->orgUser->getTenants($brandPanel);

        $this->assertCount(0, $tenants);
    }

    public function test_multiple_roles_for_user(): void
    {
        // Add brand role
        $brandRole = Role::create([
            'name' => 'brand_manager',
            'guard_name' => 'web',
            'team_id' => $this->brand->id,
            'scope_type' => 'BRAND', // morphMap 사용
            'scope_ref_id' => $this->brand->id,
        ]);

        setPermissionsTeamId($brandRole->team_id);
        $this->orgUser->assignRole($brandRole);

        // Check organization access
        $orgPanel = \Filament\Facades\Filament::getPanel('org');
        $orgTenants = $this->orgUser->getTenants($orgPanel);
        $this->assertCount(1, $orgTenants);
        $this->assertInstanceOf(Organization::class, $orgTenants->first());

        // Check brand access
        $brandPanel = \Filament\Facades\Filament::getPanel('brand');
        $brandTenants = $this->orgUser->getTenants($brandPanel);
        $this->assertCount(1, $brandTenants);
        $this->assertInstanceOf(Brand::class, $brandTenants->first());
    }

    public function test_tenant_isolation_between_users(): void
    {
        $user2 = User::create([
            'name' => 'Test User 2',
            'email' => 'test2@example.com',
            'password' => bcrypt('password'),
        ]);

        // User1 has access
        $this->assertTrue($this->orgUser->canAccessTenant($this->org));

        // User2 doesn't have access
        $this->assertFalse($user2->canAccessTenant($this->org));

        // User1 sees the organization
        $orgPanel = \Filament\Facades\Filament::getPanel('org');
        $user1Tenants = $this->orgUser->getTenants($orgPanel);
        $this->assertCount(1, $user1Tenants);

        // User2 doesn't see any organizations
        $user2Tenants = $user2->getTenants($orgPanel);
        $this->assertCount(0, $user2Tenants);
    }

    public function test_scopeable_relationship_works(): void
    {
        $this->assertNotNull($this->orgRole->scopeable);
        $this->assertInstanceOf(Organization::class, $this->orgRole->scopeable);
        $this->assertEquals($this->org->id, $this->orgRole->scopeable->id);
    }
}

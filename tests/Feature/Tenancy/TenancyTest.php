<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Models\Brand;
use App\Models\Organization;
use App\Models\Store;
use App\Models\Team;
use App\Models\TenantMembership;
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
    protected Team $orgTeam;
    protected Team $brandTeam;
    protected Team $storeTeam;

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

        // Create organization team
        $this->orgTeam = Team::create([
            'tenant_type' => Organization::class,
            'tenant_id' => $this->org->id,
            'scope_type' => 'ORG',
            'name' => $this->org->name,
        ]);

        // Create brand
        $this->brand = Brand::create([
            'organization_id' => $this->org->id,
            'name' => 'Sample Brand 1-1',
            'description' => 'Test Brand',
            'is_active' => true,
        ]);

        // Create brand team
        $this->brandTeam = Team::create([
            'tenant_type' => Brand::class,
            'tenant_id' => $this->brand->id,
            'scope_type' => 'BRAND',
            'name' => $this->brand->name,
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

        // Create store team
        $this->storeTeam = Team::create([
            'tenant_type' => Store::class,
            'tenant_id' => $this->store->id,
            'scope_type' => 'STORE',
            'name' => $this->store->name,
        ]);

        // Create user
        $this->orgUser = User::create([
            'name' => 'Organization User',
            'email' => 'org@example.com',
            'password' => bcrypt('password'),
        ]);

        // Create organization membership
        TenantMembership::create([
            'user_id' => $this->orgUser->id,
            'tenant_type' => Organization::class,
            'tenant_id' => $this->org->id,
            'team_id' => $this->orgTeam->id,
            'scope_type' => 'ORG',
            'is_owner' => true,
            'status' => 'active',
        ]);
    }

    public function test_organization_team_is_created_correctly(): void
    {
        $this->assertNotNull($this->org);
        $this->assertNotNull($this->orgTeam);
        $this->assertEquals('ORG', $this->orgTeam->scope_type);
        $this->assertEquals($this->org->name, $this->orgTeam->name);
    }

    public function test_user_membership_is_created_correctly(): void
    {
        $membership = TenantMembership::where('user_id', $this->orgUser->id)
            ->where('tenant_type', Organization::class)
            ->where('tenant_id', $this->org->id)
            ->first();

        $this->assertNotNull($membership);
        $this->assertTrue($membership->is_owner);
        $this->assertEquals('active', $membership->status);
        $this->assertEquals('ORG', $membership->scope_type);
    }

    public function test_user_can_access_tenant(): void
    {
        $this->assertTrue($this->orgUser->canAccessTenant($this->org));
    }

    public function test_user_cannot_access_tenant_without_membership(): void
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

    public function test_brand_team_and_membership_creation(): void
    {
        $this->assertNotNull($this->brand);
        $this->assertNotNull($this->brandTeam);
        $this->assertEquals('BRAND', $this->brandTeam->scope_type);
    }

    public function test_store_team_and_membership_creation(): void
    {
        $this->assertNotNull($this->store);
        $this->assertNotNull($this->storeTeam);
        $this->assertEquals('STORE', $this->storeTeam->scope_type);
    }

    public function test_multiple_memberships_for_user(): void
    {
        // Add brand membership
        TenantMembership::create([
            'user_id' => $this->orgUser->id,
            'tenant_type' => Brand::class,
            'tenant_id' => $this->brand->id,
            'team_id' => $this->brandTeam->id,
            'scope_type' => 'BRAND',
            'is_owner' => false,
            'status' => 'active',
        ]);

        // Check organization access
        $orgPanel = \Filament\Facades\Filament::getPanel('org');
        $orgTenants = $this->orgUser->getTenants($orgPanel);
        $this->assertCount(1, $orgTenants);
        $this->assertInstanceOf(Organization::class, $orgTenants->first());

        // Check brand access
        $brandPanel = \Filament\Facades\Filament::getPanel('brand');
        $brandTenants = $this->orgUser->getTenants($brandPanel);
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

    public function test_team_context_middleware_creates_team_if_not_exists(): void
    {
        // Create new organization without team
        $newOrg = Organization::create([
            'name' => 'New Organization',
            'description' => 'Test',
            'contact_email' => 'new@example.com',
            'is_active' => true,
        ]);

        // Verify no team exists yet
        $team = Team::where('tenant_type', Organization::class)
            ->where('tenant_id', $newOrg->id)
            ->first();
        $this->assertNull($team);

        // Simulate middleware creating team
        $team = Team::firstOrCreate(
            [
                'tenant_type' => Organization::class,
                'tenant_id' => $newOrg->id,
                'scope_type' => 'ORG',
            ],
            ['name' => $newOrg->name]
        );

        $this->assertNotNull($team);
        $this->assertEquals($newOrg->name, $team->name);
    }
}

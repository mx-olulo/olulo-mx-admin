<?php

declare(strict_types=1);

namespace Tests\Browser\Tenancy;

use App\Models\Brand;
use App\Models\Organization;
use App\Models\Store;
use App\Models\Team;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class TenancyIntegrationTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected User $multiTenantUser;
    protected Organization $org1;
    protected Organization $org2;
    protected Brand $brand;
    protected Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        // Create user with multiple tenant memberships
        $this->multiTenantUser = User::create([
            'name' => 'Multi-Tenant User',
            'email' => 'multi@example.com',
            'password' => bcrypt('password'),
        ]);

        // Create Organization 1
        $this->org1 = Organization::create([
            'name' => 'Organization One',
            'description' => 'First Organization',
            'contact_email' => 'org1@example.com',
            'is_active' => true,
        ]);

        $org1Team = Team::create([
            'tenant_type' => Organization::class,
            'tenant_id' => $this->org1->id,
            'scope_type' => 'ORG',
            'name' => $this->org1->name,
        ]);

        TenantMembership::create([
            'user_id' => $this->multiTenantUser->id,
            'tenant_type' => Organization::class,
            'tenant_id' => $this->org1->id,
            'team_id' => $org1Team->id,
            'scope_type' => 'ORG',
            'is_owner' => true,
            'status' => 'active',
        ]);

        // Create Organization 2
        $this->org2 = Organization::create([
            'name' => 'Organization Two',
            'description' => 'Second Organization',
            'contact_email' => 'org2@example.com',
            'is_active' => true,
        ]);

        $org2Team = Team::create([
            'tenant_type' => Organization::class,
            'tenant_id' => $this->org2->id,
            'scope_type' => 'ORG',
            'name' => $this->org2->name,
        ]);

        TenantMembership::create([
            'user_id' => $this->multiTenantUser->id,
            'tenant_type' => Organization::class,
            'tenant_id' => $this->org2->id,
            'team_id' => $org2Team->id,
            'scope_type' => 'ORG',
            'is_owner' => false,
            'status' => 'active',
        ]);

        // Create Brand
        $this->brand = Brand::create([
            'organization_id' => $this->org1->id,
            'name' => 'Test Brand',
            'description' => 'Test Brand',
            'is_active' => true,
        ]);

        $brandTeam = Team::create([
            'tenant_type' => Brand::class,
            'tenant_id' => $this->brand->id,
            'scope_type' => 'BRAND',
            'name' => $this->brand->name,
        ]);

        TenantMembership::create([
            'user_id' => $this->multiTenantUser->id,
            'tenant_type' => Brand::class,
            'tenant_id' => $this->brand->id,
            'team_id' => $brandTeam->id,
            'scope_type' => 'BRAND',
            'is_owner' => false,
            'status' => 'active',
        ]);

        // Create Store
        $this->store = Store::create([
            'brand_id' => $this->brand->id,
            'name' => 'Test Store',
            'description' => 'Test Store',
            'address' => 'Test Address',
            'phone' => '02-1234-5678',
            'is_active' => true,
        ]);

        $storeTeam = Team::create([
            'tenant_type' => Store::class,
            'tenant_id' => $this->store->id,
            'scope_type' => 'STORE',
            'name' => $this->store->name,
        ]);

        TenantMembership::create([
            'user_id' => $this->multiTenantUser->id,
            'tenant_type' => Store::class,
            'tenant_id' => $this->store->id,
            'team_id' => $storeTeam->id,
            'scope_type' => 'STORE',
            'is_owner' => false,
            'status' => 'active',
        ]);
    }

    /**
     * Test user can see all their organizations
     */
    public function test_user_sees_all_their_organizations(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->multiTenantUser)
                ->visit('/org')
                ->waitForText('Organization One', 10)
                ->assertSee('Organization One')
                ->assertSee('Organization Two')
                ->screenshot('multiple-organizations-list');
        });
    }

    /**
     * Test user can switch between organizations
     */
    public function test_user_can_switch_between_organizations(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->multiTenantUser)
                ->visit('/org')
                ->waitForText('Organization One', 10)
                ->click('@tenant-menu-item-' . $this->org1->id)
                ->waitForLocation('/org/' . $this->org1->id)
                ->assertPathIs('/org/' . $this->org1->id . '*')
                ->screenshot('org1-selected')
                ->pause(1000)
                // Switch to org2
                ->visit('/org')
                ->waitForText('Organization Two', 10)
                ->click('@tenant-menu-item-' . $this->org2->id)
                ->waitForLocation('/org/' . $this->org2->id)
                ->assertPathIs('/org/' . $this->org2->id . '*')
                ->screenshot('org2-selected');
        });
    }

    /**
     * Test user can access different panel types
     */
    public function test_user_can_access_different_panel_types(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->multiTenantUser)
                // Organization panel
                ->visit('/org')
                ->waitForText('Organization One', 10)
                ->assertSee('Organization One')
                ->screenshot('org-panel-access')
                // Brand panel
                ->visit('/brand')
                ->waitForText('Test Brand', 10)
                ->assertSee('Test Brand')
                ->screenshot('brand-panel-access')
                // Store panel
                ->visit('/store')
                ->waitForText('Test Store', 10)
                ->assertSee('Test Store')
                ->screenshot('store-panel-access');
        });
    }

    /**
     * Test tenant isolation - user only sees their own tenants
     */
    public function test_tenant_isolation(): void
    {
        // Create another organization that user doesn't have access to
        $otherOrg = Organization::create([
            'name' => 'Other Organization',
            'description' => 'Not accessible',
            'contact_email' => 'other@example.com',
            'is_active' => true,
        ]);

        $this->browse(function (Browser $browser) use ($otherOrg) {
            $browser->loginAs($this->multiTenantUser)
                ->visit('/org')
                ->waitForText('Organization One', 10)
                ->assertSee('Organization One')
                ->assertSee('Organization Two')
                ->assertDontSee('Other Organization')
                ->screenshot('tenant-isolation');
        });
    }

    /**
     * Test canAccessTenant method works correctly
     */
    public function test_can_access_tenant_validation(): void
    {
        $this->assertTrue($this->multiTenantUser->canAccessTenant($this->org1));
        $this->assertTrue($this->multiTenantUser->canAccessTenant($this->org2));
        $this->assertTrue($this->multiTenantUser->canAccessTenant($this->brand));
        $this->assertTrue($this->multiTenantUser->canAccessTenant($this->store));

        // Create organization without membership
        $noAccessOrg = Organization::create([
            'name' => 'No Access Org',
            'description' => 'No access',
            'contact_email' => 'noaccess@example.com',
            'is_active' => true,
        ]);

        $this->assertFalse($this->multiTenantUser->canAccessTenant($noAccessOrg));
    }

    /**
     * Test getTenants returns correct tenants per panel
     */
    public function test_get_tenants_per_panel(): void
    {
        $orgPanel = \Filament\Facades\Filament::getPanel('org');
        $brandPanel = \Filament\Facades\Filament::getPanel('brand');
        $storePanel = \Filament\Facades\Filament::getPanel('store');

        $orgTenants = $this->multiTenantUser->getTenants($orgPanel);
        $brandTenants = $this->multiTenantUser->getTenants($brandPanel);
        $storeTenants = $this->multiTenantUser->getTenants($storePanel);

        // Organization panel should return 2 organizations
        $this->assertCount(2, $orgTenants);
        $this->assertTrue($orgTenants->contains('id', $this->org1->id));
        $this->assertTrue($orgTenants->contains('id', $this->org2->id));

        // Brand panel should return 1 brand
        $this->assertCount(1, $brandTenants);
        $this->assertTrue($brandTenants->contains('id', $this->brand->id));

        // Store panel should return 1 store
        $this->assertCount(1, $storeTenants);
        $this->assertTrue($storeTenants->contains('id', $this->store->id));
    }
}

<?php

declare(strict_types=1);

namespace Tests\Browser\Tenancy;

use App\Models\Brand;
use App\Models\Organization;
use App\Models\Team;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class BrandTenancyTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected User $brandUser;

    protected Organization $organization;

    protected Brand $brand;

    protected Team $team;

    protected function setUp(): void
    {
        parent::setUp();

        // Create organization
        $this->organization = Organization::create([
            'name' => 'Test Organization',
            'description' => 'E2E Test Organization',
            'contact_email' => 'test@org.com',
            'is_active' => true,
        ]);

        // Create brand
        $this->brand = Brand::create([
            'organization_id' => $this->organization->id,
            'name' => 'Test Brand',
            'description' => 'E2E Test Brand',
            'is_active' => true,
        ]);

        // Create team for brand
        $this->team = Team::create([
            'tenant_type' => Brand::class,
            'tenant_id' => $this->brand->id,
            'scope_type' => 'BRAND',
            'name' => $this->brand->name,
        ]);

        // Create user
        $this->brandUser = User::create([
            'name' => 'Brand Test User',
            'email' => 'brandtest@example.com',
            'password' => bcrypt('password'),
        ]);

        // Create membership
        TenantMembership::create([
            'user_id' => $this->brandUser->id,
            'tenant_type' => Brand::class,
            'tenant_id' => $this->brand->id,
            'team_id' => $this->team->id,
            'scope_type' => 'BRAND',
            'is_owner' => true,
            'status' => 'active',
        ]);
    }

    /**
     * Test brand panel access with domain model tenancy
     */
    public function test_user_can_access_brand_panel_with_membership(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->loginAs($this->brandUser)
                ->visit('/brand')
                ->waitForText('Test Brand', 10)
                ->assertSee('Test Brand')
                ->screenshot('brand-tenant-selection');
        });
    }

    /**
     * Test brand tenant context is set correctly
     */
    public function test_brand_tenant_context_is_set(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->loginAs($this->brandUser)
                ->visit('/brand')
                ->waitForText('Test Brand', 10)
                ->click('@tenant-menu-item-' . $this->brand->id)
                ->waitForLocation('/brand/' . $this->brand->id)
                ->assertPathIs('/brand/' . $this->brand->id . '*')
                ->screenshot('brand-tenant-selected');
        });
    }

    /**
     * Test user without brand membership cannot access brand panel
     */
    public function test_user_without_membership_cannot_access_brand_panel(): void
    {
        $otherUser = User::create([
            'name' => 'Other User',
            'email' => 'other@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->browse(function (Browser $browser) use ($otherUser): void {
            $browser->loginAs($otherUser)
                ->visit('/brand')
                ->waitFor('body', 10)
                ->assertDontSee('Test Brand')
                ->screenshot('no-brand-access');
        });
    }
}

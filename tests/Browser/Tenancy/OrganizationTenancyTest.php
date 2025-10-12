<?php

declare(strict_types=1);

namespace Tests\Browser\Tenancy;

use App\Models\Organization;
use App\Models\Team;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class OrganizationTenancyTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected User $orgUser;
    protected Organization $organization;
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

        // Create team for organization
        $this->team = Team::create([
            'tenant_type' => Organization::class,
            'tenant_id' => $this->organization->id,
            'scope_type' => 'ORG',
            'name' => $this->organization->name,
        ]);

        // Create user
        $this->orgUser = User::create([
            'name' => 'Organization Test User',
            'email' => 'orgtest@example.com',
            'password' => bcrypt('password'),
        ]);

        // Create membership
        TenantMembership::create([
            'user_id' => $this->orgUser->id,
            'tenant_type' => Organization::class,
            'tenant_id' => $this->organization->id,
            'team_id' => $this->team->id,
            'scope_type' => 'ORG',
            'is_owner' => true,
            'status' => 'active',
        ]);
    }

    /**
     * Test organization panel access with domain model tenancy
     */
    public function test_user_can_access_organization_panel_with_membership(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->orgUser)
                ->visit('/org')
                ->waitForText('Test Organization', 10)
                ->assertSee('Test Organization')
                ->screenshot('organization-tenant-selection');
        });
    }

    /**
     * Test tenant context is set correctly
     */
    public function test_tenant_context_is_set_after_selection(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->orgUser)
                ->visit('/org')
                ->waitForText('Test Organization', 10)
                ->click('@tenant-menu-item-' . $this->organization->id)
                ->waitForLocation('/org/' . $this->organization->id)
                ->assertPathIs('/org/' . $this->organization->id . '*')
                ->screenshot('organization-tenant-selected');
        });
    }

    /**
     * Test user without membership cannot access organization
     */
    public function test_user_without_membership_cannot_access_organization_panel(): void
    {
        $otherUser = User::create([
            'name' => 'Other User',
            'email' => 'other@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->browse(function (Browser $browser) use ($otherUser) {
            $browser->loginAs($otherUser)
                ->visit('/org')
                ->waitFor('body', 10)
                ->assertDontSee('Test Organization')
                ->screenshot('no-organization-access');
        });
    }

    /**
     * Test getTenants returns correct organizations
     */
    public function test_get_tenants_returns_user_organizations(): void
    {
        // Create second organization
        $org2 = Organization::create([
            'name' => 'Second Organization',
            'description' => 'Second Test Org',
            'contact_email' => 'test2@org.com',
            'is_active' => true,
        ]);

        $team2 = Team::create([
            'tenant_type' => Organization::class,
            'tenant_id' => $org2->id,
            'scope_type' => 'ORG',
            'name' => $org2->name,
        ]);

        TenantMembership::create([
            'user_id' => $this->orgUser->id,
            'tenant_type' => Organization::class,
            'tenant_id' => $org2->id,
            'team_id' => $team2->id,
            'scope_type' => 'ORG',
            'is_owner' => false,
            'status' => 'active',
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->orgUser)
                ->visit('/org')
                ->waitForText('Test Organization', 10)
                ->assertSee('Test Organization')
                ->assertSee('Second Organization')
                ->screenshot('multiple-organizations');
        });
    }

    /**
     * Test Spatie team context is set correctly
     */
    public function test_spatie_team_context_is_set(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->orgUser)
                ->visit('/org')
                ->waitForText('Test Organization', 10)
                ->click('@tenant-menu-item-' . $this->organization->id)
                ->waitForLocation('/org/' . $this->organization->id)
                ->pause(500);

            // Verify team context by checking if permissions are scoped
            $teamId = getPermissionsTeamId();
            $this->assertEquals($this->team->id, $teamId);
        });
    }
}

<?php

declare(strict_types=1);

namespace Tests\Browser\Tenancy;

use App\Models\Store;
use App\Models\Team;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class StoreTenancyTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected User $storeUser;

    protected Store $store;

    protected Team $team;

    protected function setUp(): void
    {
        parent::setUp();

        // Create independent store
        $this->store = Store::create([
            'name' => 'Test Store',
            'description' => 'E2E Test Store',
            'address' => 'Test Address',
            'phone' => '02-1234-5678',
            'is_active' => true,
        ]);

        // Create team for store
        $this->team = Team::create([
            'tenant_type' => Store::class,
            'tenant_id' => $this->store->id,
            'scope_type' => 'STORE',
            'name' => $this->store->name,
        ]);

        // Create user
        $this->storeUser = User::create([
            'name' => 'Store Test User',
            'email' => 'storetest@example.com',
            'password' => bcrypt('password'),
        ]);

        // Create membership
        TenantMembership::create([
            'user_id' => $this->storeUser->id,
            'tenant_type' => Store::class,
            'tenant_id' => $this->store->id,
            'team_id' => $this->team->id,
            'scope_type' => 'STORE',
            'is_owner' => true,
            'status' => 'active',
        ]);
    }

    /**
     * Test store panel access with domain model tenancy
     */
    public function test_user_can_access_store_panel_with_membership(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->loginAs($this->storeUser)
                ->visit('/store')
                ->waitForText('Test Store', 10)
                ->assertSee('Test Store')
                ->screenshot('store-tenant-selection');
        });
    }

    /**
     * Test store tenant context is set correctly
     */
    public function test_store_tenant_context_is_set(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->loginAs($this->storeUser)
                ->visit('/store')
                ->waitForText('Test Store', 10)
                ->click('@tenant-menu-item-' . $this->store->id)
                ->waitForLocation('/store/' . $this->store->id)
                ->assertPathIs('/store/' . $this->store->id . '*')
                ->screenshot('store-tenant-selected');
        });
    }

    /**
     * Test user without store membership cannot access store panel
     */
    public function test_user_without_membership_cannot_access_store_panel(): void
    {
        $otherUser = User::create([
            'name' => 'Other User',
            'email' => 'other@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->browse(function (Browser $browser) use ($otherUser): void {
            $browser->loginAs($otherUser)
                ->visit('/store')
                ->waitFor('body', 10)
                ->assertDontSee('Test Store')
                ->screenshot('no-store-access');
        });
    }

    /**
     * Test store default panel
     */
    public function test_store_is_default_panel(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->loginAs($this->storeUser)
                ->visit('/')
                ->waitForLocation('/store')
                ->assertPathIs('/store*')
                ->screenshot('store-default-panel');
        });
    }
}

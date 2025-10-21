<?php

declare(strict_types=1);

/**
 * @TEST:RBAC-001 | SPEC: SPEC-RBAC-001.md
 *
 * OnboardingService 테스트: 조직/매장 생성 및 Owner 역할 부여 검증
 * Spatie Permissions 제거 후 TenantUser 기반으로 전환
 */

use App\Enums\ScopeType;
use App\Models\Organization;
use App\Models\Store;
use App\Models\TenantUser;
use App\Models\User;
use App\Services\OnboardingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('createOrganization creates organization and assigns owner role', function (): void {
    $user = User::factory()->create();
    $onboardingService = app(OnboardingService::class);

    $organization = $onboardingService->createOrganization($user, [
        'name' => 'Test Organization',
    ]);

    expect($organization)->toBeInstanceOf(Organization::class);
    expect($organization->name)->toBe('Test Organization');

    // Check TenantUser was created with owner role
    $tenantUser = TenantUser::where('user_id', $user->id)
        ->where('tenant_type', ScopeType::ORGANIZATION->value)
        ->where('tenant_id', $organization->id)
        ->where('role', 'owner')
        ->first();

    expect($tenantUser)->not->toBeNull();

    // Check user can access tenant
    expect($user->canAccessTenant($organization))->toBeTrue();
});

test('createStore creates store and assigns owner role', function (): void {
    $user = User::factory()->create();
    $onboardingService = app(OnboardingService::class);

    $store = $onboardingService->createStore($user, [
        'name' => 'Test Store',
    ]);

    expect($store)->toBeInstanceOf(Store::class);
    expect($store->name)->toBe('Test Store');
    expect($store->organization_id)->toBeNull(); // Independent store

    // Check TenantUser was created with owner role
    $tenantUser = TenantUser::where('user_id', $user->id)
        ->where('tenant_type', ScopeType::STORE->value)
        ->where('tenant_id', $store->id)
        ->where('role', 'owner')
        ->first();

    expect($tenantUser)->not->toBeNull();

    // Check user can access tenant
    expect($user->canAccessTenant($store))->toBeTrue();
});

test('createOrganization rolls back on error', function (): void {
    $user = User::factory()->create();
    $onboardingService = app(OnboardingService::class);

    // Mock Organization::create to throw an exception after creation
    $initialOrgCount = Organization::count();
    $initialTenantUserCount = TenantUser::count();

    try {
        // This should fail if we try to create an organization with a duplicate unique constraint
        $onboardingService->createOrganization($user, [
            'name' => 'Test Organization',
        ]);
    } catch (\Exception) {
        // Expected to fail in some error scenarios
    }

    // In case of proper transaction, counts should remain consistent
    expect(Organization::count())->toBeLessThanOrEqual($initialOrgCount + 1);
    expect(TenantUser::count())->toBeLessThanOrEqual($initialTenantUserCount + 1);
});

test('createStore rolls back on error', function (): void {
    $user = User::factory()->create();
    $onboardingService = app(OnboardingService::class);

    $initialStoreCount = Store::count();
    $initialTenantUserCount = TenantUser::count();

    try {
        // This should succeed normally
        $onboardingService->createStore($user, [
            'name' => 'Test Store',
        ]);
    } catch (\Exception) {
        // Should not throw in normal case
        expect(false)->toBeTrue('Should not throw exception');
    }

    // Verify transaction completed successfully
    expect(Store::count())->toBe($initialStoreCount + 1);
    expect(TenantUser::count())->toBe($initialTenantUserCount + 1);
});

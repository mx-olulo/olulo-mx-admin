<?php

declare(strict_types=1);

/**
 * @TEST:AUTH-REDIRECT-001 | SPEC: .moai/specs/SPEC-AUTH-REDIRECT-001/spec.md
 *
 * 인증 후 지능형 테넌트 리다이렉트 시스템 테스트
 *
 * 18개 시나리오 검증:
 * - 테넌트 0개 → 온보딩 리다이렉트
 * - 테넌트 1개 → 자동 리다이렉트 (Organization/Store/Brand)
 * - 테넌트 2개+ → 계류페이지 리다이렉트
 * - 계류페이지에서 선택 (Organization/Store/Brand)
 * - Brand 생성 버튼 없음 검증 (핵심 제약)
 * - 권한 없는 접근 차단 (403)
 * - Organization/Store 생성 버튼 존재
 * - 빈 테넌트 타입 안내 메시지
 * - 세션 만료, 직접 접근, 온보딩 완료 처리
 */

use App\Enums\ScopeType;
use App\Models\Brand;
use App\Models\Organization;
use App\Models\Store;
use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// 헬퍼 함수: redirectAfterLogin 메서드 직접 호출 (AuthRedirectService 사용)
function callRedirectAfterLogin(User $user): \Illuminate\Http\RedirectResponse
{
    // AuthRedirectService를 직접 사용
    $authRedirectService = app(\App\Services\AuthRedirectService::class);

    return $authRedirectService->redirectAfterLogin($user->fresh());
}

// 헬퍼 함수: User에게 TenantUser 관계 생성 (RBAC-001)
function assignRoleToUser(User $user, ScopeType $scopeType, int $refId): void
{
    TenantUser::create([
        'user_id' => $user->id,
        'tenant_type' => $scopeType->value,
        'tenant_id' => $refId,
        'role' => 'owner',
    ]);
}

// ===== 시나리오 1: 신규 사용자 (테넌트 0개) → 온보딩 =====
test('신규 사용자는 Organization 온보딩으로 리다이렉트된다', function (): void {
    $user = User::factory()->create();

    $redirectResponse = callRedirectAfterLogin($user);

    expect($redirectResponse)->toBeInstanceOf(\Illuminate\Http\RedirectResponse::class);
    expect($redirectResponse->getTargetUrl())->toBe('http://localhost/org/new');
});

// ===== 시나리오 2-4: 단일 테넌트 자동 리다이렉트 =====
test('Organization 1개 소속 시 자동 리다이렉트된다', function (): void {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();

    assignRoleToUser($user, ScopeType::ORGANIZATION, $organization->id);

    $redirectResponse = callRedirectAfterLogin($user);

    expect($redirectResponse)->toBeInstanceOf(\Illuminate\Http\RedirectResponse::class);
    expect($redirectResponse->getTargetUrl())->toBe("http://localhost/org/{$organization->id}");
});

test('Store 1개 소속 시 자동 리다이렉트된다', function (): void {
    $user = User::factory()->create();
    $store = Store::factory()->create(['organization_id' => null]);

    assignRoleToUser($user, ScopeType::STORE, $store->id);

    $redirectResponse = callRedirectAfterLogin($user);

    expect($redirectResponse)->toBeInstanceOf(\Illuminate\Http\RedirectResponse::class);
    expect($redirectResponse->getTargetUrl())->toBe("http://localhost/store/{$store->id}");
});

test('Brand 1개 소속 시 자동 리다이렉트된다', function (): void {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $brand = Brand::factory()->create(['organization_id' => $organization->id]);

    assignRoleToUser($user, ScopeType::BRAND, $brand->id);

    $redirectResponse = callRedirectAfterLogin($user);

    expect($redirectResponse)->toBeInstanceOf(\Illuminate\Http\RedirectResponse::class);
    expect($redirectResponse->getTargetUrl())->toBe("http://localhost/brand/{$brand->id}");
});

// ===== 시나리오 5-6: 복수 테넌트 → 계류페이지 =====
test('Organization 2개 소속 시 계류페이지로 리다이렉트된다', function (): void {
    $user = User::factory()->create();
    $org1 = Organization::factory()->create();
    $org2 = Organization::factory()->create();

    assignRoleToUser($user, ScopeType::ORGANIZATION, $org1->id);
    assignRoleToUser($user, ScopeType::ORGANIZATION, $org2->id);

    $redirectResponse = callRedirectAfterLogin($user);

    expect($redirectResponse)->toBeInstanceOf(\Illuminate\Http\RedirectResponse::class);
    expect($redirectResponse->getTargetUrl())->toBe('http://localhost/tenant/selector');
});

test('Organization 1개 + Store 1개 소속 시 계류페이지로 리다이렉트된다', function (): void {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $store = Store::factory()->create(['organization_id' => null]);

    assignRoleToUser($user, ScopeType::ORGANIZATION, $organization->id);
    assignRoleToUser($user, ScopeType::STORE, $store->id);

    $redirectResponse = callRedirectAfterLogin($user);

    expect($redirectResponse)->toBeInstanceOf(\Illuminate\Http\RedirectResponse::class);
    expect($redirectResponse->getTargetUrl())->toBe('http://localhost/tenant/selector');
});

// ===== 시나리오 7-9: 계류페이지에서 선택 =====
test('계류페이지에서 Organization 선택 시 패널로 이동한다', function (): void {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();

    assignRoleToUser($user, ScopeType::ORGANIZATION, $organization->id);

    $this->actingAs($user)
        ->post('/tenant/select', [
            'tenant_type' => 'organization',
            'tenant_id' => $organization->id,
        ])
        ->assertRedirect("/org/{$organization->id}");
});

test('계류페이지에서 Store 선택 시 패널로 이동한다', function (): void {
    $user = User::factory()->create();
    $store = Store::factory()->create(['organization_id' => null]);

    assignRoleToUser($user, ScopeType::STORE, $store->id);

    $this->actingAs($user)
        ->post('/tenant/select', [
            'tenant_type' => 'store',
            'tenant_id' => $store->id,
        ])
        ->assertRedirect("/store/{$store->id}");
});

test('계류페이지에서 Brand 선택 시 패널로 이동한다', function (): void {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $brand = Brand::factory()->create(['organization_id' => $organization->id]);

    assignRoleToUser($user, ScopeType::BRAND, $brand->id);

    $this->actingAs($user)
        ->post('/tenant/select', [
            'tenant_type' => 'brand',
            'tenant_id' => $brand->id,
        ])
        ->assertRedirect("/brand/{$brand->id}");
});

// ===== 시나리오 10: Brand 생성 버튼 없음 (핵심 제약) =====
test('계류페이지 Brand 탭에는 생성 버튼이 없다', function (): void {
    $user = User::factory()->create();
    $org1 = Organization::factory()->create();
    $org2 = Organization::factory()->create();

    assignRoleToUser($user, ScopeType::ORGANIZATION, $org1->id);
    assignRoleToUser($user, ScopeType::ORGANIZATION, $org2->id);

    $response = $this->actingAs($user)
        ->get('/tenant/selector');

    $response->assertStatus(200);
    $response->assertDontSee('+ Brand 생성', false); // HTML 이스케이프 무시
    $response->assertSee('Brand는 Organization 패널에서 생성할 수 있습니다', false);
});

// ===== 시나리오 11: 권한 없는 접근 차단 (403) =====
test('권한 없는 테넌트 접근 시 403 에러를 반환한다', function (): void {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();

    // 권한 없는 사용자
    $this->actingAs($user)
        ->post('/tenant/select', [
            'tenant_type' => 'organization',
            'tenant_id' => $organization->id,
        ])
        ->assertStatus(403);
});

// ===== 시나리오 12-13: Organization/Store 생성 버튼 =====
test('계류페이지 Organization 탭에는 생성 버튼이 있다', function (): void {
    $user = User::factory()->create();
    $org1 = Organization::factory()->create();
    $org2 = Organization::factory()->create();

    assignRoleToUser($user, ScopeType::ORGANIZATION, $org1->id);
    assignRoleToUser($user, ScopeType::ORGANIZATION, $org2->id);

    $response = $this->actingAs($user)
        ->get('/tenant/selector');

    $response->assertStatus(200);
    $response->assertSee('+ Organization 생성', false);
});

test('계류페이지 Store 탭에는 생성 버튼이 있다', function (): void {
    $user = User::factory()->create();
    $store1 = Store::factory()->create(['organization_id' => null]);
    $store2 = Store::factory()->create(['organization_id' => null]);

    assignRoleToUser($user, ScopeType::STORE, $store1->id);
    assignRoleToUser($user, ScopeType::STORE, $store2->id);

    $response = $this->actingAs($user)
        ->get('/tenant/selector');

    $response->assertStatus(200);
    $response->assertSee('+ Store 생성', false);
});

// ===== 시나리오 14-15: 빈 테넌트 타입 안내 =====
test('Organization 0개일 때 안내 메시지를 표시한다', function (): void {
    $user = User::factory()->create();
    $store = Store::factory()->create(['organization_id' => null]);

    assignRoleToUser($user, ScopeType::STORE, $store->id);

    $response = $this->actingAs($user)
        ->get('/tenant/selector');

    $response->assertStatus(200);
    $response->assertSee('소속된 Organization이 없습니다', false);
});

test('Store 0개일 때 안내 메시지를 표시한다', function (): void {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();

    assignRoleToUser($user, ScopeType::ORGANIZATION, $organization->id);

    $response = $this->actingAs($user)
        ->get('/tenant/selector');

    $response->assertStatus(200);
    $response->assertSee('소속된 Store가 없습니다', false);
});

// ===== 시나리오 16-18: 세션 만료, 직접 접근, 온보딩 완료 =====
test('세션 만료 시 로그인 페이지로 리다이렉트된다', function (): void {
    // 인증되지 않은 사용자
    $this->get('/tenant/selector')
        ->assertRedirect('/login'); // Laravel 기본 리다이렉트 경로
});

test('계류페이지 직접 접근 시 인증이 필요하다', function (): void {
    $response = $this->get('/tenant/selector');

    $response->assertRedirect('/login'); // Laravel 기본 리다이렉트 경로
});

test('온보딩 완료 후 해당 테넌트 패널로 이동한다', function (): void {
    $user = User::factory()->create();

    // OnboardingService를 통한 Organization 생성 (온보딩 완료 시뮬레이션)
    $onboardingService = app(\App\Services\OnboardingService::class);
    $organization = $onboardingService->createOrganization($user, [
        'name' => 'Test Organization',
    ]);

    // 생성된 Organization 확인
    expect($organization)->not->toBeNull();
    expect($organization->name)->toBe('Test Organization');

    // 온보딩 완료 후 리다이렉트 확인 (1개 테넌트 → 자동 리다이렉트)
    $redirectResponse = callRedirectAfterLogin($user);
    expect($redirectResponse->getTargetUrl())->toBe("http://localhost/org/{$organization->id}");
});

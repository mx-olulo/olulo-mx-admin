<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

// @TEST:RBAC-001-US3 | SPEC: SPEC-RBAC-001.md

use App\Enums\UserType;
use App\Models\User;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Customer Firebase 인증 유지 테스트
 *
 * US3: Customer의 Firebase 인증 흐름을 유지하되,
 * Admin/User 권한 모델과 독립적으로 작동
 */
class CustomerFirebaseAuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Customer가 Firebase UID로 식별됨
     */
    public function test_customer_is_firebase_user(): void
    {
        // Given: Firebase UID를 가진 Customer
        $customer = User::factory()->create([
            'user_type' => UserType::CUSTOMER,
            'firebase_uid' => 'firebase_test_uid_123',
        ]);

        // Then: isFirebaseUser()는 true
        $this->assertTrue($customer->isFirebaseUser());
    }

    /**
     * Customer가 Filament Admin 패널 접근 시 false 반환
     */
    public function test_customer_cannot_access_admin_panels(): void
    {
        // Given: Firebase Customer
        $customer = User::factory()->create([
            'user_type' => UserType::CUSTOMER,
            'firebase_uid' => 'firebase_test_uid_456',
        ]);

        // When/Then: 모든 패널 접근 불가
        $this->assertFalse($customer->canAccessPanel($this->createMockPanel('platform')));
        $this->assertFalse($customer->canAccessPanel($this->createMockPanel('system')));
        $this->assertFalse($customer->canAccessPanel($this->createMockPanel('org')));
        $this->assertFalse($customer->canAccessPanel($this->createMockPanel('brand')));
        $this->assertFalse($customer->canAccessPanel($this->createMockPanel('store')));
    }

    /**
     * Customer는 tenant_users 레코드 없음 확인
     */
    public function test_customer_has_no_tenant_users(): void
    {
        // Given: Firebase Customer
        $customer = User::factory()->create([
            'user_type' => UserType::CUSTOMER,
            'firebase_uid' => 'firebase_test_uid_789',
        ]);

        // Then: tenant_users 관계가 비어있음
        $this->assertCount(0, $customer->tenantUsers);
    }

    /**
     * isFirebaseUser() 메서드 검증 (firebase_uid 존재 시)
     */
    public function test_is_firebase_user_returns_true_when_firebase_uid_exists(): void
    {
        // Given: Firebase UID를 가진 사용자 (user_type 무관)
        $customerWithFirebase = User::factory()->create([
            'user_type' => UserType::CUSTOMER,
            'firebase_uid' => 'firebase_uid_abc',
        ]);

        $adminWithFirebase = User::factory()->create([
            'user_type' => UserType::ADMIN,
            'firebase_uid' => 'firebase_uid_def',
        ]);

        // Then: isFirebaseUser()는 firebase_uid 존재 여부만 확인
        $this->assertTrue($customerWithFirebase->isFirebaseUser());
        $this->assertTrue($adminWithFirebase->isFirebaseUser());
    }

    /**
     * isFirebaseUser() 메서드 검증 (firebase_uid 없을 시)
     */
    public function test_is_firebase_user_returns_false_when_no_firebase_uid(): void
    {
        // Given: Firebase UID가 없는 사용자
        $admin = User::factory()->create([
            'user_type' => UserType::ADMIN,
            'firebase_uid' => null,
        ]);

        $user = User::factory()->create([
            'user_type' => UserType::USER,
            'global_role' => 'platform_admin',
            'firebase_uid' => null,
        ]);

        // Then: isFirebaseUser()는 false
        $this->assertFalse($admin->isFirebaseUser());
        $this->assertFalse($user->isFirebaseUser());
    }

    /**
     * Customer는 hasGlobalRole() 호출 시 항상 false
     */
    public function test_customer_cannot_have_global_role(): void
    {
        // Given: Firebase Customer
        $customer = User::factory()->create([
            'user_type' => UserType::CUSTOMER,
            'firebase_uid' => 'firebase_uid_ghi',
            'global_role' => null, // Customer는 global_role이 없음
        ]);

        // Then: hasGlobalRole() 항상 false
        $this->assertFalse($customer->hasGlobalRole('platform_admin'));
        $this->assertFalse($customer->hasGlobalRole('system_admin'));
    }

    /**
     * Customer가 잘못된 global_role을 가지더라도 패널 접근 불가
     */
    public function test_customer_with_invalid_global_role_cannot_access_panels(): void
    {
        // Given: 잘못된 데이터 (Customer에 global_role 설정)
        $customer = User::factory()->create([
            'user_type' => UserType::CUSTOMER,
            'firebase_uid' => 'firebase_uid_jkl',
        ]);

        // 수동으로 global_role 설정 (데이터 무결성 위반 시나리오)
        $customer->update(['global_role' => 'platform_admin']);

        // Then: user_type이 CUSTOMER이므로 패널 접근 불가
        $this->assertFalse($customer->canAccessPanel($this->createMockPanel('platform')));
        $this->assertFalse($customer->canAccessPanel($this->createMockPanel('system')));
    }

    /**
     * Mock Panel 생성 헬퍼
     */
    private function createMockPanel(string $id): Panel
    {
        return new class($id) extends Panel
        {
            public function __construct(private readonly string $panelId) {}

            public function getId(): string
            {
                return $this->panelId;
            }
        };
    }
}

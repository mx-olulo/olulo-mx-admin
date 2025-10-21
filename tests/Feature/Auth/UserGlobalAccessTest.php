<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

// @TEST:RBAC-001-US2 | SPEC: SPEC-RBAC-001.md

use App\Enums\UserType;
use App\Models\User;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * User 글로벌 접근 제한 테스트
 *
 * US2: User 사용자는 Platform/System 패널만 접근 가능하며,
 * Organization/Brand/Store 패널 접근 차단
 */
class UserGlobalAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * User(platform_admin)가 Platform 패널 접근 가능
     */
    public function test_platform_admin_can_access_platform_panel(): void
    {
        // Given: platform_admin 역할을 가진 User 타입 사용자
        $user = User::factory()->create([
            'user_type' => UserType::USER,
            'global_role' => 'platform_admin',
        ]);

        // When: Platform 패널 접근 시도
        $panel = $this->createMockPanel('platform');

        // Then: 접근 가능
        $this->assertTrue($user->canAccessPanel($panel));
    }

    /**
     * User(system_admin)가 System 패널 접근 가능
     */
    public function test_system_admin_can_access_system_panel(): void
    {
        // Given: system_admin 역할을 가진 User 타입 사용자
        $user = User::factory()->create([
            'user_type' => UserType::USER,
            'global_role' => 'system_admin',
        ]);

        // When: System 패널 접근 시도
        $panel = $this->createMockPanel('system');

        // Then: 접근 가능
        $this->assertTrue($user->canAccessPanel($panel));
    }

    /**
     * User가 Organization 패널 접근 시 false 반환
     */
    public function test_user_cannot_access_organization_panel(): void
    {
        // Given: platform_admin 역할을 가진 User 타입 사용자
        $user = User::factory()->create([
            'user_type' => UserType::USER,
            'global_role' => 'platform_admin',
        ]);

        // When: Organization 패널 접근 시도
        $panel = $this->createMockPanel('org');

        // Then: 접근 불가
        $this->assertFalse($user->canAccessPanel($panel));
    }

    /**
     * User가 Brand 패널 접근 시 false 반환
     */
    public function test_user_cannot_access_brand_panel(): void
    {
        // Given: system_admin 역할을 가진 User 타입 사용자
        $user = User::factory()->create([
            'user_type' => UserType::USER,
            'global_role' => 'system_admin',
        ]);

        // When: Brand 패널 접근 시도
        $panel = $this->createMockPanel('brand');

        // Then: 접근 불가
        $this->assertFalse($user->canAccessPanel($panel));
    }

    /**
     * User가 Store 패널 접근 시 false 반환
     */
    public function test_user_cannot_access_store_panel(): void
    {
        // Given: platform_admin 역할을 가진 User 타입 사용자
        $user = User::factory()->create([
            'user_type' => UserType::USER,
            'global_role' => 'platform_admin',
        ]);

        // When: Store 패널 접근 시도
        $panel = $this->createMockPanel('store');

        // Then: 접근 불가
        $this->assertFalse($user->canAccessPanel($panel));
    }

    /**
     * hasGlobalRole() 메서드 검증 - platform_admin
     */
    public function test_has_global_role_platform_admin(): void
    {
        // Given: platform_admin 역할을 가진 User
        $user = User::factory()->create([
            'user_type' => UserType::USER,
            'global_role' => 'platform_admin',
        ]);

        // Then: hasGlobalRole('platform_admin')은 true
        $this->assertTrue($user->hasGlobalRole('platform_admin'));

        // Then: hasGlobalRole('system_admin')은 false
        $this->assertFalse($user->hasGlobalRole('system_admin'));
    }

    /**
     * hasGlobalRole() 메서드 검증 - system_admin
     */
    public function test_has_global_role_system_admin(): void
    {
        // Given: system_admin 역할을 가진 User
        $user = User::factory()->create([
            'user_type' => UserType::USER,
            'global_role' => 'system_admin',
        ]);

        // Then: hasGlobalRole('system_admin')은 true
        $this->assertTrue($user->hasGlobalRole('system_admin'));

        // Then: hasGlobalRole('platform_admin')은 false
        $this->assertFalse($user->hasGlobalRole('platform_admin'));
    }

    /**
     * Admin이 Platform 패널 접근 불가 (user_type 검증)
     */
    public function test_admin_cannot_access_platform_panel(): void
    {
        // Given: Admin 타입 사용자 (global_role 설정해도)
        $admin = User::factory()->create([
            'user_type' => UserType::ADMIN,
            'global_role' => 'platform_admin', // 무효함
        ]);

        // When: Platform 패널 접근 시도
        $panel = $this->createMockPanel('platform');

        // Then: 접근 불가 (user_type이 ADMIN이므로)
        $this->assertFalse($admin->canAccessPanel($panel));
    }

    /**
     * Admin이 System 패널 접근 불가 (user_type 검증)
     */
    public function test_admin_cannot_access_system_panel(): void
    {
        // Given: Admin 타입 사용자
        $admin = User::factory()->create([
            'user_type' => UserType::ADMIN,
            'global_role' => 'system_admin', // 무효함
        ]);

        // When: System 패널 접근 시도
        $panel = $this->createMockPanel('system');

        // Then: 접근 불가
        $this->assertFalse($admin->canAccessPanel($panel));
    }

    /**
     * Customer가 모든 패널 접근 불가
     */
    public function test_customer_cannot_access_any_panel(): void
    {
        // Given: Customer 타입 사용자
        $customer = User::factory()->create([
            'user_type' => UserType::CUSTOMER,
        ]);

        // When/Then: 모든 패널 접근 불가
        $this->assertFalse($customer->canAccessPanel($this->createMockPanel('platform')));
        $this->assertFalse($customer->canAccessPanel($this->createMockPanel('system')));
        $this->assertFalse($customer->canAccessPanel($this->createMockPanel('org')));
        $this->assertFalse($customer->canAccessPanel($this->createMockPanel('brand')));
        $this->assertFalse($customer->canAccessPanel($this->createMockPanel('store')));
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

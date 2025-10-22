<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\UserType;
use Tests\TestCase;

/**
 * @TEST:RBAC-001-FOUNDATION | SPEC: SPEC-RBAC-001.md
 *
 * UserType Enum 테스트
 * - 3티어 사용자 타입 구분: Admin, User, Customer
 * - 헬퍼 메서드 검증
 */
class UserTypeTest extends TestCase
{
    /**
     * @test
     * UserType Enum이 세 가지 값을 가져야 함
     */
    public function it_has_three_user_types(): void
    {
        $cases = UserType::cases();

        $this->assertCount(3, $cases);
        $this->assertTrue(in_array(UserType::ADMIN, $cases, true));
        $this->assertTrue(in_array(UserType::USER, $cases, true));
        $this->assertTrue(in_array(UserType::CUSTOMER, $cases, true));
    }

    /**
     * @test
     * UserType::ADMIN isAdmin() 메서드가 true 반환
     */
    public function admin_type_returns_true_for_is_admin(): void
    {
        $this->assertTrue(UserType::ADMIN->isAdmin());
        $this->assertFalse(UserType::USER->isAdmin());
        $this->assertFalse(UserType::CUSTOMER->isAdmin());
    }

    /**
     * @test
     * UserType::USER isUser() 메서드가 true 반환
     */
    public function user_type_returns_true_for_is_user(): void
    {
        $this->assertFalse(UserType::ADMIN->isUser());
        $this->assertTrue(UserType::USER->isUser());
        $this->assertFalse(UserType::CUSTOMER->isUser());
    }

    /**
     * @test
     * UserType::CUSTOMER isCustomer() 메서드가 true 반환
     */
    public function customer_type_returns_true_for_is_customer(): void
    {
        $this->assertFalse(UserType::ADMIN->isCustomer());
        $this->assertFalse(UserType::USER->isCustomer());
        $this->assertTrue(UserType::CUSTOMER->isCustomer());
    }

    /**
     * @test
     * UserType value 값이 올바른 문자열 반환
     */
    public function it_returns_correct_values(): void
    {
        $this->assertSame('admin', UserType::ADMIN->value);
        $this->assertSame('user', UserType::USER->value);
        $this->assertSame('customer', UserType::CUSTOMER->value);
    }
}

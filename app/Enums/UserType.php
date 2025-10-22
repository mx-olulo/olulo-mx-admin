<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * @CODE:RBAC-001 | SPEC: SPEC-RBAC-001.md | TEST: tests/Unit/Enums/UserTypeTest.php
 *
 * 3티어 사용자 타입 Enum
 * - Admin: 멀티테넌트 접근 (Organization, Brand, Store)
 * - User: 글로벌 패널 접근 (Platform, System)
 * - Customer: Firebase 인증, Admin 패널 접근 불가
 */
enum UserType: string
{
    case ADMIN = 'admin';
    case USER = 'user';
    case CUSTOMER = 'customer';

    /**
     * Admin 타입 여부 확인
     */
    public function isAdmin(): bool
    {
        return $this === self::ADMIN;
    }

    /**
     * User 타입 여부 확인
     */
    public function isUser(): bool
    {
        return $this === self::USER;
    }

    /**
     * Customer 타입 여부 확인
     */
    public function isCustomer(): bool
    {
        return $this === self::CUSTOMER;
    }
}

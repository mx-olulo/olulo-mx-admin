<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\TenantRole;
use Tests\TestCase;

/**
 * @TEST:RBAC-001-FOUNDATION | SPEC: SPEC-RBAC-001.md
 *
 * TenantRole Enum 테스트
 * - 테넌트별 역할 구분: Owner, Manager, Viewer
 * - 권한 레벨 검증 메서드
 */
class TenantRoleTest extends TestCase
{
    /**
     * @test
     * TenantRole Enum이 세 가지 역할을 가져야 함
     */
    public function it_has_three_tenant_roles(): void
    {
        $cases = TenantRole::cases();

        $this->assertCount(3, $cases);
        $this->assertTrue(in_array(TenantRole::OWNER, $cases, true));
        $this->assertTrue(in_array(TenantRole::MANAGER, $cases, true));
        $this->assertTrue(in_array(TenantRole::VIEWER, $cases, true));
    }

    /**
     * @test
     * canManage() 메서드가 Owner와 Manager만 true 반환
     */
    public function can_manage_returns_true_for_owner_and_manager(): void
    {
        $this->assertTrue(TenantRole::OWNER->canManage());
        $this->assertTrue(TenantRole::MANAGER->canManage());
        $this->assertFalse(TenantRole::VIEWER->canManage());
    }

    /**
     * @test
     * canView() 메서드가 모든 역할에서 true 반환
     */
    public function can_view_returns_true_for_all_roles(): void
    {
        $this->assertTrue(TenantRole::OWNER->canView());
        $this->assertTrue(TenantRole::MANAGER->canView());
        $this->assertTrue(TenantRole::VIEWER->canView());
    }

    /**
     * @test
     * value 값이 올바른 문자열 반환
     */
    public function it_returns_correct_values(): void
    {
        $this->assertSame('owner', TenantRole::OWNER->value);
        $this->assertSame('manager', TenantRole::MANAGER->value);
        $this->assertSame('viewer', TenantRole::VIEWER->value);
    }
}

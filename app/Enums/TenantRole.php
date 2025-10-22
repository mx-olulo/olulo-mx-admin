<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * @CODE:RBAC-001 | SPEC: SPEC-RBAC-001.md | TEST: tests/Unit/Enums/TenantRoleTest.php
 *
 * 테넌트별 역할 Enum
 * - Owner: 모든 권한 (생성, 수정, 삭제, 조회)
 * - Manager: 관리 권한 (생성, 수정, 조회)
 * - Viewer: 읽기 전용 (조회만)
 */
enum TenantRole: string
{
    case OWNER = 'owner';
    case MANAGER = 'manager';
    case VIEWER = 'viewer';

    /**
     * 관리 권한 보유 여부 (Owner, Manager)
     */
    public function canManage(): bool
    {
        return match ($this) {
            self::OWNER, self::MANAGER => true,
            self::VIEWER => false,
        };
    }

    /**
     * 조회 권한 보유 여부 (모든 역할)
     */
    public function canView(): bool
    {
        return true;
    }
}

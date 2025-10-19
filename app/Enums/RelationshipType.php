<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * @CODE:BRAND-STORE-MGMT-001 | SPEC: SPEC-BRAND-STORE-MGMT-001.md
 *
 * Brand/Store와 상위 조직 간의 관계 유형
 *
 * - OWNED: 직영 (상위 조직이 직접 운영)
 * - TENANT: 입점 (독립 운영, 제한된 권한)
 */
enum RelationshipType: string
{
    case OWNED = 'owned';
    case TENANT = 'tenant';

    /**
     * 한국어 라벨 반환
     */
    public function label(): string
    {
        return match ($this) {
            self::OWNED => '직영',
            self::TENANT => '입점',
        };
    }

    /**
     * Filament Badge 색상 반환
     */
    public function color(): string
    {
        return match ($this) {
            self::OWNED => 'success',
            self::TENANT => 'warning',
        };
    }

    /**
     * 삭제 가능 여부 확인
     *
     * - OWNED: 삭제 가능 (상위 조직이 관리)
     * - TENANT: 삭제 불가 (본인만 삭제 가능)
     */
    public function isDeletable(): bool
    {
        return match ($this) {
            self::OWNED => true,
            self::TENANT => false,
        };
    }
}

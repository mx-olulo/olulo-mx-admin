<?php

namespace App\Enums;

use App\Models\Role;

enum PanelType: string
{
    case PLATFORM = 'platform';
    case SYSTEM = 'system';
    case ORGANIZATION = 'organization';
    case BRAND = 'brand';
    case STORE = 'store';

    /**
     * Panel ID에 해당하는 scope_type 반환
     */
    public function getScopeType(): string
    {
        return match ($this) {
            self::PLATFORM => Role::TYPE_PLATFORM,
            self::SYSTEM => Role::TYPE_SYSTEM,
            self::ORGANIZATION => Role::TYPE_ORG,
            self::BRAND => Role::TYPE_BRAND,
            self::STORE => Role::TYPE_STORE,
        };
    }

    /**
     * Panel ID로 PanelType 찾기
     */
    public static function fromPanelId(string $panelId): ?self
    {
        return self::tryFrom($panelId);
    }

    /**
     * 모든 Panel ID와 scope_type 매핑 반환
     */
    public static function getScopeTypeMap(): array
    {
        return [
            self::PLATFORM->value => Role::TYPE_PLATFORM,
            self::SYSTEM->value => Role::TYPE_SYSTEM,
            self::ORGANIZATION->value => Role::TYPE_ORG,
            self::BRAND->value => Role::TYPE_BRAND,
            self::STORE->value => Role::TYPE_STORE,
        ];
    }
}

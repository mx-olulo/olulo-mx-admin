<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * System role names in a single source of truth.
 */
enum UserRole: string
{
    case ADMIN = 'admin';
    case ORG_ADMIN = 'org_admin';
    case STORE_OWNER = 'store_owner';
    case STORE_MANAGER = 'store_manager';
    case STAFF = 'staff';
    case CUSTOMER = 'customer';

    /**
     * @return list<string>
     */
    public static function toArray(): array
    {
        return array_map(static fn (self $r): string => $r->value, self::cases());
    }

    /**
     * Roles allowed to access Filament panel.
     *
     * @return list<string>
     */
    public static function panelAccess(): array
    {
        return [
            self::ADMIN->value,
            self::ORG_ADMIN->value,
            self::STORE_OWNER->value,
            self::STORE_MANAGER->value,
            self::STAFF->value,
        ];
    }
}

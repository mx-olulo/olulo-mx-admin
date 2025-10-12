<?php

declare(strict_types=1);

namespace App\Filament\System\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

/**
 * System Admin Dashboard
 * 시스템 관리자 대시보드
 *
 * Dashboard page for system administrators.
 * Manage system settings and operational status.
 *
 * 시스템 관리자를 위한 대시보드 페이지입니다.
 * 시스템 설정 및 운영 현황을 확인할 수 있습니다.
 */
class Dashboard extends BaseDashboard
{
    /**
     * Navigation icon
     * Navigation 아이콘
     *
     * Uses gear icon symbolizing system settings
     * 시스템 설정을 상징하는 톱니바퀴 아이콘 사용
     */
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    /**
     * Navigation sort order
     * Navigation 정렬 순서
     *
     * Display dashboard at the top
     * Dashboard를 최상단에 표시
     */
    protected static ?int $navigationSort = -1;

    /**
     * Check dashboard access permission
     * Dashboard 접근 권한 확인
     *
     * Restrict access to System scope roles only
     * System scope의 Role만 접근 가능하도록 제한
     */
    public static function canAccess(): bool
    {
        $tenant = \Filament\Facades\Filament::getTenant();

        if (! $tenant instanceof \App\Models\Role) {
            return false;
        }

        // Verify SYSTEM scope
        // SYSTEM scope 검증
        return $tenant->scope_type === \App\Enums\ScopeType::SYSTEM->value;
    }

    /**
     * Customize page title
     * Page 제목 커스터마이징
     *
     * Returns localized title
     * 다국어 지원을 위한 제목 반환
     */
    public function getTitle(): string
    {
        return __('filament.system.dashboard.title');
    }

    /**
     * Page subheading
     * Page 부제목
     *
     * Subheading describing the main features of the dashboard
     * Dashboard의 주요 기능을 설명하는 부제목
     */
    public function getSubheading(): ?string
    {
        return __('filament.system.dashboard.subheading');
    }
}

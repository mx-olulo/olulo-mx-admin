<?php

declare(strict_types=1);

namespace App\Filament\Platform\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

/**
 * Platform Admin Dashboard
 * 플랫폼 관리자 대시보드
 *
 * Dashboard page for top-level platform administrators.
 * Displays key metrics and statistics for the entire system.
 *
 * 최상위 플랫폼 관리자를 위한 대시보드 페이지입니다.
 * 전체 시스템의 주요 메트릭과 통계를 확인할 수 있습니다.
 */
class Dashboard extends BaseDashboard
{
    /**
     * Navigation icon
     * Navigation 아이콘
     *
     * Uses high-rise building icon symbolizing the platform
     * 플랫폼을 상징하는 고층 빌딩 아이콘 사용
     */
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

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
     * Platform Panel has no tenant restrictions (admin-only)
     * Platform Panel은 테넌트 제한 없음 (관리자 전용)
     */
    public static function canAccess(): bool
    {
        // Global panel - no tenant restriction
        // 글로벌 패널 - 테넌트 제한 없음
        return true;
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
        return __('filament.platform.dashboard.title');
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
        return __('filament.platform.dashboard.subheading');
    }
}

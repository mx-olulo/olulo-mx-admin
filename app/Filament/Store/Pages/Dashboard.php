<?php

declare(strict_types=1);

namespace App\Filament\Store\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

/**
 * Store Admin Dashboard
 * 매장 관리자 대시보드
 *
 * Dashboard page for store administrators.
 * Track real-time orders, sales statistics, and inventory status.
 *
 * 매장 관리자를 위한 대시보드 페이지입니다.
 * 실시간 주문 현황, 매출 통계, 재고 상태 등을 확인할 수 있습니다.
 */
class Dashboard extends BaseDashboard
{
    /**
     * Navigation icon
     * Navigation 아이콘
     *
     * Uses shopping bag icon symbolizing the store
     * 매장을 상징하는 쇼핑백 아이콘 사용
     */
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';

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
     * Restrict access to Store scope roles only
     * Store scope의 Role만 접근 가능하도록 제한
     */
    public static function canAccess(): bool
    {
        $tenant = \Filament\Facades\Filament::getTenant();

        if (! $tenant instanceof \App\Models\Role) {
            return false;
        }

        // Verify STORE scope
        // STORE scope 검증
        return $tenant->scope_type === \App\Enums\ScopeType::STORE->value;
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
        return __('filament.store.dashboard.title');
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
        return __('filament.store.dashboard.subheading');
    }
}

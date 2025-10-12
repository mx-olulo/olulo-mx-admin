<?php

declare(strict_types=1);

namespace App\Filament\Brand\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

/**
 * 브랜드 관리자 대시보드
 *
 * 브랜드 관리자를 위한 대시보드 페이지입니다.
 * 브랜드 전체의 매출, 주문 현황 및 통계를 확인할 수 있습니다.
 */
class Dashboard extends BaseDashboard
{
    /**
     * Navigation 아이콘
     *
     * 브랜드를 상징하는 반짝임 아이콘 사용
     */
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-sparkles';

    /**
     * Navigation 정렬 순서
     *
     * Dashboard를 최상단에 표시
     */
    protected static ?int $navigationSort = -1;

    /**
     * Dashboard 접근 권한 확인
     *
     * Brand scope의 Role만 접근 가능하도록 제한
     */
    public static function canAccess(): bool
    {
        $tenant = \Filament\Facades\Filament::getTenant();

        if (! $tenant instanceof \App\Models\Role) {
            return false;
        }

        // BRAND scope 검증
        return $tenant->scope_type === \App\Enums\ScopeType::BRAND->value;
    }

    /**
     * Page 제목 커스터마이징
     *
     * 다국어 지원을 위한 제목 반환
     */
    public function getTitle(): string
    {
        return __('filament.brand.dashboard.title');
    }

    /**
     * Page 부제목
     *
     * Dashboard의 주요 기능을 설명하는 부제목
     */
    public function getSubheading(): ?string
    {
        return __('filament.brand.dashboard.subheading');
    }
}

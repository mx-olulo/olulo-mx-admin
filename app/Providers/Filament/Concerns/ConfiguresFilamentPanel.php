<?php

declare(strict_types=1);

namespace App\Providers\Filament\Concerns;

use App\Enums\ScopeType;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Filament Panel 공통 설정 Trait
 *
 * 모든 Panel Provider에서 공통으로 사용하는 설정을 제공합니다.
 * - 기본 패널 설정
 * - 미들웨어 체인 구성
 * - 인증 설정
 * - 테넌시 설정
 */
trait ConfiguresFilamentPanel
{
    /**
     * Panel에 공통 설정 적용
     *
     * @param  Panel  $panel  설정할 Panel 인스턴스
     * @param  ScopeType  $scopeType  Panel의 스코프 타입
     * @return Panel 설정이 적용된 Panel 인스턴스
     */
    protected function applyCommonConfiguration(
        Panel $panel,
        ScopeType $scopeType,
    ): Panel {
        return $panel
            ->id($scopeType->getPanelId())
            ->path($scopeType->getPanelId())
            ->loginRouteSlug('../auth/login') // Firebase 로그인 페이지로 리디렉션
            ->colors([
                'primary' => Color::Amber,
            ])
            ->widgets([AccountWidget::class, FilamentInfoWidget::class])
            ->middleware($this->getMiddleware())
            ->authMiddleware($this->getAuthMiddleware());
    }

    /**
     * 공통 미들웨어 체인 반환
     *
     * @return array<int, class-string> 미들웨어 클래스 배열
     */
    protected function getMiddleware(): array
    {
        return [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            \App\Http\Middleware\DebugAuthMiddleware::class, // DEBUG: 403 추적용 (세션 초기화 후)
            AuthenticateSession::class,
            ShareErrorsFromSession::class,
            VerifyCsrfToken::class,
            SubstituteBindings::class,
            DisableBladeIconComponents::class,
            DispatchServingFilamentEvent::class,
        ];
    }

    /**
     * 인증 미들웨어 반환
     *
     * @return array<int, class-string> 인증 미들웨어 클래스 배열
     */
    protected function getAuthMiddleware(): array
    {
        return [
            Authenticate::class,
        ];
    }
}

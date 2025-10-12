<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ScopeType;
use App\Providers\Filament\BrandPanelProvider;
use App\Providers\Filament\OrganizationPanelProvider;
use App\Providers\Filament\PlatformPanelProvider;
use App\Providers\Filament\StorePanelProvider;
use App\Providers\Filament\SystemPanelProvider;
use Filament\Panel;
use Tests\TestCase;

/**
 * Panel Provider 리팩토링 테스트
 *
 * ConfiguresFilamentPanel Trait 사용 후 각 Panel Provider가
 * 정상적으로 작동하는지 검증합니다.
 */
class PanelProviderRefactoringTest extends TestCase
{
    /**
     * 각 Panel Provider가 올바른 설정을 가지는지 테스트
     *
     * @dataProvider panelProviderDataProvider
     */
    public function test_panel_providers_configure_correctly(
        string $providerClass,
        ScopeType $scopeType,
        string $expectedResourceNamespace,
        bool $isDefault = false
    ): void {
        $provider = new $providerClass($this->app);
        $panel = Panel::make();

        $configuredPanel = $provider->panel($panel);

        // 기본 설정 검증
        $this->assertEquals($scopeType->getPanelId(), $configuredPanel->getId());
        $this->assertEquals($scopeType->getPanelId(), $configuredPanel->getPath());

        // Tenant 모델 클래스 검증 (getTenantModel 사용)
        $this->assertEquals(\App\Models\Role::class, $configuredPanel->getTenantModel());

        // 미들웨어 설정 검증
        $middleware = $configuredPanel->getMiddleware();
        $this->assertContains(\Illuminate\Cookie\Middleware\EncryptCookies::class, $middleware);
        $this->assertContains(\Illuminate\Session\Middleware\StartSession::class, $middleware);
        $this->assertContains(\Filament\Http\Middleware\DispatchServingFilamentEvent::class, $middleware);

        // 인증 미들웨어 검증
        $authMiddleware = $configuredPanel->getAuthMiddleware();
        $this->assertContains(\Filament\Http\Middleware\Authenticate::class, $authMiddleware);

        // 기본 패널 검증 (StorePanelProvider만 해당)
        if ($isDefault) {
            $this->assertTrue($configuredPanel->isDefault());
        }
    }

    /**
     * ConfiguresFilamentPanel Trait의 메서드들이 존재하는지 테스트
     */
    public function test_trait_methods_exist(): void
    {
        $provider = new StorePanelProvider($this->app);

        // Trait 메서드 존재 확인
        $this->assertTrue(method_exists($provider, 'applyCommonConfiguration'));
        $this->assertTrue(method_exists($provider, 'getMiddleware'));
        $this->assertTrue(method_exists($provider, 'getAuthMiddleware'));
    }

    /**
     * Panel Provider 데이터 프로바이더
     *
     * @return array<string, array{
     *     class-string,
     *     ScopeType,
     *     string,
     *     bool
     * }>
     */
    public static function panelProviderDataProvider(): array
    {
        return [
            'Platform Panel' => [
                PlatformPanelProvider::class,
                ScopeType::PLATFORM,
                'App\Filament\Platform',
                false,
            ],
            'System Panel' => [
                SystemPanelProvider::class,
                ScopeType::SYSTEM,
                'App\Filament\System',
                false,
            ],
            'Organization Panel' => [
                OrganizationPanelProvider::class,
                ScopeType::ORGANIZATION,
                'App\Filament\Organization',
                false,
            ],
            'Brand Panel' => [
                BrandPanelProvider::class,
                ScopeType::BRAND,
                'App\Filament\Brand',
                false,
            ],
            'Store Panel (Default)' => [
                StorePanelProvider::class,
                ScopeType::STORE,
                'App\Filament\Store',
                true,
            ],
        ];
    }

    /**
     * 공통 설정이 모든 Panel에 동일하게 적용되는지 테스트
     */
    public function test_common_configuration_is_consistent_across_panels(): void
    {
        $providers = [
            new PlatformPanelProvider($this->app),
            new SystemPanelProvider($this->app),
            new OrganizationPanelProvider($this->app),
            new BrandPanelProvider($this->app),
            new StorePanelProvider($this->app),
        ];

        $authMiddlewareSets = [];
        $tenantModels = [];

        foreach ($providers as $provider) {
            $panel = Panel::make();
            $configuredPanel = $provider->panel($panel);

            // 각 Panel의 고유 미들웨어는 제외하고, 공통 미들웨어만 검사
            $middleware = $configuredPanel->getMiddleware();
            // Panel ID 미들웨어를 제외한 공통 미들웨어 확인
            $commonMiddleware = array_filter($middleware, function ($m) {
                return ! str_starts_with($m, 'panel:');
            });

            // 공통 미들웨어 클래스들이 포함되어 있는지 확인
            $this->assertContains(\Illuminate\Cookie\Middleware\EncryptCookies::class, $commonMiddleware);
            $this->assertContains(\Illuminate\Session\Middleware\StartSession::class, $commonMiddleware);
            $this->assertContains(\Filament\Http\Middleware\DispatchServingFilamentEvent::class, $commonMiddleware);

            $authMiddlewareSets[] = $configuredPanel->getAuthMiddleware();
            $tenantModels[] = $configuredPanel->getTenantModel();
        }

        // 모든 Panel이 동일한 인증 미들웨어 세트를 가지는지 확인
        $firstAuthMiddleware = $authMiddlewareSets[0];
        foreach ($authMiddlewareSets as $authMiddleware) {
            $this->assertEquals($firstAuthMiddleware, $authMiddleware);
        }

        // 모든 Panel이 동일한 Tenant 모델을 사용하는지 확인
        $firstTenantModel = $tenantModels[0];
        foreach ($tenantModels as $tenantModel) {
            $this->assertEquals($firstTenantModel, $tenantModel);
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use App\Tenancy\TenantManager;
use App\Tenancy\TenantResolver;
use App\Tenancy\Middleware\TenantMiddleware;
use App\Tenancy\Middleware\TenantScopeMiddleware;

/**
 * 테넌시 서비스 프로바이더
 *
 * 멀티테넌트 기능을 위한 설정과 미들웨어를 관리
 */
class TenancyServiceProvider extends ServiceProvider
{
    /**
     * 서비스 등록
     */
    public function register(): void
    {
        // TenantManager 싱글톤 등록
        $this->app->singleton(TenantManager::class, function ($app) {
            return new TenantManager(
                $app['config'],
                $app['db'],
                $app['cache']
            );
        });

        // TenantResolver 싱글톤 등록
        $this->app->singleton(TenantResolver::class, function ($app) {
            return new TenantResolver(
                $app[TenantManager::class],
                $app['request']
            );
        });

        // 현재 테넌트를 컨테이너에 바인딩
        $this->app->bind('tenant', function ($app) {
            return $app[TenantResolver::class]->resolve();
        });
    }

    /**
     * 서비스 부팅
     */
    public function boot(): void
    {
        // 테넌시 설정 퍼블리시
        $this->publishes([
            __DIR__ . '/../../config/tenancy.php' => config_path('tenancy.php'),
        ], 'tenancy-config');

        // 테넌시 마이그레이션 퍼블리시
        $this->publishes([
            __DIR__ . '/../../database/migrations/tenancy' => database_path('migrations/tenancy'),
        ], 'tenancy-migrations');

        // 미들웨어 등록
        $this->registerMiddleware();

        // 라우트 매크로 등록
        $this->registerRouteMacros();

        // 테넌트별 설정 적용
        $this->configureTenant();
    }

    /**
     * 미들웨어 등록
     */
    private function registerMiddleware(): void
    {
        // 글로벌 미들웨어 그룹에 추가
        $router = $this->app['router'];

        // 테넌트 식별 미들웨어
        $router->aliasMiddleware('tenant', TenantMiddleware::class);

        // 테넌트 스코프 미들웨어 (Eloquent 쿼리 스코프)
        $router->aliasMiddleware('tenant.scope', TenantScopeMiddleware::class);

        // 웹 미들웨어 그룹에 자동 추가
        $router->pushMiddlewareToGroup('web', TenantMiddleware::class);
    }

    /**
     * 라우트 매크로 등록
     */
    private function registerRouteMacros(): void
    {
        // 테넌트 라우트 그룹 매크로
        Route::macro('tenant', function ($callback) {
            Route::middleware(['tenant', 'tenant.scope'])
                ->group($callback);
        });

        // 마스터 관리자 라우트 그룹 매크로
        Route::macro('master', function ($callback) {
            Route::domain(config('app.master_domain'))
                ->middleware(['auth', 'admin.master'])
                ->prefix('admin')
                ->group($callback);
        });
    }

    /**
     * 현재 테넌트 기반 설정 적용
     */
    private function configureTenant(): void
    {
        if ($this->app->runningInConsole()) {
            return;
        }

        $tenant = $this->app['tenant'] ?? null;

        if (!$tenant) {
            return;
        }

        // 테넌트별 데이터베이스 연결 설정
        if (config('tenancy.database_per_tenant')) {
            config([
                'database.connections.tenant' => array_merge(
                    config('database.connections.mysql'),
                    ['database' => 'tenant_' . $tenant->id]
                )
            ]);

            $this->app['db']->setDefaultConnection('tenant');
        }

        // 테넌트별 파일시스템 설정
        config([
            'filesystems.disks.tenant' => [
                'driver' => 'local',
                'root' => storage_path('app/tenants/' . $tenant->id),
                'url' => env('APP_URL') . '/storage/tenants/' . $tenant->id,
                'visibility' => 'public',
            ],
        ]);

        // 테넌트별 캐시 설정
        config([
            'cache.prefix' => 'tenant_' . $tenant->id . '_',
        ]);

        // 테넌트별 세션 설정
        config([
            'session.cookie' => 'olulo_session_' . $tenant->slug,
            'session.domain' => $tenant->domain,
        ]);
    }

    /**
     * 제공하는 서비스들
     *
     * @return array<string>
     */
    public function provides(): array
    {
        return [
            TenantManager::class,
            TenantResolver::class,
            'tenant',
        ];
    }
}
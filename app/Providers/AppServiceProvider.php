<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // ScopeContextService를 싱글톤으로 등록
        $this->app->singleton(\App\Services\ScopeContextService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 다형 관계 타입 매핑 (짧은 키 사용)
        // TODO: Organization, Brand, Store 모델 생성 후 활성화
        // Relation::morphMap([
        //     'ORG' => \App\Models\Organization::class,
        //     'BRAND' => \App\Models\Brand::class,
        //     'STORE' => \App\Models\Store::class,
        // ]);
    }
}

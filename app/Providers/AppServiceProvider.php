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
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 다형 관계 타입 매핑 (Role scopeable 관계용)
        Relation::morphMap([
            'PLATFORM' => \App\Models\Platform::class,
            'SYSTEM' => \App\Models\System::class,
            'ORG' => \App\Models\Organization::class,
            'BRAND' => \App\Models\Brand::class,
            'STORE' => \App\Models\Store::class,
        ]);
    }
}

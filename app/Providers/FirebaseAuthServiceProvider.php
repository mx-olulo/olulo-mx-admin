<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\FirebaseService;
use Illuminate\Support\ServiceProvider;

/**
 * Firebase 인증 서비스 프로바이더
 *
 * Firebase 인증 서비스를 Laravel DI 컨테이너에 등록
 */
class FirebaseAuthServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Firebase 통합 서비스를 싱글톤으로 등록
        $this->app->singleton(fn (): \App\Services\FirebaseService => new FirebaseService);
    }
}

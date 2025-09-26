<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Auth\Contracts\FirebaseAuthInterface;
use App\Services\Auth\FirebaseAuthService;
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
        // Firebase 인증 서비스를 싱글톤으로 등록
        $this->app->singleton(FirebaseAuthInterface::class, function () {
            return new FirebaseAuthService;
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Firebase 설정 기본값 설정
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/firebase.php',
            'services.firebase'
        );
    }
}

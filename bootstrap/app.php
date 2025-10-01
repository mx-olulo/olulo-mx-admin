<?php

declare(strict_types=1);

use App\Constants\RateLimit;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: function ($router) {
            // 고객 라우트 (Web)
            $router->middleware('web')
                ->group(base_path('routes/customer.php'));

            // 고객 API 라우트
            $router->middleware('api')
                ->prefix('api')
                ->group(base_path('routes/customer-api.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Inertia 미들웨어 (Web 그룹)
        $middleware->web(append: [
            \Inertia\Middleware\HandleInertiaRequests::class,
        ]);

        // API 미들웨어 그룹 설정
        $middleware->api(prepend: [
            Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        // CORS 미들웨어 글로벌 적용
        $middleware->append(\Illuminate\Http\Middleware\HandleCors::class);

        // Rate Limiting 설정 (인증 엔드포인트)
        $middleware->alias([
            'throttle.auth' => \Illuminate\Routing\Middleware\ThrottleRequests::class . ':' . RateLimit::authThrottle(),
        ]);

        // 보안 헤더 미들웨어
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        // Firebase 콜백은 CSRF 예외 처리 (fetch JSON 호출 때문)
        $middleware->validateCsrfTokens(except: [
            'auth/firebase/callback',
            'customer/auth/firebase/callback',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // API 미들웨어 그룹 설정
        $middleware->api(prepend: [
            Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        // 웹 미들웨어 그룹에 SetLocale 추가
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
        ]);

        // CORS 미들웨어 글로벌 적용
        $middleware->append(\Illuminate\Http\Middleware\HandleCors::class);

        // 로컬 개발 환경에서 Firebase 콜백은 CSRF 예외 처리 (fetch JSON 호출 때문)
        if (env('APP_ENV') === 'local') {
            $middleware->validateCsrfTokens(except: [
                'auth/firebase/callback',
            ]);
        }
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

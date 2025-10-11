<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Firebase + Sanctum SPA 인증 API 엔드포인트
| 서브도메인 간 세션 공유를 위한 설정
|
*/

// Sanctum SPA 인증을 위한 CSRF 쿠키 엔드포인트
// 이 라우트는 Laravel Sanctum에서 자동으로 제공되지만 명시적으로 문서화
// Route::get('/sanctum/csrf-cookie', ...); // Sanctum에서 자동 제공

// 관리자 인증 API 라우트 그룹 (Rate Limiting 적용)
Route::prefix('auth')->name('api.auth.')->middleware('throttle.auth')->group(function (): void {
    // Firebase 로그인 (게스트용) - 1분당 10회 제한
    Route::post('/firebase-login', [AuthController::class, 'apiFirebaseLogin'])->name('firebase.login');

    // 로그아웃 (인증된 사용자용)
    Route::middleware('auth:web')->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    });

    // 언어 변경 (인증 여부 무관)
    Route::post('/locale/{locale}', [AuthController::class, 'changeLocale'])->name('locale.change');
});

// 고객 API 라우트는 별도 파일로 분리: routes/customer-api.php
// bootstrap/app.php에서 로드됨

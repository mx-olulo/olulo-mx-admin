<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\FirebaseAuthController;
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

// 인증 관련 라우트 그룹
Route::prefix('auth')->name('auth.')->group(function () {
    // Firebase 인증
    Route::prefix('firebase')->name('firebase.')->group(function () {
        Route::post('/login', [FirebaseAuthController::class, 'login'])->name('login');
        Route::post('/verify', [FirebaseAuthController::class, 'verify'])->name('verify');
    });

    // 인증 필요 라우트
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', [FirebaseAuthController::class, 'user'])->name('user');
        Route::post('/logout', [FirebaseAuthController::class, 'logout'])->name('logout');
    });
});

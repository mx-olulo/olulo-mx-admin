<?php

declare(strict_types=1);

use App\Http\Controllers\Customer\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Customer API Routes
|--------------------------------------------------------------------------
|
| 고객앱 API 엔드포인트
| Firebase 인증 + Sanctum SPA 세션
|
*/

// 고객 인증 API
Route::prefix('customer/auth')->name('api.customer.auth.')->middleware('throttle.auth')->group(function (): void {
    // Firebase 로그인
    Route::post('/firebase/login', [AuthController::class, 'apiFirebaseLogin'])
        ->name('firebase.login');
});

// 보호된 고객 API (인증 필요)
Route::middleware('auth:web')->group(function (): void {
    // 현재 사용자 정보
    Route::get('/user', fn () => request()->user());

    // 고객 개인 영역 API (Placeholder)
    Route::prefix('my')->name('api.my.')->group(function (): void {
        Route::get('/orders', fn () => response()->json(['data' => []]))->name('orders');
    });
});

<?php

declare(strict_types=1);

use App\Http\Controllers\Customer\AuthController;
use App\Http\Controllers\Customer\HomeController;
use App\Http\Controllers\Customer\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Customer Routes
|--------------------------------------------------------------------------
|
| 고객앱 전용 라우트 (Inertia.js + React)
| 관리자 라우트와 완전 분리: /, /customer/*, /my/*
|
*/

// QR 진입점 (홈 페이지)
Route::get('/', [HomeController::class, 'index'])->name('customer.home');

// 고객 인증 (Firebase)
Route::prefix('customer/auth')->name('customer.auth.')->group(function (): void {
    // 로그인 페이지 (게스트만 접근)
    Route::middleware('guest')->group(function (): void {
        Route::get('/login', [AuthController::class, 'showLogin'])
            ->name('login');
    });

    // Firebase 콜백 (세션 확립)
    Route::post('/firebase/callback', [AuthController::class, 'firebaseCallback'])
        ->name('firebase.callback');

    // 로그아웃 (인증된 사용자만)
    Route::middleware('auth:web')->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout'])
            ->name('logout');
    });
});

// 법적 문서 페이지 (짧은 경로)
Route::get('/terms', fn () => \Inertia\Inertia::render('Customer/Legal/Terms')
    ->rootView('customer.app'))->name('terms');

Route::get('/privacy', fn () => \Inertia\Inertia::render('Customer/Legal/Privacy')
    ->rootView('customer.app'))->name('privacy');

// 고객 개인 영역 (인증 필요)
Route::middleware('auth:web')->prefix('my')->name('my.')->group(function (): void {
    Route::get('/orders', [ProfileController::class, 'orders'])
        ->name('orders');
});

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
| 관리자 라우트와 완전 분리: /customer/*, /my/*
|
*/

// 고객 인증 (Firebase)
Route::prefix('customer/auth')->name('customer.auth.')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])
        ->name('login');
    Route::post('/firebase/callback', [AuthController::class, 'firebaseCallback'])
        ->name('firebase.callback');
});

// 고객 개인 영역 (인증 필요)
Route::middleware('auth:web')->prefix('my')->name('my.')->group(function () {
    Route::get('/orders', [ProfileController::class, 'orders'])
        ->name('orders');
});

<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| 웹 애플리케이션 라우트 정의
| Firebase + Sanctum SPA 인증 기반의 멀티테넌트 관리자 인터페이스
|
*/

// 메인 페이지
Route::get('/', function () {
    return view('welcome');
});

// 로그인 라우트 별칭 (Laravel 기본 인증 호환성)
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');

// 홈 및 대시보드 라우트 (인증 후 리다이렉트용)
Route::middleware('auth:web')->group(function () {
    Route::get('/home', function () {
        return redirect('/admin');
    })->name('home');

    Route::get('/dashboard', function () {
        return redirect('/admin');
    })->name('dashboard');
});

// 인증 관련 라우트 그룹
Route::prefix('auth')->name('auth.')->group(function () {
    // 로그인 페이지 (게스트만 접근 가능)
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('/firebase/callback', [AuthController::class, 'firebaseCallback'])->name('firebase.callback');
    });

    // 로그아웃 (인증된 사용자만 접근 가능)
    Route::middleware('auth:web')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    });

    // 언어 변경 라우트는 제거됨: 요청 시 query string `?locale=xx`로 처리
});

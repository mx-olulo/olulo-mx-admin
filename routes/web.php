<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Http;
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

// 관리자 메인 페이지 (임시 - 추후 고객앱으로 전환)
Route::get('/', function () {
    return view('welcome');
});

// 로그인 라우트 별칭 (Laravel 기본 인증 호환성) - /auth/login으로 리다이렉트
Route::get('/login', function () {
    return redirect()->route('auth.login');
})->name('login');

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

// 고객 라우트는 별도 파일로 분리: routes/customer.php
// bootstrap/app.php에서 로드됨

// Firebase Auth Handler Proxy
// Laravel Cloud에서도 작동하도록 Laravel 라우트로 Firebase 프록시 구현
Route::any('__/auth/{path}', function (string $path) {
    $firebaseUrl = 'https://' . config('firebase.web.project_id') . '.firebaseapp.com/__/auth/' . $path;

    // Query string 전달
    $queryString = request()->getQueryString();
    if ($queryString) {
        $firebaseUrl .= '?' . $queryString;
    }

    try {
        $response = Http::withOptions([
            'verify' => true,
            'allow_redirects' => true,
        ])->withHeaders([
            'User-Agent' => request()->header('User-Agent'),
            'Accept' => request()->header('Accept', '*/*'),
            'Accept-Language' => request()->header('Accept-Language', 'en-US,en;q=0.9'),
            'Referer' => request()->header('Referer', ''),
        ])->send(
            request()->method(),
            $firebaseUrl,
            [
                'body' => request()->getContent(),
            ]
        );

        return response($response->body(), $response->status())
            ->withHeaders([
                'Content-Type' => $response->header('Content-Type') ?? 'text/html',
                'Cache-Control' => $response->header('Cache-Control') ?? 'no-cache',
                'Access-Control-Allow-Origin' => config('app.url'),
                'Access-Control-Allow-Credentials' => 'true',
            ]);
    } catch (\Exception $e) {
        \Log::error('Firebase auth proxy error', [
            'error' => $e->getMessage(),
            'url' => $firebaseUrl,
        ]);

        return response('Firebase Auth Proxy Error', 502);
    }
})->where('path', '.*');

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * 고객 인증 컨트롤러
 *
 * Firebase 인증 및 Sanctum 세션 관리
 */
class AuthController extends Controller
{
    /**
     * 고객 로그인 페이지 (Firebase UI)
     */
    public function showLogin(): Response
    {
        return Inertia::render('Customer/Auth/Login')
            ->rootView('customer.app');
    }

    /**
     * Firebase 콜백 처리 (세션 확립)
     */
    public function firebaseCallback(Request $request)
    {
        // TODO: Firebase ID Token 검증 및 세션 확립
        // Phase 2에서 구현
        return response()->json(['message' => 'Not implemented yet'], 501);
    }

    /**
     * Firebase 로그인 API
     */
    public function apiFirebaseLogin(Request $request)
    {
        // TODO: Firebase ID Token 검증 및 세션 확립
        // Phase 4에서 구현
        return response()->json(['message' => 'Not implemented yet'], 501);
    }

    /**
     * 로그아웃
     *
     * 세션을 종료하고 홈으로 리다이렉트합니다.
     */
    public function logout(Request $request)
    {
        // 세션 종료
        auth()->guard('web')->logout();

        // 세션 무효화 및 재생성
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // 홈으로 리다이렉트
        return redirect()->route('customer.home')
            ->with('message', '로그아웃되었습니다.');
    }
}

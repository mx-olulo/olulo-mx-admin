<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\Contracts\FirebaseAuthInterface;
use App\Services\Auth\Exceptions\FirebaseAuthException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Firebase 인증 컨트롤러
 *
 * Firebase ID 토큰을 검증하고 Laravel 세션을 생성
 */
class FirebaseAuthController extends Controller
{
    public function __construct(
        private FirebaseAuthInterface $firebaseAuth
    ) {}

    /**
     * Firebase ID 토큰으로 로그인
     */
    public function login(Request $request): Response|JsonResponse
    {
        // 요청 유효성 검증
        $request->validate([
            'id_token' => 'required|string',
        ]);

        try {
            // Firebase ID 토큰 검증
            $firebaseUser = $this->firebaseAuth->verifyIdToken($request->id_token);

            // 로컬 사용자와 동기화
            $user = $this->firebaseAuth->syncUser($firebaseUser);

            // Laravel 세션 생성 (Sanctum SPA 세션)
            Auth::login($user, $request->boolean('remember'));

            // 세션 재생성 (보안 강화)
            $request->session()->regenerate();

            Log::info('Firebase 로그인 성공', [
                'user_id' => $user->id,
                'firebase_uid' => $user->firebase_uid,
                'ip' => $request->ip(),
            ]);

            // 204 No Content 응답 (세션 쿠키는 자동 설정됨)
            return response()->noContent();
        } catch (FirebaseAuthException $e) {
            Log::warning('Firebase 로그인 실패', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 401);
        } catch (\Throwable $e) {
            Log::error('Firebase 로그인 중 예외 발생', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => '인증 처리 중 오류가 발생했습니다.',
            ], 500);
        }
    }

    /**
     * 현재 인증된 사용자 정보 조회
     */
    public function user(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'message' => '인증되지 않은 사용자입니다.',
            ], 401);
        }

        // 사용자 정보와 권한 반환
        return response()->json([
            'id' => $user->id,
            'firebase_uid' => $user->firebase_uid,
            'email' => $user->email,
            'name' => $user->name,
            'email_verified' => $user->hasVerifiedEmail(),
            'roles' => $user->getRoleNames ?? [],
            'permissions' => [],
        ]);
    }

    /**
     * 로그아웃
     */
    public function logout(Request $request): Response
    {
        // 현재 사용자 로그아웃
        Auth::guard('web')->logout();

        // 세션 무효화
        $request->session()->invalidate();

        // CSRF 토큰 재생성
        $request->session()->regenerateToken();

        Log::info('사용자 로그아웃', [
            'user_id' => Auth::id(),
            'ip' => $request->ip(),
        ]);

        return response()->noContent();
    }

    /**
     * Firebase ID 토큰 유효성 검증 (미들웨어 용도)
     */
    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'id_token' => 'required|string',
        ]);

        try {
            $firebaseUser = $this->firebaseAuth->verifyIdToken($request->id_token);

            return response()->json([
                'valid' => true,
                'uid' => $firebaseUser['uid'],
                'email' => $firebaseUser['email'],
            ]);
        } catch (FirebaseAuthException $e) {
            return response()->json([
                'valid' => false,
                'message' => $e->getMessage(),
            ], 401);
        }
    }
}

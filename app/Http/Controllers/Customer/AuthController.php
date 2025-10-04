<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Kreait\Firebase\Contract\Auth as FirebaseAuth;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;
use Kreait\Firebase\Factory;
use App\Enums\UserRole;

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
    public function firebaseCallback(Request $request): JsonResponse
    {
        // TODO: Firebase ID Token 검증 및 세션 확립
        // Phase 2에서 구현
        return response()->json(['message' => 'Not implemented yet'], 501);
    }

    /**
     * Firebase 로그인 API
     *
     * Firebase ID Token을 검증하고 Sanctum 세션을 확립합니다.
     *
     * @param  Request  $request  { idToken: string }
     * @return JsonResponse { success: bool, message: string, user?: array, error?: string }
     *
     * @throws ValidationException
     */
    public function apiFirebaseLogin(Request $request): JsonResponse
    {
        // 1. Request 유효성 검증
        $validated = $request->validate([
            'idToken' => 'required|string',
        ]);

        try {
            // 2. Firebase Admin SDK 초기화
            $auth = $this->initializeFirebaseAuth();

            // 3. ID Token 검증
            $verifiedIdToken = $auth->verifyIdToken($validated['idToken']);

            // 4. Firebase 사용자 정보 추출
            $uid = $verifiedIdToken->claims()->get('sub');
            $email = $verifiedIdToken->claims()->get('email');
            $name = $verifiedIdToken->claims()->get('name');
            $phoneNumber = $verifiedIdToken->claims()->get('phone_number');
            $picture = $verifiedIdToken->claims()->get('picture');
            $emailVerified = $verifiedIdToken->claims()->get('email_verified', false);

            // 5. 사용자 조회 또는 생성
            $user = $this->findOrCreateUser([
                'firebase_uid' => $uid,
                'email' => $email,
                'name' => $name,
                'phone_number' => $phoneNumber,
                'picture' => $picture,
                'email_verified' => $emailVerified,
            ]);

            // 6. Sanctum 세션 확립
            Auth::guard('web')->login($user);
            $request->session()->regenerate();

            // 7. 마지막 로그인 시간 업데이트
            $user->updateLastLoginAt();

            // 7-1. 기본 고객 역할 부여 (없을 경우)
            if (! $user->hasRole(UserRole::CUSTOMER->value)) {
                $user->assignRole(UserRole::CUSTOMER->value);
            }

            // 8. 성공 응답
            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone_number' => $user->phone_number,
                    'avatar_url' => $user->avatar_url,
                ],
            ], 200);
        } catch (FailedToVerifyToken $e) {
            // Firebase 토큰 검증 실패
            Log::warning('Firebase token verification failed', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Authentication failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Invalid or expired token',
            ], 401);
        } catch (\Exception $e) {
            // 기타 예외 처리
            Log::error('Firebase authentication error', [
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Authentication failed',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred during authentication',
            ], 500);
        }
    }

    /**
     * Firebase Auth 인스턴스 초기화
     *
     * @return FirebaseAuth Firebase Auth 인스턴스
     *
     * @throws \RuntimeException Firebase 설정이 올바르지 않은 경우
     */
    private function initializeFirebaseAuth(): FirebaseAuth
    {
        $projectId = config('firebase.project_id');
        $clientEmail = config('firebase.client_email');
        $privateKey = config('firebase.private_key');

        // 환경 변수 검증
        if (empty($projectId) || empty($clientEmail) || empty($privateKey)) {
            Log::error('Firebase configuration is incomplete', [
                'has_project_id' => ! empty($projectId),
                'has_client_email' => ! empty($clientEmail),
                'has_private_key' => ! empty($privateKey),
            ]);

            throw new \RuntimeException(
                'Authentication service is temporarily unavailable.'
            );
        }

        // Private key 포맷 정규화 (줄바꿈 처리)
        $privateKey = str_replace('\\n', "\n", $privateKey);

        // Firebase Factory 초기화
        $factory = (new Factory)->withServiceAccount([
            'type' => 'service_account',
            'project_id' => $projectId,
            'client_email' => $clientEmail,
            'private_key' => $privateKey,
        ]);

        return $factory->createAuth();
    }

    /**
     * Firebase 사용자 정보로 User 조회 또는 생성
     *
     * @param  array{firebase_uid: string, email: ?string, name: ?string, phone_number: ?string, picture: ?string, email_verified: bool}  $firebaseData  Firebase 사용자 데이터
     * @return User 조회되거나 생성된 사용자 인스턴스
     */
    private function findOrCreateUser(array $firebaseData): User
    {
        // Firebase UID로 기존 사용자 조회
        $user = User::findByFirebaseUid($firebaseData['firebase_uid']);

        if ($user) {
            // 기존 사용자 정보 업데이트
            $user->updateFromFirebase([
                'name' => $firebaseData['name'],
                'email' => $firebaseData['email'],
                'phone_number' => $firebaseData['phone_number'],
                'picture' => $firebaseData['picture'],
                'email_verified' => $firebaseData['email_verified'],
            ]);

            return $user;
        }

        // 신규 사용자 생성
        return User::create([
            'firebase_uid' => $firebaseData['firebase_uid'],
            'email' => $firebaseData['email'] ?? null,
            'name' => $firebaseData['name'] ?? 'User',
            'phone_number' => $firebaseData['phone_number'] ?? null,
            'firebase_phone' => $firebaseData['phone_number'] ?? null,
            'avatar_url' => $firebaseData['picture'] ?? null,
            'provider' => 'firebase',
            'email_verified_at' => $firebaseData['email_verified'] ? now() : null,
            'locale' => config('app.locale', 'es-MX'),
        ]);
    }

    /**
     * 로그아웃
     *
     * 세션을 종료하고 홈으로 리다이렉트합니다.
     */
    public function logout(Request $request): RedirectResponse
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

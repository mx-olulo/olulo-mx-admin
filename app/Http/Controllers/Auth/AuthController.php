<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\FirebaseService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;

/**
 * 인증 컨트롤러
 *
 * Firebase Authentication과 Laravel Sanctum을 연동한 인증 시스템을 담당합니다.
 * 멀티테넌트 환경에서 동일 루트 도메인 기반 세션 공유를 지원합니다.
 *
 * 주요 기능:
 * - FirebaseUI 로그인 페이지 제공
 * - Firebase ID Token 검증 및 Laravel 세션 생성
 * - API 엔드포인트용 Firebase 로그인
 * - 로그아웃 처리
 * - 다국어 지원 (언어 변경)
 */
class AuthController extends Controller
{
    /**
     * Firebase 서비스 인스턴스
     */
    public function __construct(
        private readonly FirebaseService $firebaseService
    ) {}

    /**
     * FirebaseUI 로그인 페이지 표시
     *
     * intended URL을 세션에 저장하고 다국어/테마를 지원하는
     * Firebase 로그인 페이지를 표시합니다.
     *
     * @param  Request  $request  HTTP 요청
     * @return View 로그인 페이지 뷰
     */
    public function showLogin(Request $request): View
    {
        // 1. intended URL이 쿼리 파라미터로 전달된 경우 (Filament 패널에서 리다이렉트된 경우)
        $intendedUrl = $request->input('intended');

        // 2. 쿼리 파라미터가 없으면 이전 URL 확인
        if (! $intendedUrl) {
            $previousUrl = url()->previous();
            /** @var string $appUrl */
            $appUrl = config('app.url');

            // 이전 URL이 우리 앱이고, 로그인 페이지가 아니며, 유효한 경로인 경우
            if ($previousUrl &&
                is_string($appUrl) &&
                str_starts_with($previousUrl, $appUrl) &&
                ! str_contains($previousUrl, '/auth/login') &&
                $previousUrl !== $appUrl &&
                $previousUrl !== $appUrl . '/') {
                $intendedUrl = parse_url($previousUrl, PHP_URL_PATH);
            }
        }

        // 3. intended URL이 있으면 세션에 저장 (로그인 후 리다이렉트용)
        if ($intendedUrl && is_string($intendedUrl)) {
            Session::put('auth.intended_url', $intendedUrl);
        }

        // 현재 locale 설정
        /** @var array<string> $availableLocales */
        $availableLocales = array_keys(config('app.available_locales', []));
        $locale = $request->query('locale')
            ?: ($availableLocales ? $request->getPreferredLanguage($availableLocales) : null)
            ?: config('app.locale', 'es-MX');

        // 애플리케이션 로케일 설정
        App::setLocale($locale);

        // 다크/라이트 모드 설정 (기본값: light)
        $theme = Session::get('theme', 'light');

        // Firebase 설정
        $firebaseConfig = [
            'apiKey' => config('services.firebase.web_api_key'),
            'authDomain' => config('services.firebase.project_id') . '.firebaseapp.com',
            'projectId' => config('services.firebase.project_id'),
        ];

        return view('auth.login', [
            'firebaseConfig' => $firebaseConfig,
            'locale' => $locale,
            'theme' => $theme,
            'supportedLocales' => $availableLocales,
            'callbackUrl' => route('auth.firebase.callback'),
        ]);
    }

    /**
     * Firebase ID Token 검증 및 Laravel 세션 생성
     *
     * Firebase에서 전송된 ID Token을 검증하고 Laravel 사용자를 생성/업데이트한 후
     * Sanctum SPA 세션을 확립합니다.
     *
     * @param  Request  $request  HTTP 요청 (idToken 포함)
     * @return RedirectResponse 로그인 후 리다이렉트
     *
     * @throws ValidationException Firebase 토큰 검증 실패 시
     */
    public function firebaseCallback(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'idToken' => ['required', 'string'],
        ]);

        $wantsJson = $request->expectsJson() || $request->isJson() || str_contains((string) $request->header('Accept'), 'application/json');

        try {
            // Firebase ID Token 검증
            if (config('app.env') === 'local') {
                // 로컬 환경에서는 에뮬레이터 토큰(서명 없음) 대비 관대한 검증 경로를 사용
                $firebaseUserData = $this->firebaseService->verifyIdTokenLenient($request->input('idToken'));
            } else {
                $firebaseUserData = $this->firebaseService->verifyIdToken($request->input('idToken'));
            }

            // Laravel 사용자 동기화 (생성/업데이트)
            $user = $this->firebaseService->syncFirebaseUserWithLaravel($firebaseUserData);

            // Laravel 세션 생성: Filament 패널 가드(web)와 일치시키기 위해 명시적으로 web 가드 사용
            Auth::guard('web')->login($user, true);

            // intended URL 또는 기본 경로로 리다이렉트
            // 기본값: /platform (Platform 패널, 존재하지 않는 /admin이 아님)
            $intendedUrl = Session::pull('auth.intended_url', '/platform');

            // fetch() 등 JSON을 원하는 호출에는 JSON 응답으로 처리
            if ($wantsJson) {
                return response()->json([
                    'success' => true,
                    'redirect' => $intendedUrl,
                ], 200);
            }

            /** @var RedirectResponse $redirect */
            $redirect = redirect($intendedUrl);

            return $redirect->with('auth.success', __('auth.login_success'));
        } catch (FailedToVerifyToken) {
            Log::warning('Firebase ID Token 검증 실패', [
                'ip' => $request->ip(),
            ]);

            if ($wantsJson) {
                return response()->json([
                    'success' => false,
                    'message' => __('auth.invalid_firebase_token'),
                    'errors' => [
                        'idToken' => [__('auth.invalid_firebase_token')],
                    ],
                ], 422);
            }

            throw ValidationException::withMessages([
                'idToken' => [__('auth.invalid_firebase_token')],
            ]);
        } catch (\Exception $e) {
            Log::error('Firebase 로그인 처리 중 오류 발생', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);

            if ($wantsJson) {
                return response()->json([
                    'success' => false,
                    'message' => __('auth.login_failed'),
                ], 500);
            }

            return redirect()->route('auth.login')->withErrors([__('auth.login_failed')]);
        }
    }

    /**
     * API 엔드포인트용 Firebase 로그인
     *
     * SPA 애플리케이션에서 사용하는 Firebase 로그인 API입니다.
     * JSON 응답을 반환하고 세션 토큰을 발급합니다.
     *
     * @param  Request  $request  HTTP 요청 (idToken 포함)
     * @return JsonResponse JSON 응답
     */
    public function apiFirebaseLogin(Request $request): JsonResponse
    {
        $request->validate([
            'idToken' => ['required', 'string'],
        ]);

        try {
            // Firebase ID Token 검증
            $firebaseUserData = $this->firebaseService->verifyIdToken($request->input('idToken'));

            // Laravel 사용자 동기화 (생성/업데이트)
            $user = $this->firebaseService->syncFirebaseUserWithLaravel($firebaseUserData);

            // Laravel 세션 생성 (Sanctum SPA)
            Auth::login($user, true);

            return response()->json([
                'success' => true,
                'message' => __('auth.login_success'),
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar_url' => $user->avatar_url,
                    'firebase_uid' => $user->firebase_uid,
                    'phone_number' => $user->phone_number,
                ],
            ], 200);
        } catch (FailedToVerifyToken) {
            Log::warning('Firebase API 토큰 검증 실패', [
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('auth.invalid_firebase_token'),
                'errors' => [
                    'idToken' => [__('auth.invalid_firebase_token')],
                ],
            ], 422);
        } catch (\Exception $e) {
            Log::error('Firebase API 로그인 처리 중 오류 발생', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('auth.login_failed'),
            ], 500);
        }
    }

    /**
     * 로그아웃 처리
     *
     * Laravel 세션을 종료하고 로그인 페이지로 리다이렉트합니다.
     * API 요청인 경우 JSON 응답을 반환합니다.
     *
     * @param  Request  $request  HTTP 요청
     * @return RedirectResponse|JsonResponse 리다이렉트 또는 JSON 응답
     */
    public function logout(Request $request): RedirectResponse|JsonResponse
    {
        // Laravel 세션 종료
        Auth::logout();

        // 세션 무효화 및 재생성
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // API 요청인 경우 JSON 응답
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('auth.logout_success'),
            ], 200);
        }

        // 웹 요청인 경우 로그인 페이지로 리다이렉트
        /** @var RedirectResponse $redirectResponse */
        $redirectResponse = redirect()->route('auth.login');

        return $redirectResponse->with('auth.success', __('auth.logout_success'));
    }

    /**
     * 언어 변경
     *
     * 세션에 locale을 저장하고 이전 페이지로 리다이렉트합니다.
     * 지원하는 언어: ko(한국어), en(영어), es-MX(멕시코 스페인어)
     *
     * @param  Request  $request  HTTP 요청
     * @param  string  $locale  언어 코드
     * @return RedirectResponse|JsonResponse 리다이렉트 또는 JSON 응답
     */
    public function changeLocale(Request $request, string $locale): RedirectResponse|JsonResponse
    {
        // 지원하는 언어 목록
        $supportedLocales = array_keys(config('app.available_locales', ['ko' => '한국어', 'en' => 'English', 'es-MX' => 'Español']));

        // 유효한 언어인지 확인
        if (! in_array($locale, $supportedLocales, true)) {
            $locale = config('app.locale', 'ko');
        }

        // 애플리케이션 로케일 설정
        App::setLocale($locale);

        // API 요청인 경우 JSON 응답
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('auth.locale_changed'),
                'locale' => $locale,
            ], 200);
        }

        // 웹 요청인 경우 이전 페이지로 리다이렉트
        /** @var RedirectResponse $redirectResponse */
        $redirectResponse = redirect()->back();

        return $redirectResponse->with('auth.success', __('auth.locale_changed'));
    }
}

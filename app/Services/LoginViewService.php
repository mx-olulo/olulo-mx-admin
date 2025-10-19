<?php

declare(strict_types=1);

/**
 * 로그인 뷰 준비 서비스
 *
 * TDD History:
 * - REFACTOR (2025-10-19): AuthController에서 뷰 준비 로직 분리 (LOC 제약 준수)
 *
 * 책임:
 * - intended URL 추출 및 세션 저장
 * - locale 설정 및 언어 감지
 * - Firebase 설정 준비
 * - 로그인 뷰 데이터 구성
 */

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class LoginViewService
{
    /**
     * intended URL 추출
     *
     * 쿼리 파라미터 또는 이전 URL에서 intended URL을 추출합니다.
     *
     * @param  Request  $request  HTTP 요청
     * @return string|null intended URL
     */
    public function getIntendedUrl(Request $request): ?string
    {
        // 1. 쿼리 파라미터로 전달된 경우 (Filament 패널에서 리다이렉트)
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

        // intended URL이 있으면 세션에 저장
        if ($intendedUrl && is_string($intendedUrl)) {
            Session::put('auth.intended_url', $intendedUrl);
        }

        return is_string($intendedUrl) ? $intendedUrl : null;
    }

    /**
     * locale 설정 추출
     *
     * 쿼리 파라미터, Accept-Language 헤더, 기본값 순서로 locale을 결정합니다.
     *
     * @param  Request  $request  HTTP 요청
     * @return string locale 코드
     */
    public function getLocale(Request $request): string
    {
        /** @var array<string> $availableLocales */
        $availableLocales = array_keys(config('app.available_locales', []));

        $locale = $request->query('locale')
            ?: ($availableLocales ? $request->getPreferredLanguage($availableLocales) : null)
            ?: config('app.locale', 'es-MX');

        // 애플리케이션 로케일 설정
        App::setLocale($locale);

        return $locale;
    }

    /**
     * Firebase 설정 준비
     *
     * @return array<string, string> Firebase 설정
     */
    public function getFirebaseConfig(): array
    {
        return [
            'apiKey' => config('services.firebase.web_api_key'),
            'authDomain' => config('services.firebase.project_id') . '.firebaseapp.com',
            'projectId' => config('services.firebase.project_id'),
        ];
    }

    /**
     * 로그인 뷰 데이터 구성
     *
     * 로그인 페이지 렌더링에 필요한 모든 데이터를 준비합니다.
     *
     * @param  Request  $request  HTTP 요청
     * @return array<string, mixed> 뷰 데이터
     */
    public function getViewData(Request $request): array
    {
        $this->getIntendedUrl($request);
        $locale = $this->getLocale($request);

        /** @var array<string> $availableLocales */
        $availableLocales = array_keys(config('app.available_locales', []));
        $theme = Session::get('theme', 'light');

        return [
            'firebaseConfig' => $this->getFirebaseConfig(),
            'locale' => $locale,
            'theme' => $theme,
            'supportedLocales' => $availableLocales,
            'callbackUrl' => route('auth.firebase.callback'),
        ];
    }
}

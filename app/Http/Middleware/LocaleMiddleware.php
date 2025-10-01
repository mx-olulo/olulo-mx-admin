<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 언어 설정 미들웨어
 *
 * 브라우저 Accept-Language 헤더 또는 세션/사용자 프로필 기반으로
 * 애플리케이션 언어를 자동 설정합니다.
 */
class LocaleMiddleware
{
    /**
     * 지원하는 언어 목록
     */
    private const AVAILABLE_LOCALES = ['ko', 'es-MX', 'es', 'en'];

    /**
     * 기본 언어
     */
    private const DEFAULT_LOCALE = 'es-MX';

    /**
     * 애플리케이션 언어 설정
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->determineLocale($request);

        app()->setLocale($locale);

        return $next($request);
    }

    /**
     * 요청에서 적절한 언어 결정
     */
    private function determineLocale(Request $request): string
    {
        // 1. 세션에서 확인
        if ($locale = session('locale')) {
            return $this->validateLocale($locale);
        }

        // 2. 인증된 사용자 설정
        if ($user = $request->user()) {
            if (isset($user->preferred_locale)) {
                return $this->validateLocale($user->preferred_locale);
            }
        }

        // 3. Accept-Language 헤더 파싱
        $acceptLanguage = $request->header('Accept-Language');
        if ($acceptLanguage) {
            $locale = $this->parseAcceptLanguage($acceptLanguage);
            if ($locale) {
                return $locale;
            }
        }

        // 4. 기본값
        return self::DEFAULT_LOCALE;
    }

    /**
     * Accept-Language 헤더 파싱
     */
    private function parseAcceptLanguage(string $header): ?string
    {
        $locales = [];

        // Accept-Language 헤더 형식: ko-KR,ko;q=0.9,en-US;q=0.8,en;q=0.7
        $parts = explode(',', $header);

        foreach ($parts as $part) {
            $locale = trim(explode(';', $part)[0]);

            // ko-KR → ko, es-MX → es-MX 변환
            if (str_contains($locale, '-')) {
                $locales[] = $locale; // 전체 (es-MX)
                $locales[] = explode('-', $locale)[0]; // 앞부분만 (es)
            } else {
                $locales[] = $locale;
            }
        }

        // 지원하는 언어 중 첫 번째 매칭
        foreach ($locales as $locale) {
            if (in_array($locale, self::AVAILABLE_LOCALES, true)) {
                return $locale;
            }
        }

        return null;
    }

    /**
     * 언어 유효성 검증
     */
    private function validateLocale(string $locale): string
    {
        return in_array($locale, self::AVAILABLE_LOCALES, true)
            ? $locale
            : self::DEFAULT_LOCALE;
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 보안 헤더 미들웨어
 *
 * XSS, Clickjacking, MIME-Type Sniffing 등의 공격을 방지하기 위한
 * 보안 관련 HTTP 헤더를 설정합니다.
 */
class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // CSP Nonce 생성 및 요청에 저장
        $nonce = base64_encode(random_bytes(16));
        $request->attributes->set('csp-nonce', $nonce);

        /** @var Response $response */
        $response = $next($request);

        // X-Frame-Options: Clickjacking 방지
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // X-Content-Type-Options: MIME-Type Sniffing 방지
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // X-XSS-Protection: XSS 필터 활성화 (레거시 브라우저 지원)
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Referrer-Policy: Referrer 정보 제어
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions-Policy: 브라우저 기능 제어
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(self)');

        // X-Powered-By: 정보 노출 방지 (PHP 버전 숨김)
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        // HTTPS 환경에서만 Strict-Transport-Security 헤더 설정
        if ($request->secure() && config('app.env') === 'production') {
            // HSTS: HTTPS 강제 (1년)
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        // Content-Security-Policy: 프로덕션 환경에서만 엄격한 정책 적용
        if (config('app.env') === 'production') {
            $csp = implode('; ', [
                "default-src 'self'",
                "script-src 'self' 'nonce-{$nonce}' https://www.gstatic.com https://www.googleapis.com",
                "style-src 'self' 'nonce-{$nonce}' https://fonts.googleapis.com",
                "font-src 'self' data: https://fonts.gstatic.com",
                "img-src 'self' data: https: blob:",
                "connect-src 'self' https://*.firebaseapp.com https://*.web.app https://*.olulo.com.mx",
                "frame-src 'self' https://*.firebaseapp.com",
                "object-src 'none'",
                "base-uri 'self'",
                "form-action 'self'",
                'upgrade-insecure-requests',
            ]);

            $response->headers->set('Content-Security-Policy', $csp);
        }

        return $response;
    }
}

<?php

declare(strict_types=1);

/**
 * Security Headers 테스트
 *
 * SecurityHeaders 미들웨어의 보안 헤더 적용을 검증합니다.
 * X-Frame-Options, CSP, HSTS 등 주요 보안 헤더를 확인합니다.
 */
describe('Basic Security Headers', function (): void {
    /**
     * 테스트: 기본 보안 헤더 존재 확인
     */
    test('includes basic security headers', function (): void {
        // Act: API 요청
        $response = $this->get('/');

        // Assert: 기본 보안 헤더 확인
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(self)');
    })->group('security', 'headers', 'basic');

    /**
     * 테스트: X-Powered-By 헤더 제거 확인
     */
    test('removes x powered by header', function (): void {
        // Act: API 요청
        $response = $this->get('/');

        // Assert: X-Powered-By 헤더가 없어야 함 (정보 노출 방지)
        expect($response->headers->has('X-Powered-By'))->toBeFalse('X-Powered-By header should be removed for security');
    })->group('security', 'headers', 'basic');
});

describe('Content Security Policy', function (): void {
    /**
     * 테스트: Content-Security-Policy 헤더는 production에서만 존재
     */
    test('includes content security policy in production', function (): void {
        // Arrange: production 환경
        config(['app.env' => 'production']);

        // Act: production 요청
        $response = $this->get('/');

        // Assert: CSP 헤더 확인
        $response->assertHeader('Content-Security-Policy');

        $csp = $response->headers->get('Content-Security-Policy');
        expect($csp)->toContain("default-src 'self'");
    })->group('security', 'headers', 'csp');

    /**
     * 테스트: CSP는 production에서 더 엄격
     */
    test('csp stricter in production', function (): void {
        // Arrange: production 환경
        config(['app.env' => 'production']);

        // Act: production 요청
        $response = $this->get('/');

        // Assert: CSP 정책 확인
        $csp = $response->headers->get('Content-Security-Policy');

        // production에서는 unsafe-inline/unsafe-eval 제한
        expect($csp)->toContain("default-src 'self'");
        expect($csp)->toContain('script-src');
        expect($csp)->toContain('style-src');
    })->group('security', 'headers', 'csp');
});

describe('HSTS Headers', function (): void {
    /**
     * 테스트: HSTS 헤더는 HTTPS에서만 적용
     */
    test('hsts header only on https', function (): void {
        // Act: HTTP 요청 (HTTPS 아님)
        $response = $this->get('/');

        // Assert: HSTS는 HTTPS에서만 적용되므로 HTTP에서는 없음
        expect($response->headers->has('Strict-Transport-Security'))->toBeFalse('HSTS should not be present on HTTP requests');
    })->group('security', 'headers', 'hsts');

    /**
     * 테스트: HSTS 헤더는 production 환경에서 활성화
     */
    test('hsts header in production with https', function (): void {
        // Arrange: production 환경 시뮬레이션
        config(['app.env' => 'production']);

        // HTTPS 요청 시뮬레이션 (https:// 스킴 사용)
        $response = $this->get('https://localhost/');

        // Assert: HSTS 헤더 확인 (production + HTTPS)
        $response->assertHeader('Strict-Transport-Security');

        $hsts = $response->headers->get('Strict-Transport-Security');
        expect($hsts)->toContain('max-age=');
        expect($hsts)->toContain('includeSubDomains');
        expect($hsts)->toContain('preload');
    })->group('security', 'headers', 'hsts');
});

describe('Security Headers Across Routes', function (): void {
    /**
     * 테스트: 모든 라우트에 기본 보안 헤더 적용
     */
    test('security headers apply to all routes', function (): void {
        // Act: 여러 경로 요청
        $routes = [
            '/',
            '/admin',
            '/api/health',
        ];

        foreach ($routes as $route) {
            $response = $this->get($route);

            // Assert: 각 경로마다 기본 보안 헤더 확인
            expect($response->headers->has('X-Frame-Options'))->toBeTrue("X-Frame-Options missing on {$route}");
            expect($response->headers->has('X-Content-Type-Options'))->toBeTrue("X-Content-Type-Options missing on {$route}");
            expect($response->headers->has('Referrer-Policy'))->toBeTrue("Referrer-Policy missing on {$route}");
        }
    })->group('security', 'headers', 'all-routes');
});

<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use Tests\TestCase;

/**
 * Security Headers 테스트
 *
 * SecurityHeaders 미들웨어의 보안 헤더 적용을 검증합니다.
 * X-Frame-Options, CSP, HSTS 등 주요 보안 헤더를 확인합니다.
 */
class SecurityHeadersTest extends TestCase
{
    /**
     * 테스트: 기본 보안 헤더 존재 확인
     */
    public function test_includes_basic_security_headers(): void
    {
        // Act: API 요청
        $testResponse = $this->get('/');

        // Assert: 기본 보안 헤더 확인
        $testResponse->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $testResponse->assertHeader('X-Content-Type-Options', 'nosniff');
        $testResponse->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $testResponse->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(self)');
    }

    /**
     * 테스트: Content-Security-Policy 헤더는 production에서만 존재
     */
    public function test_includes_content_security_policy_in_production(): void
    {
        // Arrange: production 환경
        config(['app.env' => 'production']);

        // Act: production 요청
        $testResponse = $this->get('/');

        // Assert: CSP 헤더 확인
        $testResponse->assertHeader('Content-Security-Policy');

        $csp = $testResponse->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("default-src 'self'", $csp);
    }

    /**
     * 테스트: HSTS 헤더는 HTTPS에서만 적용
     */
    public function test_hsts_header_only_on_https(): void
    {
        // Act: HTTP 요청 (HTTPS 아님)
        $testResponse = $this->get('/');

        // Assert: HSTS는 HTTPS에서만 적용되므로 HTTP에서는 없음
        $this->assertFalse(
            $testResponse->headers->has('Strict-Transport-Security'),
            'HSTS should not be present on HTTP requests'
        );
    }

    /**
     * 테스트: HSTS 헤더는 production 환경에서 활성화
     */
    public function test_hsts_header_in_production_with_https(): void
    {
        // Arrange: production 환경 시뮬레이션
        config(['app.env' => 'production']);

        // HTTPS 요청 시뮬레이션 (https:// 스킴 사용)
        $testResponse = $this->get('https://localhost/');

        // Assert: HSTS 헤더 확인 (production + HTTPS)
        $testResponse->assertHeader('Strict-Transport-Security');

        $hsts = $testResponse->headers->get('Strict-Transport-Security');
        $this->assertStringContainsString('max-age=', $hsts);
        $this->assertStringContainsString('includeSubDomains', $hsts);
        $this->assertStringContainsString('preload', $hsts);
    }

    /**
     * 테스트: CSP는 production에서 더 엄격
     */
    public function test_csp_stricter_in_production(): void
    {
        // Arrange: production 환경
        config(['app.env' => 'production']);

        // Act: production 요청
        $testResponse = $this->get('/');

        // Assert: CSP 정책 확인
        $csp = $testResponse->headers->get('Content-Security-Policy');

        // production에서는 unsafe-inline/unsafe-eval 제한
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString('script-src', $csp);
        $this->assertStringContainsString('style-src', $csp);
    }

    /**
     * 테스트: 모든 라우트에 기본 보안 헤더 적용
     */
    public function test_security_headers_apply_to_all_routes(): void
    {
        // Act: 여러 경로 요청
        $routes = [
            '/',
            '/admin',
            '/api/health',
        ];

        foreach ($routes as $route) {
            $response = $this->get($route);

            // Assert: 각 경로마다 기본 보안 헤더 확인
            $this->assertTrue(
                $response->headers->has('X-Frame-Options'),
                "X-Frame-Options missing on {$route}"
            );
            $this->assertTrue(
                $response->headers->has('X-Content-Type-Options'),
                "X-Content-Type-Options missing on {$route}"
            );
            $this->assertTrue(
                $response->headers->has('Referrer-Policy'),
                "Referrer-Policy missing on {$route}"
            );
        }
    }

    /**
     * 테스트: X-Powered-By 헤더 제거 확인
     */
    public function test_removes_x_powered_by_header(): void
    {
        // Act: API 요청
        $testResponse = $this->get('/');

        // Assert: X-Powered-By 헤더가 없어야 함 (정보 노출 방지)
        $this->assertFalse(
            $testResponse->headers->has('X-Powered-By'),
            'X-Powered-By header should be removed for security'
        );
    }
}

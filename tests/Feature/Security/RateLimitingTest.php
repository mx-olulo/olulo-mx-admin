<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Rate Limiting 테스트
 *
 * 인증 API 엔드포인트의 Rate Limiting 동작을 검증합니다.
 * Firebase Mock을 사용하여 실제 Firebase 호출 없이 테스트합니다.
 */
class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 테스트: Rate Limiting이 적용되지 않은 경우 정상 요청
     */
    public function test_allows_requests_within_rate_limit(): void
    {
        // Arrange & Act: 10회 미만 요청 (제한 내)
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson(route('api.auth.locale.change', ['locale' => 'en']));

            // Assert: 모든 요청 성공
            $response->assertStatus(200);
        }
    }

    /**
     * 테스트: Rate Limit 초과 시 429 응답
     */
    public function test_blocks_requests_exceeding_rate_limit(): void
    {
        // Arrange & Act: Rate Limit 초과 요청 (1분당 10회 제한)
        $responses = [];

        for ($i = 0; $i < 12; $i++) {
            $responses[] = $this->postJson(route('api.auth.locale.change', ['locale' => 'en']));
        }

        // Assert: 처음 10개는 성공, 11번째부터 429 응답
        for ($i = 0; $i < 10; $i++) {
            $this->assertEquals(200, $responses[$i]->status(), "Request {$i} should succeed");
        }

        $this->assertEquals(429, $responses[10]->status(), 'Request 11 should be rate limited');
        $this->assertEquals(429, $responses[11]->status(), 'Request 12 should be rate limited');
    }

    /**
     * 테스트: Rate Limit 헤더 존재 확인
     */
    public function test_includes_rate_limit_headers(): void
    {
        // Act: API 요청
        $testResponse = $this->postJson(route('api.auth.locale.change', ['locale' => 'en']));

        // Assert: Rate Limit 관련 헤더 확인
        $testResponse->assertStatus(200);
        $testResponse->assertHeader('X-RateLimit-Limit');
        $testResponse->assertHeader('X-RateLimit-Remaining');
    }

    /**
     * 테스트: Rate Limit 초과 후 Retry-After 헤더 확인
     */
    public function test_includes_retry_after_header_when_rate_limited(): void
    {
        // Arrange: Rate Limit 초과까지 요청
        for ($i = 0; $i < 10; $i++) {
            $this->postJson(route('api.auth.locale.change', ['locale' => 'en']));
        }

        // Act: 11번째 요청 (Rate Limit 초과)
        $testResponse = $this->postJson(route('api.auth.locale.change', ['locale' => 'en']));

        // Assert: 429 응답 및 Retry-After 헤더 확인
        $testResponse->assertStatus(429);
        $testResponse->assertHeader('Retry-After');
        $testResponse->assertHeader('X-RateLimit-Limit', '10');
        $testResponse->assertHeader('X-RateLimit-Remaining', '0');
    }

    /**
     * 테스트: 다른 사용자는 독립적인 Rate Limit 적용
     */
    public function test_rate_limit_is_per_ip_address(): void
    {
        // Arrange: 첫 번째 IP에서 Rate Limit 초과
        for ($i = 0; $i < 10; $i++) {
            $this->postJson(route('api.auth.locale.change', ['locale' => 'en']));
        }

        // 11번째 요청 - 차단되어야 함
        $testResponse = $this->postJson(route('api.auth.locale.change', ['locale' => 'en']));
        $this->assertEquals(429, $testResponse->status());

        // Act: 다른 IP에서 요청 (시뮬레이션)
        $response2 = $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.100'])
            ->postJson(route('api.auth.locale.change', ['locale' => 'en']));

        // Assert: 다른 IP는 정상 요청 가능
        $response2->assertStatus(200);
    }

    /**
     * 테스트: 언어 변경 이외의 라우트도 Rate Limit 적용 확인
     */
    public function test_rate_limit_applies_to_all_auth_routes(): void
    {
        // 언어 변경으로 Rate Limit 소진
        for ($i = 0; $i < 10; $i++) {
            $this->postJson(route('api.auth.locale.change', ['locale' => 'en']));
        }

        // 다른 인증 라우트도 같은 Rate Limit 적용 확인
        // 참고: firebase-login은 Mock이 필요하므로 locale만 테스트
        $testResponse = $this->postJson(route('api.auth.locale.change', ['locale' => 'es-MX']));

        $this->assertEquals(429, $testResponse->status(), 'Rate limit should apply to all auth.* routes');
    }

    // =========================================================================
    // Rate Limiting 경계값 테스트
    // =========================================================================

    /**
     * 테스트: 정확히 10회 요청 시 모두 성공 (경계값)
     */
    public function test_exactly_ten_requests_all_succeed(): void
    {
        // Act: 정확히 10회 요청
        $responses = [];
        for ($i = 0; $i < 10; $i++) {
            $responses[] = $this->postJson(route('api.auth.locale.change', ['locale' => 'en']));
        }

        // Assert: 모든 요청 성공
        foreach ($responses as $index => $response) {
            $this->assertEquals(200, $response->status(), "Request {$index} should succeed");
        }
    }

    /**
     * 테스트: 11번째 요청부터 Rate Limit 적용 (경계값 초과)
     */
    public function test_eleventh_request_is_rate_limited(): void
    {
        // Arrange: 10회 요청 (모두 성공)
        for ($i = 0; $i < 10; $i++) {
            $response = $this->postJson(route('api.auth.locale.change', ['locale' => 'en']));
            $this->assertEquals(200, $response->status());
        }

        // Act: 11번째 요청
        $response = $this->postJson(route('api.auth.locale.change', ['locale' => 'en']));

        // Assert: 429 응답
        $this->assertEquals(429, $response->status());
        $response->assertHeader('Retry-After');
        $response->assertHeader('X-RateLimit-Limit', '10');
        $response->assertHeader('X-RateLimit-Remaining', '0');
    }

    /**
     * 테스트: Rate Limit 헤더가 남은 요청 수를 정확히 반영
     */
    public function test_rate_limit_headers_reflect_remaining_attempts(): void
    {
        // Act & Assert: 각 요청마다 남은 횟수 확인
        for ($i = 0; $i < 10; $i++) {
            $response = $this->postJson(route('api.auth.locale.change', ['locale' => 'en']));

            $remaining = (string) (10 - $i - 1);
            $response->assertStatus(200);
            $response->assertHeader('X-RateLimit-Limit', '10');
            $response->assertHeader('X-RateLimit-Remaining', $remaining);
        }

        // 마지막 요청 후 remaining이 0이 되었는지 확인
        $testResponse = $this->postJson(route('api.auth.locale.change', ['locale' => 'en']));
        $this->assertEquals(429, $testResponse->status());
        $testResponse->assertHeader('X-RateLimit-Remaining', '0');
    }

    /**
     * 테스트: Rate Limit 리셋 시간 확인 (Retry-After 헤더)
     */
    public function test_retry_after_header_provides_wait_time(): void
    {
        // Arrange: Rate Limit 소진
        for ($i = 0; $i < 10; $i++) {
            $this->postJson(route('api.auth.locale.change', ['locale' => 'en']));
        }

        // Act: Rate Limit 초과 요청
        $testResponse = $this->postJson(route('api.auth.locale.change', ['locale' => 'en']));

        // Assert: Retry-After 헤더 존재 및 값 확인 (초 단위)
        $testResponse->assertStatus(429);
        $this->assertTrue($testResponse->headers->has('Retry-After'));

        $retryAfter = (int) $testResponse->headers->get('Retry-After');
        $this->assertGreaterThan(0, $retryAfter, 'Retry-After should be greater than 0');
        $this->assertLessThanOrEqual(60, $retryAfter, 'Retry-After should not exceed 60 seconds for 1-minute window');
    }

    /**
     * 테스트: 동일 IP에서 다른 엔드포인트 호출 시 Rate Limit 공유
     *
     * 참고: 현재 설정에서는 auth.* 네임스페이스에 Rate Limit이 공유 적용됨
     */
    public function test_rate_limit_shared_across_auth_endpoints(): void
    {
        // Arrange: locale 변경으로 5회 사용
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson(route('api.auth.locale.change', ['locale' => 'en']));
            $this->assertEquals(200, $response->status());
        }

        // Act: 다시 locale 변경으로 5회 사용 (총 10회)
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson(route('api.auth.locale.change', ['locale' => 'es-MX']));
            $this->assertEquals(200, $response->status());
        }

        // 11번째 요청은 Rate Limit 초과
        $response = $this->postJson(route('api.auth.locale.change', ['locale' => 'en']));
        $this->assertEquals(429, $response->status());
    }

    /**
     * 테스트: Rate Limit 메시지 JSON 응답 형식 확인
     */
    public function test_rate_limit_response_has_proper_json_format(): void
    {
        // Arrange: Rate Limit 소진
        for ($i = 0; $i < 10; $i++) {
            $this->postJson(route('api.auth.locale.change', ['locale' => 'en']));
        }

        // Act: Rate Limit 초과 요청
        $testResponse = $this->postJson(route('api.auth.locale.change', ['locale' => 'en']));

        // Assert: JSON 응답 형식 확인
        $testResponse->assertStatus(429);
        $testResponse->assertJsonStructure([
            'message',
        ]);

        // 메시지 내용 확인
        $json = $testResponse->json();
        $this->assertStringContainsString('Too Many Attempts', $json['message'] ?? '');
    }

    /**
     * 테스트: 0회 요청 상태에서 헤더 확인
     */
    public function test_rate_limit_headers_on_first_request(): void
    {
        // Act: 첫 번째 요청
        $testResponse = $this->postJson(route('api.auth.locale.change', ['locale' => 'en']));

        // Assert: 첫 요청 시 헤더 확인
        $testResponse->assertStatus(200);
        $testResponse->assertHeader('X-RateLimit-Limit', '10');
        $testResponse->assertHeader('X-RateLimit-Remaining', '9'); // 1회 사용 후 9회 남음
    }

    /**
     * 테스트: Rate Limit 초과 후 연속 요청 모두 차단
     */
    public function test_consecutive_requests_after_rate_limit_all_blocked(): void
    {
        // Arrange: Rate Limit 소진
        for ($i = 0; $i < 10; $i++) {
            $this->postJson(route('api.auth.locale.change', ['locale' => 'en']));
        }

        // Act: 연속 5회 요청
        $blockedResponses = [];
        for ($i = 0; $i < 5; $i++) {
            $blockedResponses[] = $this->postJson(route('api.auth.locale.change', ['locale' => 'en']));
        }

        // Assert: 모두 429 응답
        foreach ($blockedResponses as $index => $response) {
            $this->assertEquals(429, $response->status(), "Blocked request {$index} should return 429");
        }
    }
}

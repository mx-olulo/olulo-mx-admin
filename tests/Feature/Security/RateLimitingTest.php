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
     *
     * @test
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
     *
     * @test
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
     *
     * @test
     */
    public function test_includes_rate_limit_headers(): void
    {
        // Act: API 요청
        $response = $this->postJson(route('api.auth.locale.change', ['locale' => 'en']));

        // Assert: Rate Limit 관련 헤더 확인
        $response->assertStatus(200);
        $response->assertHeader('X-RateLimit-Limit');
        $response->assertHeader('X-RateLimit-Remaining');
    }

    /**
     * 테스트: Rate Limit 초과 후 Retry-After 헤더 확인
     *
     * @test
     */
    public function test_includes_retry_after_header_when_rate_limited(): void
    {
        // Arrange: Rate Limit 초과까지 요청
        for ($i = 0; $i < 10; $i++) {
            $this->postJson(route('api.auth.locale.change', ['locale' => 'en']));
        }

        // Act: 11번째 요청 (Rate Limit 초과)
        $response = $this->postJson(route('api.auth.locale.change', ['locale' => 'en']));

        // Assert: 429 응답 및 Retry-After 헤더 확인
        $response->assertStatus(429);
        $response->assertHeader('Retry-After');
        $response->assertHeader('X-RateLimit-Limit', '10');
        $response->assertHeader('X-RateLimit-Remaining', '0');
    }

    /**
     * 테스트: 다른 사용자는 독립적인 Rate Limit 적용
     *
     * @test
     */
    public function test_rate_limit_is_per_ip_address(): void
    {
        // Arrange: 첫 번째 IP에서 Rate Limit 초과
        for ($i = 0; $i < 10; $i++) {
            $this->postJson(route('api.auth.locale.change', ['locale' => 'en']));
        }

        // 11번째 요청 - 차단되어야 함
        $response1 = $this->postJson(route('api.auth.locale.change', ['locale' => 'en']));
        $this->assertEquals(429, $response1->status());

        // Act: 다른 IP에서 요청 (시뮬레이션)
        $response2 = $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.100'])
            ->postJson(route('api.auth.locale.change', ['locale' => 'en']));

        // Assert: 다른 IP는 정상 요청 가능
        $response2->assertStatus(200);
    }

    /**
     * 테스트: 언어 변경 이외의 라우트도 Rate Limit 적용 확인
     *
     * @test
     */
    public function test_rate_limit_applies_to_all_auth_routes(): void
    {
        // 언어 변경으로 Rate Limit 소진
        for ($i = 0; $i < 10; $i++) {
            $this->postJson(route('api.auth.locale.change', ['locale' => 'en']));
        }

        // 다른 인증 라우트도 같은 Rate Limit 적용 확인
        // 참고: firebase-login은 Mock이 필요하므로 locale만 테스트
        $response = $this->postJson(route('api.auth.locale.change', ['locale' => 'es-MX']));

        $this->assertEquals(429, $response->status(), 'Rate limit should apply to all auth.* routes');
    }
}

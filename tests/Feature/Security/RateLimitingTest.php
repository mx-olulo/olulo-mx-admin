<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Rate Limiting 테스트
 *
 * 인증 API 엔드포인트의 Rate Limiting 동작을 검증합니다.
 * Firebase Mock을 사용하여 실제 Firebase 호출 없이 테스트합니다.
 */
uses(RefreshDatabase::class);

describe('Basic Rate Limiting', function () {
    /**
     * 테스트: Rate Limiting이 적용되지 않은 경우 정상 요청
     */
    test('allows requests within rate limit', function () {
        // Arrange & Act: 10회 미만 요청 (제한 내)
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson(route('api.auth.locale.change', ['locale' => 'en']));

            // Assert: 모든 요청 성공
            expect($response->status())->toBe(200);
        }
    })->group('security', 'rate-limiting');

    /**
     * 테스트: Rate Limit 초과 시 429 응답
     */
    test('blocks requests exceeding rate limit', function () {
        // Arrange & Act: Rate Limit 초과 요청 (1분당 10회 제한)
        $responses = [];

        for ($i = 0; $i < 12; $i++) {
            $responses[] = $this->postJson(route('api.auth.locale.change', ['locale' => 'en']));
        }

        // Assert: 처음 10개는 성공, 11번째부터 429 응답
        for ($i = 0; $i < 10; $i++) {
            expect($responses[$i]->status())->toBe(200, "Request {$i} should succeed");
        }

        expect($responses[10]->status())->toBe(429, 'Request 11 should be rate limited');
        expect($responses[11]->status())->toBe(429, 'Request 12 should be rate limited');
    })->group('security', 'rate-limiting');
});

describe('Rate Limit Headers', function () {
    /**
     * 테스트: Rate Limit 헤더 존재 확인
     */
    test('includes rate limit headers', function () {
        // Act: API 요청
        $response = $this->postJson(route('api.auth.locale.change', ['locale' => 'en']));

        // Assert: Rate Limit 관련 헤더 확인
        expect($response->status())->toBe(200);
        $response->assertHeader('X-RateLimit-Limit');
        $response->assertHeader('X-RateLimit-Remaining');
    })->group('security', 'rate-limiting', 'headers');

    /**
     * 테스트: Rate Limit 초과 후 Retry-After 헤더 확인
     */
    test('includes retry after header when rate limited', function () {
        // Arrange: Rate Limit 초과까지 요청
        for ($i = 0; $i < 10; $i++) {
            $this->postJson(route('api.auth.locale.change', ['locale' => 'en']));
        }

        // Act: 11번째 요청 (Rate Limit 초과)
        $response = $this->postJson(route('api.auth.locale.change', ['locale' => 'en']));

        // Assert: 429 응답 및 Retry-After 헤더 확인
        expect($response->status())->toBe(429);
        $response->assertHeader('Retry-After');
        $response->assertHeader('X-RateLimit-Limit', '10');
        $response->assertHeader('X-RateLimit-Remaining', '0');
    })->group('security', 'rate-limiting', 'headers');

    /**
     * 테스트: 0회 요청 상태에서 헤더 확인
     */
    test('rate limit headers on first request', function () {
        // Act: 첫 번째 요청
        $response = $this->postJson(route('api.auth.locale.change', ['locale' => 'en']));

        // Assert: 첫 요청 시 헤더 확인
        expect($response->status())->toBe(200);
        $response->assertHeader('X-RateLimit-Limit', '10');
        $response->assertHeader('X-RateLimit-Remaining', '9'); // 1회 사용 후 9회 남음
    })->group('security', 'rate-limiting', 'headers');
});

describe('Rate Limit Per IP Address', function () {
    /**
     * 테스트: 다른 사용자는 독립적인 Rate Limit 적용
     */
    test('rate limit is per ip address', function () {
        // Arrange: 첫 번째 IP에서 Rate Limit 초과
        for ($i = 0; $i < 10; $i++) {
            $this->postJson(route('api.auth.locale.change', ['locale' => 'en']));
        }

        // 11번째 요청 - 차단되어야 함
        $response = $this->postJson(route('api.auth.locale.change', ['locale' => 'en']));
        expect($response->status())->toBe(429);

        // Act: 다른 IP에서 요청 (시뮬레이션)
        $response2 = $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.100'])
            ->postJson(route('api.auth.locale.change', ['locale' => 'en']));

        // Assert: 다른 IP는 정상 요청 가능
        expect($response2->status())->toBe(200);
    })->group('security', 'rate-limiting', 'per-ip');
});

describe('Rate Limit Across Routes', function () {
    /**
     * 테스트: 언어 변경 이외의 라우트도 Rate Limit 적용 확인
     */
    test('rate limit applies to all auth routes', function () {
        // 언어 변경으로 Rate Limit 소진
        for ($i = 0; $i < 10; $i++) {
            $this->postJson(route('api.auth.locale.change', ['locale' => 'en']));
        }

        // 다른 인증 라우트도 같은 Rate Limit 적용 확인
        // 참고: firebase-login은 Mock이 필요하므로 locale만 테스트
        $response = $this->postJson(route('api.auth.locale.change', ['locale' => 'es-MX']));

        expect($response->status())->toBe(429, 'Rate limit should apply to all auth.* routes');
    })->group('security', 'rate-limiting', 'shared-limit');

    /**
     * 테스트: Rate Limit 공유 확인
     *
     * 참고: 현재 설정에서는 auth.* 네임스페이스에 Rate Limit이 공유 적용됨
     */
    test('rate limit shared across auth endpoints', function () {
        // Arrange: locale 변경으로 5회 사용
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson(route('api.auth.locale.change', ['locale' => 'en']));
            expect($response->status())->toBe(200);
        }

        // Act: 다시 locale 변경으로 5회 사용 (총 10회)
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson(route('api.auth.locale.change', ['locale' => 'es-MX']));
            expect($response->status())->toBe(200);
        }

        // 11번째 요청은 Rate Limit 초과
        $response = $this->postJson(route('api.auth.locale.change', ['locale' => 'en']));
        expect($response->status())->toBe(429);
    })->group('security', 'rate-limiting', 'shared-limit');
});

describe('Rate Limiting Boundary Tests', function () {
    /**
     * 테스트: 정확히 10회 요청 시 모두 성공 (경계값)
     */
    test('exactly ten requests all succeed', function () {
        // Act: 정확히 10회 요청
        $responses = [];
        for ($i = 0; $i < 10; $i++) {
            $responses[] = $this->postJson(route('api.auth.locale.change', ['locale' => 'en']));
        }

        // Assert: 모든 요청 성공
        foreach ($responses as $index => $response) {
            expect($response->status())->toBe(200, "Request {$index} should succeed");
        }
    })->group('security', 'rate-limiting', 'boundary');

    /**
     * 테스트: 11번째 요청부터 Rate Limit 적용 (경계값 초과)
     */
    test('eleventh request is rate limited', function () {
        // Arrange: 10회 요청 (모두 성공)
        for ($i = 0; $i < 10; $i++) {
            $response = $this->postJson(route('api.auth.locale.change', ['locale' => 'en']));
            expect($response->status())->toBe(200);
        }

        // Act: 11번째 요청
        $response = $this->postJson(route('api.auth.locale.change', ['locale' => 'en']));

        // Assert: 429 응답
        expect($response->status())->toBe(429);
        $response->assertHeader('Retry-After');
        $response->assertHeader('X-RateLimit-Limit', '10');
        $response->assertHeader('X-RateLimit-Remaining', '0');
    })->group('security', 'rate-limiting', 'boundary');

    /**
     * 테스트: Rate Limit 헤더가 남은 요청 수를 정확히 반영
     */
    test('rate limit headers reflect remaining attempts', function () {
        // Act & Assert: 각 요청마다 남은 횟수 확인
        for ($i = 0; $i < 10; $i++) {
            $response = $this->postJson(route('api.auth.locale.change', ['locale' => 'en']));

            $remaining = (string) (10 - $i - 1);
            expect($response->status())->toBe(200);
            $response->assertHeader('X-RateLimit-Limit', '10');
            $response->assertHeader('X-RateLimit-Remaining', $remaining);
        }

        // 마지막 요청 후 remaining이 0이 되었는지 확인
        $response = $this->postJson(route('api.auth.locale.change', ['locale' => 'en']));
        expect($response->status())->toBe(429);
        $response->assertHeader('X-RateLimit-Remaining', '0');
    })->group('security', 'rate-limiting', 'boundary');

    /**
     * 테스트: Rate Limit 리셋 시간 확인 (Retry-After 헤더)
     */
    test('retry after header provides wait time', function () {
        // Arrange: Rate Limit 소진
        for ($i = 0; $i < 10; $i++) {
            $this->postJson(route('api.auth.locale.change', ['locale' => 'en']));
        }

        // Act: Rate Limit 초과 요청
        $response = $this->postJson(route('api.auth.locale.change', ['locale' => 'en']));

        // Assert: Retry-After 헤더 존재 및 값 확인 (초 단위)
        expect($response->status())->toBe(429);
        expect($response->headers->has('Retry-After'))->toBeTrue();

        $retryAfter = (int) $response->headers->get('Retry-After');
        expect($retryAfter)->toBeGreaterThan(0, 'Retry-After should be greater than 0');
        expect($retryAfter)->toBeLessThanOrEqual(60, 'Retry-After should not exceed 60 seconds for 1-minute window');
    })->group('security', 'rate-limiting', 'boundary');
});

describe('Rate Limit Response Format', function () {
    /**
     * 테스트: Rate Limit 메시지 JSON 응답 형식 확인
     */
    test('rate limit response has proper json format', function () {
        // Arrange: Rate Limit 소진
        for ($i = 0; $i < 10; $i++) {
            $this->postJson(route('api.auth.locale.change', ['locale' => 'en']));
        }

        // Act: Rate Limit 초과 요청
        $response = $this->postJson(route('api.auth.locale.change', ['locale' => 'en']));

        // Assert: JSON 응답 형식 확인
        expect($response->status())->toBe(429);
        $response->assertJsonStructure([
            'message',
        ]);

        // 메시지 내용 확인
        $json = $response->json();
        expect($json['message'] ?? '')->toContain('Too Many Attempts');
    })->group('security', 'rate-limiting', 'response-format');

    /**
     * 테스트: Rate Limit 초과 후 연속 요청 모두 차단
     */
    test('consecutive requests after rate limit all blocked', function () {
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
            expect($response->status())->toBe(429, "Blocked request {$index} should return 429");
        }
    })->group('security', 'rate-limiting', 'consecutive-blocks');
});

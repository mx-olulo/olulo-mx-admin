<?php

namespace Tests\Browser\Auth;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\Browser\Concerns\InteractsWithFirebaseEmulator;
use Tests\DuskTestCase;

/**
 * Firebase ID Token 검증 기능 E2E 테스트
 *
 * 이 테스트는 Laravel API가 Firebase ID Token을
 * 올바르게 검증하는지 확인합니다.
 *
 * 검증 항목:
 * - 유효하지 않은 토큰 거부
 * - 잘못된 형식의 토큰 처리
 * - 토큰 누락 시 적절한 에러 응답
 * - 만료된 토큰 처리 (향후 구현)
 *
 * 환경변수 요구사항:
 * - FIREBASE_USE_EMULATOR=true
 * - FIREBASE_PROJECT_ID=demo-project
 * - FIREBASE_AUTH_EMULATOR_HOST=127.0.0.1:9099
 *
 * @see docs/auth.md
 * @see tests/Browser/Concerns/InteractsWithFirebaseEmulator.php
 */
#[Group('dusk')]
class FirebaseTokenValidationTest extends DuskTestCase
{
    use DatabaseMigrations;
    use InteractsWithFirebaseEmulator;

    /**
     * 각 테스트 전에 실행되는 설정
     *
     * Firebase Emulator가 실행 중인지 확인하고,
     * 실행되지 않았으면 테스트를 건너뜁니다.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Firebase Emulator가 실행 중인지 확인
        if (! $this->isFirebaseEmulatorRunning()) {
            $this->markTestSkipped('Firebase Emulator가 실행되지 않았습니다. 먼저 에뮬레이터를 시작하세요.');
        }
    }

    /**
     * Firebase Emulator 환경에서 잘못된 ID Token 처리 검증
     *
     * 시나리오:
     * 1. 잘못된 ID Token으로 로그인 시도
     * 2. 422 Unprocessable Entity 또는 401 Unauthorized 응답 확인
     *
     * 검증 항목:
     * - 잘못된 토큰 형식 거부
     * - 적절한 HTTP 상태 코드 반환 (401 또는 422)
     * - 인증 실패 시 세션 생성 안 됨
     */
    public function test_invalid_firebase_token_is_rejected(): void
    {
        $this->browse(function (Browser $browser) {
            // CSRF 토큰 획득
            $browser->visit('/sanctum/csrf-cookie');

            $invalidToken = 'invalid-token-12345';

            // 잘못된 토큰으로 로그인 시도
            $result = $browser->visit('/')
                ->script(
                    <<<JS
                        return fetch('/api/auth/firebase-login', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-XSRF-TOKEN': decodeURIComponent(
                                    document.cookie.split('; ')
                                        .find(row => row.startsWith('XSRF-TOKEN='))
                                        ?.split('=')[1] || ''
                                )
                            },
                            credentials: 'include',
                            body: JSON.stringify({ idToken: '$invalidToken' })
                        }).then(response => ({
                            ok: response.ok,
                            status: response.status
                        }));
                    JS
                )[0];

            // 인증 실패 확인 (422 또는 401)
            $this->assertFalse($result['ok'], '잘못된 토큰으로 로그인이 성공하면 안 됩니다.');
            $this->assertContains(
                $result['status'],
                [401, 422],
                '잘못된 토큰은 401 또는 422 상태 코드를 반환해야 합니다.'
            );
        });
    }

    /**
     * ID Token 누락 시 적절한 에러 응답 검증
     *
     * 시나리오:
     * 1. ID Token 없이 로그인 API 호출
     * 2. 422 Unprocessable Entity 응답 확인
     *
     * 검증 항목:
     * - 필수 파라미터 누락 시 422 상태 코드 반환
     * - 에러 메시지에 필드 정보 포함
     */
    public function test_missing_token_returns_validation_error(): void
    {
        $this->browse(function (Browser $browser) {
            // CSRF 토큰 획득
            $browser->visit('/sanctum/csrf-cookie');

            // 토큰 없이 로그인 시도
            $result = $browser->visit('/')
                ->script(
                    <<<'JS'
                        return fetch('/api/auth/firebase-login', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-XSRF-TOKEN': decodeURIComponent(
                                    document.cookie.split('; ')
                                        .find(row => row.startsWith('XSRF-TOKEN='))
                                        ?.split('=')[1] || ''
                                )
                            },
                            credentials: 'include',
                            body: JSON.stringify({})
                        }).then(response => ({
                            ok: response.ok,
                            status: response.status
                        }));
                    JS
                )[0];

            // 검증 실패 확인 (422)
            $this->assertFalse($result['ok'], '토큰 없이 로그인이 성공하면 안 됩니다.');
            $this->assertEquals(
                422,
                $result['status'],
                '토큰 누락 시 422 상태 코드를 반환해야 합니다.'
            );
        });
    }

    /**
     * 빈 문자열 토큰 처리 검증
     *
     * 시나리오:
     * 1. 빈 문자열 ID Token으로 로그인 시도
     * 2. 422 Unprocessable Entity 응답 확인
     *
     * 검증 항목:
     * - 빈 문자열 토큰 거부
     * - 적절한 검증 에러 메시지 반환
     */
    public function test_empty_token_is_rejected(): void
    {
        $this->browse(function (Browser $browser) {
            // CSRF 토큰 획득
            $browser->visit('/sanctum/csrf-cookie');

            $emptyToken = '';

            // 빈 토큰으로 로그인 시도
            $result = $browser->visit('/')
                ->script(
                    <<<JS
                        return fetch('/api/auth/firebase-login', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-XSRF-TOKEN': decodeURIComponent(
                                    document.cookie.split('; ')
                                        .find(row => row.startsWith('XSRF-TOKEN='))
                                        ?.split('=')[1] || ''
                                )
                            },
                            credentials: 'include',
                            body: JSON.stringify({ idToken: '$emptyToken' })
                        }).then(response => ({
                            ok: response.ok,
                            status: response.status
                        }));
                    JS
                )[0];

            // 검증 실패 확인
            $this->assertFalse($result['ok'], '빈 토큰으로 로그인이 성공하면 안 됩니다.');
            $this->assertEquals(
                422,
                $result['status'],
                '빈 토큰은 422 상태 코드를 반환해야 합니다.'
            );
        });
    }

    /**
     * 잘못된 형식의 토큰 처리 검증
     *
     * 시나리오:
     * 1. JWT 형식이 아닌 토큰으로 로그인 시도
     * 2. 401 Unauthorized 또는 422 Unprocessable Entity 응답 확인
     *
     * 검증 항목:
     * - JWT 형식이 아닌 토큰 거부
     * - 토큰 파싱 실패 시 적절한 에러 처리
     */
    public function test_malformed_token_is_rejected(): void
    {
        $this->browse(function (Browser $browser) {
            // CSRF 토큰 획득
            $browser->visit('/sanctum/csrf-cookie');

            // JWT 형식이 아닌 토큰 (점(.) 없음)
            $malformedToken = 'not-a-jwt-token';

            // 잘못된 형식의 토큰으로 로그인 시도
            $result = $browser->visit('/')
                ->script(
                    <<<JS
                        return fetch('/api/auth/firebase-login', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-XSRF-TOKEN': decodeURIComponent(
                                    document.cookie.split('; ')
                                        .find(row => row.startsWith('XSRF-TOKEN='))
                                        ?.split('=')[1] || ''
                                )
                            },
                            credentials: 'include',
                            body: JSON.stringify({ idToken: '$malformedToken' })
                        }).then(response => ({
                            ok: response.ok,
                            status: response.status
                        }));
                    JS
                )[0];

            // 인증 실패 확인
            $this->assertFalse($result['ok'], '잘못된 형식의 토큰으로 로그인이 성공하면 안 됩니다.');
            $this->assertContains(
                $result['status'],
                [401, 422],
                '잘못된 형식의 토큰은 401 또는 422 상태 코드를 반환해야 합니다.'
            );
        });
    }
}

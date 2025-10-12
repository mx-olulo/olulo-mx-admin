<?php

declare(strict_types=1);

namespace Tests\Browser\Auth;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\Browser\Concerns\InteractsWithFirebaseEmulator;
use Tests\DuskTestCase;

/**
 * Firebase 로그인 후 Sanctum 세션 확립 검증 E2E 테스트
 *
 * 이 테스트는 Firebase 인증 후 Laravel Sanctum 세션이
 * 올바르게 확립되는지 검증합니다.
 *
 * 검증 항목:
 * - 세션 쿠키 생성 및 속성 확인
 * - XSRF 토큰 쿠키 생성 확인
 * - 쿠키 도메인 정책 준수 (동일 루트 도메인)
 * - 인증된 사용자 정보 조회 가능 여부
 *
 * 환경변수 요구사항:
 * - FIREBASE_USE_EMULATOR=true
 * - FIREBASE_PROJECT_ID=demo-project
 * - FIREBASE_AUTH_EMULATOR_HOST=127.0.0.1:9099
 *
 * @see docs/auth.md
 * @see docs/devops/environments.md
 * @see tests/Browser/Concerns/InteractsWithFirebaseEmulator.php
 */
#[Group('dusk')]
class FirebaseSessionTest extends DuskTestCase
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
     * Firebase 로그인 후 Sanctum 세션 확립 검증
     *
     * 검증 항목:
     * - 세션 쿠키 존재 여부
     * - XSRF 토큰 존재 여부
     * - 쿠키 도메인 설정이 동일 루트 도메인 정책을 준수하는지 확인
     *
     * 이 테스트는 docs/auth.md의 "동일 루트 도메인 정책"을 검증합니다.
     */
    public function test_sanctum_session_established_after_firebase_login(): void
    {
        // Firebase 테스트 사용자 생성
        $this->createFirebaseTestUser();

        $this->browse(function (Browser $browser): void {
            // CSRF 토큰 획득
            $browser->visit('/sanctum/csrf-cookie');

            // Firebase 로그인
            $idToken = $this->signInWithFirebaseEmulator();

            // Laravel API 로그인
            $browser->visit('/')
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
                            body: JSON.stringify({ idToken: '$idToken' })
                        }).then(response => response.ok);
                    JS
                );

            // 세션 쿠키 검증
            $cookies = $browser->driver->manage()->getCookies();
            $sessionCookie = collect($cookies)->firstWhere('name', 'laravel_session');
            $xsrfCookie = collect($cookies)->firstWhere('name', 'XSRF-TOKEN');

            $this->assertNotNull($sessionCookie, 'Laravel 세션 쿠키가 존재해야 합니다.');
            $this->assertNotNull($xsrfCookie, 'XSRF 토큰 쿠키가 존재해야 합니다.');

            // 쿠키 도메인 검증 (동일 루트 도메인 정책)
            $sessionDomain = config('session.domain');
            if ($sessionDomain) {
                $this->assertEquals(
                    $sessionDomain,
                    $sessionCookie['domain'],
                    '세션 쿠키 도메인이 설정과 일치해야 합니다.'
                );
            }
        });
    }

    /**
     * 인증된 사용자가 보호된 라우트에 접근할 수 있는지 검증
     *
     * 시나리오:
     * 1. Firebase 로그인 및 세션 확립
     * 2. 보호된 API 엔드포인트 접근 (/api/user)
     * 3. 사용자 정보 응답 확인
     *
     * 검증 항목:
     * - 인증된 사용자 정보 조회 가능
     * - 응답 데이터가 유효한 JSON 형식
     * - 응답에 사용자 이메일 포함
     */
    public function test_authenticated_user_can_access_protected_routes(): void
    {
        // Firebase 테스트 사용자 생성
        $this->createFirebaseTestUser();

        $this->browse(function (Browser $browser): void {
            // CSRF 토큰 획득 및 Firebase 로그인
            $browser->visit('/sanctum/csrf-cookie');

            $idToken = $this->signInWithFirebaseEmulator();

            // Laravel API 로그인
            $browser->visit('/')
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
                            body: JSON.stringify({ idToken: '$idToken' })
                        }).then(response => response.json());
                    JS
                );

            // 보호된 사용자 정보 엔드포인트 접근
            $userInfo = $browser->visit('/api/user')
                ->script('return document.body.textContent;')[0];

            $userInfoArray = json_decode($userInfo, true);

            // 사용자 이메일 검증
            $this->assertIsArray($userInfoArray, '사용자 정보가 JSON 배열이어야 합니다.');
            $this->assertEquals(
                $this->testEmail,
                $userInfoArray['email'] ?? null,
                '사용자 이메일이 일치해야 합니다.'
            );
        });
    }
}

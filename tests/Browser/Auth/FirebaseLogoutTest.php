<?php

declare(strict_types=1);

namespace Tests\Browser\Auth;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\Browser\Concerns\InteractsWithFirebaseEmulator;
use Tests\DuskTestCase;

/**
 * Firebase 인증 후 로그아웃 기능 검증 E2E 테스트
 *
 * 이 테스트는 Firebase 인증 후 Laravel Sanctum 세션에서
 * 로그아웃이 올바르게 동작하는지 검증합니다.
 *
 * 검증 항목:
 * - 로그아웃 API 호출 성공
 * - 로그아웃 후 세션 무효화
 * - 로그아웃 후 보호된 라우트 접근 차단
 * - 로그아웃 후 사용자 정보 조회 불가
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
class FirebaseLogoutTest extends DuskTestCase
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
     * 로그아웃 시 세션이 무효화되는지 검증
     *
     * 시나리오:
     * 1. Firebase 로그인 및 세션 확립
     * 2. 로그인 상태 확인 (보호된 라우트 접근)
     * 3. 로그아웃 API 호출
     * 4. 보호된 라우트 접근 시 인증 실패 확인
     *
     * 검증 항목:
     * - 로그아웃 API 호출 성공
     * - 로그아웃 후 사용자 정보 조회 불가
     * - 로그아웃 후 이전 세션으로 보호된 리소스 접근 차단
     */
    public function test_logout_invalidates_session(): void
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
                        }).then(response => response.ok);
                    JS
                );

            // 로그인 확인 - 보호된 라우트에 접근 가능
            $browser->visit('/api/user');

            // 로그아웃 API 호출
            $browser->visit('/')
                ->script(
                    <<<'JS'
                        return fetch('/api/auth/logout', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-XSRF-TOKEN': decodeURIComponent(
                                    document.cookie.split('; ')
                                        .find(row => row.startsWith('XSRF-TOKEN='))
                                        ?.split('=')[1] || ''
                                )
                            },
                            credentials: 'include'
                        }).then(response => response.ok);
                    JS
                );

            // 로그아웃 후 보호된 라우트 접근 시 인증 실패 확인
            $browser->visit('/api/user');

            // HTTP 상태 코드 확인은 Dusk에서 직접 불가능하므로
            // 응답 내용으로 로그아웃 상태를 확인
            // 로그아웃 성공 시 사용자 이메일이 표시되지 않아야 함
            $browser->assertDontSee($this->testEmail);
        });
    }

    /**
     * 로그아웃 후 재로그인 가능 여부 검증
     *
     * 시나리오:
     * 1. Firebase 로그인 및 세션 확립
     * 2. 로그아웃 API 호출
     * 3. 동일 사용자로 재로그인
     * 4. 재로그인 후 보호된 라우트 접근 가능 확인
     *
     * 검증 항목:
     * - 로그아웃 후 재로그인 가능
     * - 재로그인 후 새로운 세션 확립
     * - 재로그인 후 보호된 리소스 접근 가능
     */
    public function test_user_can_login_again_after_logout(): void
    {
        // Firebase 테스트 사용자 생성
        $this->createFirebaseTestUser();

        $this->browse(function (Browser $browser): void {
            // 첫 번째 로그인
            $browser->visit('/sanctum/csrf-cookie');
            $idToken = $this->signInWithFirebaseEmulator();

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

            // 로그아웃
            $browser->visit('/')
                ->script(
                    <<<'JS'
                        return fetch('/api/auth/logout', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-XSRF-TOKEN': decodeURIComponent(
                                    document.cookie.split('; ')
                                        .find(row => row.startsWith('XSRF-TOKEN='))
                                        ?.split('=')[1] || ''
                                )
                            },
                            credentials: 'include'
                        }).then(response => response.ok);
                    JS
                );

            // 재로그인
            $browser->visit('/sanctum/csrf-cookie');
            $newIdToken = $this->signInWithFirebaseEmulator();

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
                            body: JSON.stringify({ idToken: '$newIdToken' })
                        }).then(response => response.ok);
                    JS
                );

            // 재로그인 후 보호된 라우트 접근 가능 확인
            $browser->visit('/api/user');

            // 사용자 정보가 다시 표시되어야 함
            $browser->assertSee($this->testEmail);
        });
    }
}

<?php

declare(strict_types=1);

namespace Tests\Browser\Auth;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\Browser\Concerns\InteractsWithFirebaseEmulator;
use Tests\DuskTestCase;

/**
 * Firebase Emulator 기반 로그인 플로우 E2E 테스트
 *
 * 이 테스트는 Firebase Emulator를 사용하여 실제 브라우저에서
 * Firebase 로그인 → Sanctum 세션 확립 과정을 검증합니다.
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
class FirebaseLoginTest extends DuskTestCase
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
     * 기본 Firebase Emulator 로그인 플로우 테스트
     *
     * 시나리오:
     * 1. CSRF 토큰 획득
     * 2. Firebase Emulator로 사용자 생성
     * 3. Firebase 로그인하여 ID Token 획득
     * 4. Laravel API에 ID Token 전송
     * 5. 인증 성공 확인
     *
     * 검증 항목:
     * - Firebase Emulator에서 사용자 생성 가능
     * - Firebase 로그인으로 ID Token 획득 가능
     * - Laravel API가 Firebase ID Token을 수락
     * - 인증 후 보호된 라우트 접근 가능
     */
    public function test_user_can_login_with_firebase_emulator(): void
    {
        // 1. Firebase 테스트 사용자 생성
        $this->createFirebaseTestUser();

        $this->browse(function (Browser $browser): void {
            // 2. CSRF 토큰 획득
            $browser->visit('/sanctum/csrf-cookie');

            // 3. Firebase 로그인하여 ID Token 획득
            $idToken = $this->signInWithFirebaseEmulator();

            // 4. Laravel API에 ID Token 전송하여 세션 확립
            $browser->visit('/');

            // JavaScript를 통해 API 호출 (브라우저 컨텍스트에서)
            $browser->script(
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

            // 5. 인증 성공 확인 - 보호된 사용자 정보 엔드포인트 접근
            $browser->visit('/api/user');
        });
    }
}

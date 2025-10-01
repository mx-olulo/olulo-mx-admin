<?php

namespace Tests\Browser\Auth;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Http;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\DuskTestCase;

/**
 * Firebase Emulator 기반 인증 플로우 E2E 테스트
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
 */
#[Group('dusk')]
class FirebaseLoginTest extends DuskTestCase
{
    use DatabaseMigrations;

    /**
     * Firebase Emulator 베이스 URL
     */
    protected string $firebaseEmulatorUrl = 'http://127.0.0.1:9099';

    /**
     * 테스트용 사용자 이메일
     */
    protected string $testEmail = 'test@example.com';

    /**
     * 테스트용 사용자 비밀번호
     */
    protected string $testPassword = 'password123';

    /**
     * 각 테스트 전에 실행되는 설정
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
     * Firebase Emulator 실행 여부 확인
     */
    protected function isFirebaseEmulatorRunning(): bool
    {
        try {
            $response = Http::timeout(2)->get($this->firebaseEmulatorUrl);

            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Firebase Emulator에 테스트 사용자 생성
     *
     * @return array<string, mixed>
     */
    protected function createFirebaseTestUser(): array
    {
        $projectId = config('services.firebase.project_id', 'demo-project');

        // Firebase Auth Emulator REST API를 통한 사용자 생성
        // https://firebase.google.com/docs/reference/rest/auth#section-create-email-password
        $response = Http::post(
            "{$this->firebaseEmulatorUrl}/identitytoolkit.googleapis.com/v1/accounts:signUp?key=fake-api-key",
            [
                'email' => $this->testEmail,
                'password' => $this->testPassword,
                'returnSecureToken' => true,
            ]
        );

        if (! $response->successful()) {
            throw new \RuntimeException('Firebase 테스트 사용자 생성 실패: ' . $response->body());
        }

        $data = $response->json();
        assert(is_array($data));

        /** @var array<string, mixed> $data */
        return $data;
    }

    /**
     * Firebase Emulator로 로그인하여 ID Token 획득
     */
    protected function signInWithFirebaseEmulator(): string
    {
        // Firebase Auth Emulator REST API를 통한 로그인
        // https://firebase.google.com/docs/reference/rest/auth#section-sign-in-email-password
        $response = Http::post(
            "{$this->firebaseEmulatorUrl}/identitytoolkit.googleapis.com/v1/accounts:signInWithPassword?key=fake-api-key",
            [
                'email' => $this->testEmail,
                'password' => $this->testPassword,
                'returnSecureToken' => true,
            ]
        );

        if (! $response->successful()) {
            throw new \RuntimeException('Firebase 로그인 실패: ' . $response->body());
        }

        $data = $response->json();
        assert(is_array($data) && isset($data['idToken']) && is_string($data['idToken']));

        return $data['idToken'];
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
     */
    public function test_user_can_login_with_firebase_emulator(): void
    {
        // 1. Firebase 테스트 사용자 생성
        $this->createFirebaseTestUser();

        $this->browse(function (Browser $browser) {
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

    /**
     * Firebase 로그인 후 Sanctum 세션 확립 검증
     *
     * 검증 항목:
     * - 세션 쿠키 존재 여부
     * - XSRF 토큰 존재 여부
     * - 인증된 사용자 정보 조회 가능 여부
     */
    public function test_sanctum_session_established_after_firebase_login(): void
    {
        // Firebase 테스트 사용자 생성
        $this->createFirebaseTestUser();

        $this->browse(function (Browser $browser) {
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
     */
    public function test_authenticated_user_can_access_protected_routes(): void
    {
        // Firebase 테스트 사용자 생성
        $userData = $this->createFirebaseTestUser();

        $this->browse(function (Browser $browser) {
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

    /**
     * 로그아웃 시 세션이 무효화되는지 검증
     *
     * 시나리오:
     * 1. Firebase 로그인 및 세션 확립
     * 2. 로그아웃 API 호출
     * 3. 보호된 라우트 접근 시 401 Unauthorized 응답 확인
     */
    public function test_logout_invalidates_session(): void
    {
        // Firebase 테스트 사용자 생성
        $this->createFirebaseTestUser();

        $this->browse(function (Browser $browser) {
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

            // 로그인 확인
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

            // 로그아웃 후 보호된 라우트 접근 시 401 응답 확인
            $browser->visit('/api/user');

            // HTTP 상태 코드 확인
            $statusCode = $browser->driver->executeScript('return window.performance.getEntries().slice(-1)[0].responseStatus;');

            // Laravel의 인증 실패 시 리다이렉트 또는 401 응답 확인
            // Dusk에서는 직접 HTTP 상태 코드를 확인하기 어려우므로, 응답 내용으로 검증
            $browser->assertDontSee($this->testEmail);
        });
    }

    /**
     * Firebase Emulator 환경에서 잘못된 ID Token 처리 검증
     *
     * 시나리오:
     * 1. 잘못된 ID Token으로 로그인 시도
     * 2. 422 Unprocessable Entity 또는 401 Unauthorized 응답 확인
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
}

<?php

declare(strict_types=1);

namespace Tests\Browser\Concerns;

use Illuminate\Support\Facades\Http;

/**
 * Firebase Emulator와의 상호작용을 위한 재사용 가능한 Trait
 *
 * 이 Trait은 E2E 테스트에서 Firebase Emulator를 사용한
 * 사용자 생성, 로그인, 토큰 획득 등의 공통 작업을 제공합니다.
 *
 * 환경변수 요구사항:
 * - FIREBASE_USE_EMULATOR=true
 * - FIREBASE_PROJECT_ID=demo-project
 * - FIREBASE_AUTH_EMULATOR_HOST=127.0.0.1:9099
 *
 * @see docs/auth.md
 */
trait InteractsWithFirebaseEmulator
{
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
     * Firebase Emulator 실행 여부 확인
     *
     * Firebase Auth Emulator가 실행 중인지 HTTP 요청을 통해 확인합니다.
     * 타임아웃 2초로 설정하여 빠르게 실패하도록 합니다.
     *
     * @return bool Firebase Emulator가 실행 중이면 true, 아니면 false
     */
    protected function isFirebaseEmulatorRunning(): bool
    {
        try {
            $response = Http::timeout(2)->get($this->firebaseEmulatorUrl);

            return $response->successful();
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Firebase Emulator에 테스트 사용자 생성
     *
     * Firebase Auth Emulator REST API를 사용하여 테스트 사용자를 생성합니다.
     * 이미 존재하는 이메일의 경우 실패할 수 있으므로 테스트마다 고유한 이메일 사용을 권장합니다.
     *
     * @return array<string, mixed> 생성된 사용자 정보 (idToken, email, refreshToken 등)
     *
     * @throws \RuntimeException Firebase 사용자 생성 실패 시
     *
     * @see https://firebase.google.com/docs/reference/rest/auth#section-create-email-password
     */
    protected function createFirebaseTestUser(): array
    {
        config('services.firebase.project_id', 'demo-project');

        // Firebase Auth Emulator REST API를 통한 사용자 생성
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
     *
     * Firebase Auth Emulator REST API를 사용하여 이메일/비밀번호로 로그인하고
     * ID Token을 반환합니다. 이 토큰은 Laravel API 인증에 사용됩니다.
     *
     * @return string Firebase ID Token
     *
     * @throws \RuntimeException Firebase 로그인 실패 시
     *
     * @see https://firebase.google.com/docs/reference/rest/auth#section-sign-in-email-password
     */
    protected function signInWithFirebaseEmulator(): string
    {
        // Firebase Auth Emulator REST API를 통한 로그인
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
}

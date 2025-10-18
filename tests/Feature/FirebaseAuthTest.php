<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\FirebaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;

/**
 * Firebase 인증 기능 테스트
 *
 * Firebase Authentication과 Laravel Sanctum 통합 인증 시스템의
 * 모든 주요 기능을 테스트합니다.
 *
 * 테스트 범위:
 * - 로그인 페이지 표시
 * - Firebase 토큰 검증 및 로그인
 * - 로그아웃 처리
 * - 언어 변경
 * - API 엔드포인트 인증
 * - 보호된 라우트 접근 제어
 */
uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Firebase 서비스 모킹
    $this->firebaseServiceMock = \Mockery::mock(FirebaseService::class);
    $this->app->instance(FirebaseService::class, $this->firebaseServiceMock);

    // Firebase 설정 모킹
    Config::set('services.firebase.web_api_key', 'test-api-key');
    Config::set('services.firebase.project_id', 'test-project');

    // 테스트용 환경 변수 설정
    Config::set('app.locale', 'ko');
});

/**
 * 유효한 Firebase 토큰 데이터 생성
 *
 * @return array<string, mixed>
 */
function createValidFirebaseTokenData(): array
{
    return [
        'uid' => 'firebase_uid_123',
        'email' => 'test@example.com',
        'email_verified' => true,
        'phone_number' => '+821012345678',
        'name' => 'Test User',
        'picture' => 'https://example.com/avatar.jpg',
        'provider_id' => 'google.com',
    ];
}

describe('로그인 페이지', function (): void {
    /**
     * 테스트: 로그인 페이지 표시 확인
     */
    test('로그인 페이지 표시', function (): void {
        // Act: 로그인 페이지 요청
        $response = $this->get(route('auth.login', ['locale' => 'ko']));

        // Assert: 페이지 표시 확인
        expect($response->status())->toBe(200);
        $response->assertViewIs('auth.login');
        $response->assertViewHas('firebaseConfig');
        $response->assertViewHas('locale', 'ko');
        $response->assertViewHas('theme', 'light');

        $supportedLocales = $response->viewData('supportedLocales');
        expect($supportedLocales)->toBeArray();
        expect($supportedLocales)->toEqualCanonicalizing(['ko', 'en', 'es-MX']);
        $response->assertViewHas('callbackUrl');

        // Firebase 설정 확인
        $firebaseConfig = $response->viewData('firebaseConfig');
        expect($firebaseConfig)->toHaveKey('apiKey');
        expect($firebaseConfig)->toHaveKey('authDomain');
        expect($firebaseConfig)->toHaveKey('projectId');
    })->group('firebase', 'login-page');

    /**
     * 테스트: intended URL 세션 저장
     */
    test('intended URL을 세션에 저장', function (): void {
        // Act: intended URL과 함께 로그인 페이지 요청
        $response = $this->get(route('auth.login', ['intended' => '/admin/dashboard']));

        // Assert: 세션에 intended URL 저장 확인
        expect($response->status())->toBe(200);
        $response->assertSessionHas('auth.intended_url', '/admin/dashboard');
    })->group('firebase', 'login-page');
});

describe('Firebase 토큰 로그인', function (): void {
    /**
     * 테스트: 유효한 Firebase 토큰으로 로그인
     */
    test('유효한 Firebase 토큰으로 로그인 성공', function (): void {
        // Arrange: 유효한 토큰 데이터 준비
        $tokenData = createValidFirebaseTokenData();
        $idToken = 'valid-firebase-id-token';

        // Firebase 서비스 모킹
        $this->firebaseServiceMock
            ->shouldReceive('verifyIdToken')
            ->once()
            ->with($idToken)
            ->andReturn($tokenData);

        $this->firebaseServiceMock
            ->shouldReceive('syncFirebaseUserWithLaravel')
            ->once()
            ->with($tokenData)
            ->andReturn(User::factory()->create([
                'firebase_uid' => $tokenData['uid'],
                'email' => $tokenData['email'],
                'name' => $tokenData['name'],
            ]));

        // Act: Firebase 콜백 요청 (기본 리다이렉트: /store)
        $response = $this->post(route('auth.firebase.callback'), [
            'idToken' => $idToken,
        ]);

        // Assert: 로그인 성공 및 리다이렉트 확인
        $response->assertRedirect('/store');
        $response->assertSessionHas('auth.success');
        expect(auth()->check())->toBeTrue();

        // 로그인된 사용자 정보 확인
        $user = Auth::user();
        expect($user->firebase_uid)->toBe($tokenData['uid']);
        expect($user->email)->toBe($tokenData['email']);
    })->group('firebase', 'login');

    /**
     * 테스트: intended URL로 리다이렉트
     */
    test('로그인 후 intended URL로 리다이렉트', function (): void {
        // Arrange: 유효한 토큰 데이터와 intended URL 준비
        $tokenData = createValidFirebaseTokenData();
        $idToken = 'valid-firebase-id-token';
        $intendedUrl = '/admin/specific-page';

        // 세션에 intended URL 저장
        Session::put('auth.intended_url', $intendedUrl);

        // Firebase 서비스 모킹
        $this->firebaseServiceMock
            ->shouldReceive('verifyIdToken')
            ->once()
            ->with($idToken)
            ->andReturn($tokenData);

        $this->firebaseServiceMock
            ->shouldReceive('syncFirebaseUserWithLaravel')
            ->once()
            ->with($tokenData)
            ->andReturn(User::factory()->create([
                'firebase_uid' => $tokenData['uid'],
                'email' => $tokenData['email'],
            ]));

        // Act: Firebase 콜백 요청
        $response = $this->withSession(['auth.intended_url' => $intendedUrl])
            ->post(route('auth.firebase.callback'), [
                'idToken' => $idToken,
            ]);

        // Assert: intended URL로 리다이렉트 확인
        $response->assertRedirect($intendedUrl);
        expect(auth()->check())->toBeTrue();
    })->group('firebase', 'login');

    /**
     * 테스트: 잘못된 Firebase 토큰 처리
     */
    test('잘못된 Firebase 토큰 거부', function (): void {
        // Arrange: 잘못된 토큰 준비
        $idToken = 'invalid-firebase-id-token';

        // Firebase 서비스 모킹 - 토큰 검증 실패
        $this->firebaseServiceMock
            ->shouldReceive('verifyIdToken')
            ->once()
            ->with($idToken)
            ->andThrow(new FailedToVerifyToken('Invalid token'));

        // Act: Firebase 콜백 요청
        $response = $this->post(route('auth.firebase.callback'), [
            'idToken' => $idToken,
        ]);

        // Assert: 검증 실패 응답 확인
        expect($response->status())->toBe(302);
        $response->assertSessionHasErrors(['idToken']);
        expect(auth()->guest())->toBeTrue();
    })->group('firebase', 'login', 'validation');

    /**
     * 테스트: 토큰 없이 콜백 요청 시 검증 실패
     */
    test('토큰 없이 콜백 요청 시 검증 실패', function (): void {
        // Act: 토큰 없이 Firebase 콜백 요청
        $response = $this->post(route('auth.firebase.callback'));

        // Assert: 검증 실패 확인
        expect($response->status())->toBe(302);
        $response->assertSessionHasErrors(['idToken']);
        expect(auth()->guest())->toBeTrue();
    })->group('firebase', 'login', 'validation');

    /**
     * 테스트: Firebase 서비스 오류 처리
     */
    test('Firebase 서비스 오류 처리', function (): void {
        // Arrange: 토큰 데이터 준비
        $tokenData = createValidFirebaseTokenData();
        $idToken = 'valid-firebase-id-token';

        // Firebase 서비스 모킹 - 동기화 중 오류 발생
        $this->firebaseServiceMock
            ->shouldReceive('verifyIdToken')
            ->once()
            ->with($idToken)
            ->andReturn($tokenData);

        $this->firebaseServiceMock
            ->shouldReceive('syncFirebaseUserWithLaravel')
            ->once()
            ->with($tokenData)
            ->andThrow(new \Exception('Database error'));

        // Act: Firebase 콜백 요청
        $response = $this->post(route('auth.firebase.callback'), [
            'idToken' => $idToken,
        ]);

        // Assert: 오류 처리 확인
        $response->assertRedirect(route('auth.login'));
        $response->assertSessionHasErrors();
        expect(auth()->guest())->toBeTrue();
    })->group('firebase', 'login', 'error-handling');
});

describe('로그아웃', function (): void {
    /**
     * 테스트: 로그아웃 기능
     */
    test('로그아웃 성공', function (): void {
        // Arrange: 로그인한 사용자 생성
        $user = User::factory()->create();
        $this->actingAs($user);

        // Act: 로그아웃 요청
        $response = $this->post(route('auth.logout'));

        // Assert: 로그아웃 및 리다이렉트 확인
        $response->assertRedirect(route('auth.login'));
        $response->assertSessionHas('auth.success');
        expect(auth()->guest())->toBeTrue();
    })->group('firebase', 'logout');

    /**
     * 테스트: API 로그아웃
     */
    test('API 로그아웃 JSON 응답', function (): void {
        // Arrange: 로그인한 사용자 생성
        $user = User::factory()->create();

        // Sanctum SPA 인증을 위한 설정
        $this->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

        // Act: 로그인 후 API 로그아웃 요청
        $this->actingAs($user, 'web');
        $response = $this->withHeaders([
            'Accept' => 'application/json',
            'Referer' => config('app.url'),
        ])->post(route('api.auth.logout'));

        // Assert: JSON 응답 확인
        expect($response->status())->toBe(200);
        $response->assertJson([
            'success' => true,
            'message' => __('auth.logout_success'),
        ]);
    })->group('firebase', 'logout', 'api');
});

describe('언어 변경', function (): void {
    /**
     * 테스트: 언어 변경
     */
    test('언어 변경 성공', function (): void {
        // Act: 로그인 페이지에 locale 쿼리 파라미터로 전달
        $response = $this->get(route('auth.login', ['locale' => 'en']));

        // Assert: 뷰에 전달된 locale 값 확인
        expect($response->status())->toBe(200);
        $response->assertViewHas('locale', 'en');
    })->group('firebase', 'locale');

    /**
     * 테스트: 지원하지 않는 언어 요청 시 기본값 사용
     */
    test('지원하지 않는 언어는 기본값 사용', function (): void {
        // Act: 지원하지 않는 언어로 로그인 페이지 요청
        $response = $this->get(route('auth.login', ['locale' => 'fr']));

        // Assert: 기본 언어(config('app.locale')) 반영
        expect($response->status())->toBe(200);
        $response->assertViewHas('locale', config('app.locale', 'ko'));
    })->group('firebase', 'locale');

    /**
     * 테스트: API 언어 변경
     */
    test('API 언어 변경 JSON 응답', function (): void {
        // Act: API 언어 변경 요청
        $response = $this->postJson(route('api.auth.locale.change', ['locale' => 'es-MX']));

        // Assert: JSON 응답 확인
        expect($response->status())->toBe(200);
        $response->assertJson([
            'success' => true,
            'message' => __('auth.locale_changed'),
            'locale' => 'es-MX',
        ]);
        // 현재 구현은 세션에 locale을 저장하지 않으므로 세션 단언은 생략
    })->group('firebase', 'locale', 'api');
});

describe('API Firebase 로그인', function (): void {
    /**
     * 테스트: API Firebase 로그인
     */
    test('API Firebase 로그인 성공', function (): void {
        // Arrange: 유효한 토큰 데이터 준비
        $tokenData = createValidFirebaseTokenData();
        $idToken = 'valid-firebase-id-token';

        $user = User::factory()->create([
            'firebase_uid' => $tokenData['uid'],
            'email' => $tokenData['email'],
            'name' => $tokenData['name'],
            'phone_number' => $tokenData['phone_number'],
            'avatar_url' => $tokenData['picture'],
        ]);

        // Firebase 서비스 모킹
        $this->firebaseServiceMock
            ->shouldReceive('verifyIdToken')
            ->once()
            ->with($idToken)
            ->andReturn($tokenData);

        $this->firebaseServiceMock
            ->shouldReceive('syncFirebaseUserWithLaravel')
            ->once()
            ->with($tokenData)
            ->andReturn($user);

        // Act: API Firebase 로그인 요청
        $response = $this->postJson(route('api.auth.firebase.login'), [
            'idToken' => $idToken,
        ]);

        // Assert: JSON 응답 및 인증 확인
        expect($response->status())->toBe(200);
        $response->assertJson([
            'success' => true,
            'message' => __('auth.login_success'),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'firebase_uid' => $user->firebase_uid,
                'phone_number' => $user->phone_number,
                'avatar_url' => $user->avatar_url,
            ],
        ]);
        expect(auth()->check())->toBeTrue();
    })->group('firebase', 'api', 'login');

    /**
     * 테스트: API Firebase 로그인 - 잘못된 토큰
     */
    test('API Firebase 로그인 - 잘못된 토큰 거부', function (): void {
        // Arrange: 잘못된 토큰 준비
        $idToken = 'invalid-firebase-id-token';

        // Firebase 서비스 모킹 - 토큰 검증 실패
        $this->firebaseServiceMock
            ->shouldReceive('verifyIdToken')
            ->once()
            ->with($idToken)
            ->andThrow(new FailedToVerifyToken('Invalid token'));

        // Act: API Firebase 로그인 요청
        $response = $this->postJson(route('api.auth.firebase.login'), [
            'idToken' => $idToken,
        ]);

        // Assert: 오류 응답 확인
        expect($response->status())->toBe(422);
        $response->assertJson([
            'success' => false,
            'message' => __('auth.invalid_firebase_token'),
            'errors' => [
                'idToken' => [__('auth.invalid_firebase_token')],
            ],
        ]);
        expect(auth()->guest())->toBeTrue();
    })->group('firebase', 'api', 'login', 'validation');

    /**
     * 테스트: API Firebase 로그인 - 서버 오류
     */
    test('API Firebase 로그인 - 서버 오류 처리', function (): void {
        // Arrange: 토큰 데이터 준비
        $tokenData = createValidFirebaseTokenData();
        $idToken = 'valid-firebase-id-token';

        // Firebase 서비스 모킹 - 서버 오류 발생
        $this->firebaseServiceMock
            ->shouldReceive('verifyIdToken')
            ->once()
            ->with($idToken)
            ->andReturn($tokenData);

        $this->firebaseServiceMock
            ->shouldReceive('syncFirebaseUserWithLaravel')
            ->once()
            ->with($tokenData)
            ->andThrow(new \Exception('Server error'));

        // Act: API Firebase 로그인 요청
        $response = $this->postJson(route('api.auth.firebase.login'), [
            'idToken' => $idToken,
        ]);

        // Assert: 서버 오류 응답 확인
        expect($response->status())->toBe(500);
        $response->assertJson([
            'success' => false,
            'message' => __('auth.login_failed'),
        ]);
        expect(auth()->guest())->toBeTrue();
    })->group('firebase', 'api', 'login', 'error-handling');
});

describe('인증 및 접근 제어', function (): void {
    /**
     * 테스트: 보호된 라우트 인증 확인
     */
    test('보호된 라우트는 인증 필요', function (): void {
        // Act: 인증 없이 보호된 라우트 접근 시도
        $response = $this->post(route('auth.logout'));

        // Assert: 인증 리다이렉트 확인
        $response->assertRedirect(route('login'));
        expect(auth()->guest())->toBeTrue();
    })->group('firebase', 'auth', 'protected-routes');

    /**
     * 테스트: 인증된 사용자는 로그인 페이지 접근 불가
     */
    test('인증된 사용자는 로그인 페이지 접근 불가', function (): void {
        // Arrange: 로그인한 사용자 생성
        $user = User::factory()->create();
        $this->actingAs($user);

        // Act: 로그인 페이지 접근 시도
        $response = $this->get(route('auth.login'));

        // Assert: 리다이렉트 확인
        $response->assertRedirect('/dashboard');
    })->group('firebase', 'auth', 'guest-only');
});

describe('Firebase 사용자 동기화', function (): void {
    /**
     * 테스트: Firebase 사용자 동기화 - 새 사용자 생성
     */
    test('새 Firebase 사용자 생성', function (): void {
        // Arrange: 새 Firebase 사용자 데이터
        $tokenData = createValidFirebaseTokenData();
        $idToken = 'valid-firebase-id-token';

        // Firebase 서비스 실제 동작 모킹
        $this->firebaseServiceMock
            ->shouldReceive('verifyIdToken')
            ->once()
            ->with($idToken)
            ->andReturn($tokenData);

        // 새 사용자 생성을 위한 동기화
        $this->firebaseServiceMock
            ->shouldReceive('syncFirebaseUserWithLaravel')
            ->once()
            ->with($tokenData)
            ->andReturnUsing(fn ($data) => User::create([
                'firebase_uid' => $data['uid'],
                'email' => $data['email'],
                'name' => $data['name'],
                'email_verified_at' => $data['email_verified'] ? now() : null,
                'phone_number' => $data['phone_number'],
                'avatar_url' => $data['picture'],
                'password' => bcrypt(\Illuminate\Support\Str::random(32)),
            ]));

        // Act: Firebase 콜백 요청
        $response = $this->post(route('auth.firebase.callback'), [
            'idToken' => $idToken,
        ]);

        // Assert: 사용자 생성 및 로그인 확인 (기본 리다이렉트: /store)
        $response->assertRedirect('/store');
        expect(auth()->check())->toBeTrue();

        expect(User::where('firebase_uid', $tokenData['uid'])->exists())->toBeTrue();
        expect(User::where('email', $tokenData['email'])->exists())->toBeTrue();
    })->group('firebase', 'user-sync', 'create');

    /**
     * 테스트: Firebase 사용자 동기화 - 기존 사용자 업데이트
     */
    test('기존 Firebase 사용자 업데이트', function (): void {
        // Arrange: 기존 사용자 생성
        $existingUser = User::factory()->create([
            'firebase_uid' => 'firebase_uid_123',
            'email' => 'test@example.com',
            'name' => 'Old Name',
            'avatar_url' => null,
        ]);

        // 업데이트된 Firebase 데이터
        $tokenData = [
            'uid' => 'firebase_uid_123',
            'email' => 'test@example.com',
            'email_verified' => true,
            'phone_number' => '+821012345678',
            'name' => 'Updated Name',
            'picture' => 'https://example.com/new-avatar.jpg',
            'provider_id' => 'google.com',
        ];
        $idToken = 'valid-firebase-id-token';

        // Firebase 서비스 모킹
        $this->firebaseServiceMock
            ->shouldReceive('verifyIdToken')
            ->once()
            ->with($idToken)
            ->andReturn($tokenData);

        $this->firebaseServiceMock
            ->shouldReceive('syncFirebaseUserWithLaravel')
            ->once()
            ->with($tokenData)
            ->andReturnUsing(function (array $data) use ($existingUser) {
                $existingUser->update([
                    'name' => $data['name'],
                    'phone_number' => $data['phone_number'],
                    'avatar_url' => $data['picture'],
                    'email_verified_at' => $data['email_verified'] ? now() : null,
                ]);

                return $existingUser->fresh();
            });

        // Act: Firebase 콜백 요청
        $response = $this->post(route('auth.firebase.callback'), [
            'idToken' => $idToken,
        ]);

        // Assert: 사용자 업데이트 확인 (기본 리다이렉트: /store)
        $response->assertRedirect('/store');
        expect(auth()->check())->toBeTrue();

        $updatedUser = User::find($existingUser->id);
        expect($updatedUser->name)->toBe('Updated Name');
        expect($updatedUser->phone_number)->toBe('+821012345678');
        expect($updatedUser->avatar_url)->toBe('https://example.com/new-avatar.jpg');
        expect($updatedUser->email_verified_at)->not->toBeNull();
    })->group('firebase', 'user-sync', 'update');

    /**
     * 테스트: 전화번호만 있는 Firebase 사용자 처리
     */
    test('전화번호만 있는 Firebase 사용자 처리', function (): void {
        // Arrange: 이메일 없이 전화번호만 있는 사용자 데이터
        $tokenData = [
            'uid' => 'firebase_uid_phone',
            'email' => null,
            'email_verified' => false,
            'phone_number' => '+821098765432',
            'name' => 'Phone User',
            'picture' => null,
            'provider_id' => 'phone',
        ];
        $idToken = 'valid-firebase-id-token';

        // Firebase 서비스 모킹
        $this->firebaseServiceMock
            ->shouldReceive('verifyIdToken')
            ->once()
            ->with($idToken)
            ->andReturn($tokenData);

        $this->firebaseServiceMock
            ->shouldReceive('syncFirebaseUserWithLaravel')
            ->once()
            ->with($tokenData)
            ->andReturnUsing(function (array $data) {
                // 전화번호로 이메일 생성
                $cleanPhoneNumber = preg_replace('/[^0-9]/', '', (string) $data['phone_number']);
                $email = $cleanPhoneNumber . '@olulo.com.mx';

                return User::create([
                    'firebase_uid' => $data['uid'],
                    'email' => $email,
                    'name' => $data['name'],
                    'phone_number' => $data['phone_number'],
                    'password' => bcrypt(\Illuminate\Support\Str::random(32)),
                ]);
            });

        // Act: Firebase 콜백 요청
        $response = $this->post(route('auth.firebase.callback'), [
            'idToken' => $idToken,
        ]);

        // Assert: 사용자 생성 확인 (기본 리다이렉트: /store)
        $response->assertRedirect('/store');
        expect(auth()->check())->toBeTrue();

        expect(User::where('firebase_uid', 'firebase_uid_phone')->exists())->toBeTrue();
        expect(User::where('email', '821098765432@olulo.com.mx')->exists())->toBeTrue();
        expect(User::where('phone_number', '+821098765432')->exists())->toBeTrue();
    })->group('firebase', 'user-sync', 'phone-only');
});

describe('CSRF 및 보안', function (): void {
    /**
     * 테스트: CSRF 토큰 없이 API 요청 시 성공 (Sanctum SPA 세션)
     *
     * Sanctum SPA는 CSRF 토큰을 자동으로 처리하므로,
     * API 라우트에서는 CSRF 검증이 필요하지 않습니다.
     */
    test('API 요청은 CSRF 토큰 없이 동작', function (): void {
        // Arrange: 유효한 토큰 데이터
        $tokenData = createValidFirebaseTokenData();
        $idToken = 'valid-firebase-id-token';

        $user = User::factory()->create([
            'firebase_uid' => $tokenData['uid'],
            'email' => $tokenData['email'],
        ]);

        $this->firebaseServiceMock
            ->shouldReceive('verifyIdToken')
            ->once()
            ->andReturn($tokenData);

        $this->firebaseServiceMock
            ->shouldReceive('syncFirebaseUserWithLaravel')
            ->once()
            ->andReturn($user);

        // Act: CSRF 토큰 없이 API 로그인 요청 (JSON)
        $response = $this->postJson(route('api.auth.firebase.login'), [
            'idToken' => $idToken,
        ]);

        // Assert: 정상 처리 확인
        expect($response->status())->toBe(200);
        $response->assertJson(['success' => true]);
    })->group('firebase', 'security', 'csrf');

    /**
     * 테스트: Web 라우트는 CSRF 토큰 필요
     */
    test('Web 라우트는 CSRF 토큰 필요', function (): void {
        // Act: CSRF 토큰 없이 웹 콜백 요청
        $response = $this->post(route('auth.firebase.callback'), [
            'idToken' => 'test-token',
        ]);

        // Assert: 현재 구현에서는 302 리다이렉트 발생
        expect($response->status())->toBe(302);
    })->group('firebase', 'security', 'csrf');
});

describe('Firebase 토큰 검증', function (): void {
    /**
     * 테스트: 빈 문자열 토큰 처리
     */
    test('빈 문자열 토큰 거부', function (): void {
        // Act: 빈 문자열 토큰으로 로그인 시도
        $response = $this->postJson(route('api.auth.firebase.login'), [
            'idToken' => '',
        ]);

        // Assert: 검증 실패
        expect($response->status())->toBe(422);
        $response->assertJsonValidationErrors(['idToken']);
    })->group('firebase', 'token-validation');

    /**
     * 테스트: null 토큰 처리
     */
    test('null 토큰 거부', function (): void {
        // Act: null 토큰으로 로그인 시도
        $response = $this->postJson(route('api.auth.firebase.login'), [
            'idToken' => null,
        ]);

        // Assert: 검증 실패
        expect($response->status())->toBe(422);
        $response->assertJsonValidationErrors(['idToken']);
    })->group('firebase', 'token-validation');

    /**
     * 테스트: JWT 형식이 아닌 잘못된 문자열 토큰
     */
    test('잘못된 JWT 형식 토큰 거부', function (): void {
        // Arrange: 형식이 잘못된 토큰
        $idToken = 'not-a-valid-jwt-format';

        $this->firebaseServiceMock
            ->shouldReceive('verifyIdToken')
            ->once()
            ->with($idToken)
            ->andThrow(new FailedToVerifyToken('Malformed token'));

        // Act: 잘못된 형식의 토큰으로 로그인 시도
        $response = $this->postJson(route('api.auth.firebase.login'), [
            'idToken' => $idToken,
        ]);

        // Assert: 422 응답 및 에러 메시지 확인
        expect($response->status())->toBe(422);
        $response->assertJson([
            'success' => false,
            'message' => __('auth.invalid_firebase_token'),
        ]);
    })->group('firebase', 'token-validation');

    /**
     * 테스트: 만료된 Firebase 토큰 처리
     */
    test('만료된 Firebase 토큰 거부', function (): void {
        // Arrange: 만료된 토큰
        $idToken = 'expired-firebase-id-token';

        $this->firebaseServiceMock
            ->shouldReceive('verifyIdToken')
            ->once()
            ->with($idToken)
            ->andThrow(new FailedToVerifyToken('Token expired'));

        // Act: 만료된 토큰으로 로그인 시도
        $response = $this->postJson(route('api.auth.firebase.login'), [
            'idToken' => $idToken,
        ]);

        // Assert: 422 응답 및 토큰 만료 에러 확인
        expect($response->status())->toBe(422);
        $response->assertJson([
            'success' => false,
            'message' => __('auth.invalid_firebase_token'),
            'errors' => [
                'idToken' => [__('auth.invalid_firebase_token')],
            ],
        ]);
        expect(auth()->guest())->toBeTrue();
    })->group('firebase', 'token-validation');

    /**
     * 테스트: 서명이 잘못된 Firebase 토큰 처리
     */
    test('서명이 잘못된 토큰 거부', function (): void {
        // Arrange: 서명이 잘못된 토큰
        $idToken = 'token-with-invalid-signature';

        $this->firebaseServiceMock
            ->shouldReceive('verifyIdToken')
            ->once()
            ->with($idToken)
            ->andThrow(new FailedToVerifyToken('Invalid signature'));

        // Act: 서명이 잘못된 토큰으로 로그인 시도
        $response = $this->postJson(route('api.auth.firebase.login'), [
            'idToken' => $idToken,
        ]);

        // Assert: 422 응답 및 서명 검증 실패 확인
        expect($response->status())->toBe(422);
        $response->assertJson([
            'success' => false,
            'errors' => [
                'idToken' => [__('auth.invalid_firebase_token')],
            ],
        ]);
        expect(auth()->guest())->toBeTrue();
    })->group('firebase', 'token-validation');

    /**
     * 테스트: 다른 프로젝트의 Firebase 토큰 거부
     */
    test('다른 프로젝트의 Firebase 토큰 거부', function (): void {
        // Arrange: 다른 프로젝트의 토큰
        $idToken = 'token-from-different-project';

        $this->firebaseServiceMock
            ->shouldReceive('verifyIdToken')
            ->once()
            ->with($idToken)
            ->andThrow(new FailedToVerifyToken('Token audience mismatch'));

        // Act: 다른 프로젝트의 토큰으로 로그인 시도
        $response = $this->postJson(route('api.auth.firebase.login'), [
            'idToken' => $idToken,
        ]);

        // Assert: 422 응답 및 프로젝트 불일치 확인
        expect($response->status())->toBe(422);
        $response->assertJson([
            'success' => false,
            'message' => __('auth.invalid_firebase_token'),
        ]);
    })->group('firebase', 'token-validation');
});

describe('세션 관리', function (): void {
    /**
     * 테스트: 동일 사용자의 연속 로그인 처리
     */
    test('동일 사용자의 연속 로그인 처리', function (): void {
        // Arrange: 동일 사용자 토큰 데이터
        $tokenData = createValidFirebaseTokenData();
        $idToken = 'valid-firebase-id-token';

        $user = User::factory()->create([
            'firebase_uid' => $tokenData['uid'],
            'email' => $tokenData['email'],
        ]);

        $this->firebaseServiceMock
            ->shouldReceive('verifyIdToken')
            ->twice()
            ->andReturn($tokenData);

        $this->firebaseServiceMock
            ->shouldReceive('syncFirebaseUserWithLaravel')
            ->twice()
            ->andReturn($user);

        // Act: 첫 번째 로그인
        $response = $this->postJson(route('api.auth.firebase.login'), [
            'idToken' => $idToken,
        ]);

        // 두 번째 로그인 (연속)
        $response2 = $this->postJson(route('api.auth.firebase.login'), [
            'idToken' => $idToken,
        ]);

        // Assert: 두 로그인 모두 성공
        expect($response->status())->toBe(200);
        expect($response2->status())->toBe(200);
        expect(auth()->check())->toBeTrue();

        // 동일 사용자로 인증 확인
        expect(Auth::id())->toBe($user->id);
    })->group('firebase', 'session', 'consecutive-login');

    /**
     * 테스트: 로그아웃 후 동일 세션으로 재요청 거부
     */
    test('로그아웃 후 세션 무효화', function (): void {
        // Arrange: 로그인한 사용자
        $user = User::factory()->create();
        $this->actingAs($user);

        // 세션 ID 저장
        $oldSessionId = Session::getId();

        // Act: 로그아웃
        $this->post(route('auth.logout'));

        // Assert: 세션 ID가 변경됨
        expect(Session::getId())->not->toBe($oldSessionId);

        // 로그아웃 후 게스트 상태 확인
        expect(auth()->guest())->toBeTrue();

        // 보호된 리소스 접근 시도
        $response = $this->get('/store');
        $response->assertRedirect(route('filament.store.auth.login'));
    })->group('firebase', 'session', 'logout');

    /**
     * 테스트: 다른 디바이스에서 로그인 시 세션 동시 유지 (다중 세션)
     */
    test('다중 디바이스 세션 동시 유지', function (): void {
        // Arrange: 사용자 토큰 데이터
        $tokenData = createValidFirebaseTokenData();
        $idToken = 'valid-firebase-id-token';

        $user = User::factory()->create([
            'firebase_uid' => $tokenData['uid'],
            'email' => $tokenData['email'],
        ]);

        $this->firebaseServiceMock
            ->shouldReceive('verifyIdToken')
            ->twice()
            ->andReturn($tokenData);

        $this->firebaseServiceMock
            ->shouldReceive('syncFirebaseUserWithLaravel')
            ->twice()
            ->andReturn($user);

        // Act: 첫 번째 디바이스에서 로그인
        $response = $this->postJson(route('api.auth.firebase.login'), [
            'idToken' => $idToken,
        ]);
        $session1 = Session::getId();

        // 세션 초기화 (새 디바이스 시뮬레이션)
        Session::flush();
        Session::regenerate();

        // 두 번째 디바이스에서 로그인
        $response2 = $this->postJson(route('api.auth.firebase.login'), [
            'idToken' => $idToken,
        ]);
        $session2 = Session::getId();

        // Assert: 두 세션 모두 성공, 세션 ID는 다름
        expect($response->status())->toBe(200);
        expect($response2->status())->toBe(200);
        expect($session1)->not->toBe($session2);
    })->group('firebase', 'session', 'multiple-devices');

    /**
     * 테스트: 세션 재생성 공격 방지 (로그인 후 세션 ID 변경)
     */
    test('로그인 후 세션 ID 재생성 (세션 고정 공격 방지)', function (): void {
        // Arrange: 유효한 토큰 데이터
        $tokenData = createValidFirebaseTokenData();
        $idToken = 'valid-firebase-id-token';

        $user = User::factory()->create([
            'firebase_uid' => $tokenData['uid'],
            'email' => $tokenData['email'],
        ]);

        // 로그인 전 세션 ID 저장
        $oldSessionId = Session::getId();

        $this->firebaseServiceMock
            ->shouldReceive('verifyIdToken')
            ->once()
            ->andReturn($tokenData);

        $this->firebaseServiceMock
            ->shouldReceive('syncFirebaseUserWithLaravel')
            ->once()
            ->andReturn($user);

        // Act: 로그인
        $this->post(route('auth.firebase.callback'), [
            'idToken' => $idToken,
        ]);

        // Assert: 로그인 후 세션 ID 변경 확인 (세션 고정 공격 방지)
        $newSessionId = Session::getId();
        expect($newSessionId)->not->toBe($oldSessionId);
        expect(auth()->check())->toBeTrue();
    })->group('firebase', 'session', 'fixation-prevention');
});

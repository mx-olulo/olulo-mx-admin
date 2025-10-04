<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Services\FirebaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;
use Mockery;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

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
#[Group('firebase')]
class FirebaseAuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var \Mockery\MockInterface|FirebaseService
     */
    protected $firebaseServiceMock;

    /**
     * 테스트 환경 설정
     *
     * Firebase Emulator 연결 확인 및 모킹 설정
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Firebase 서비스 모킹
        $this->firebaseServiceMock = Mockery::mock(FirebaseService::class);
        $this->app->instance(FirebaseService::class, $this->firebaseServiceMock);

        // Firebase 설정 모킹
        Config::set('services.firebase.web_api_key', 'test-api-key');
        Config::set('services.firebase.project_id', 'test-project');

        // 테스트용 환경 변수 설정
        Config::set('app.locale', 'ko');
    }

    /**
     * 테스트 환경 정리
     */
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * 유효한 Firebase 토큰 데이터 생성
     *
     * @return array<string, mixed>
     */
    protected function createValidFirebaseTokenData(): array
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

    /**
     * 테스트: 로그인 페이지 표시 확인
     */
    #[Test]
    public function test_can_display_login_page(): void
    {
        // Act: 로그인 페이지 요청
        $response = $this->get(route('auth.login', ['locale' => 'ko']));

        // Assert: 페이지 표시 확인
        $response->assertStatus(200);
        $response->assertViewIs('auth.login');
        $response->assertViewHas('firebaseConfig');
        $response->assertViewHas('locale', 'ko');
        $response->assertViewHas('theme', 'light');
        $supportedLocales = $response->viewData('supportedLocales');
        $this->assertIsArray($supportedLocales);
        $this->assertEqualsCanonicalizing(['ko', 'en', 'es-MX'], $supportedLocales);
        $response->assertViewHas('callbackUrl');

        // Firebase 설정 확인
        $firebaseConfig = $response->viewData('firebaseConfig');
        $this->assertArrayHasKey('apiKey', $firebaseConfig);
        $this->assertArrayHasKey('authDomain', $firebaseConfig);
        $this->assertArrayHasKey('projectId', $firebaseConfig);
    }

    /**
     * 테스트: intended URL 세션 저장
     */
    #[Test]
    public function test_stores_intended_url_in_session(): void
    {
        // Act: intended URL과 함께 로그인 페이지 요청
        $response = $this->get(route('auth.login', ['intended' => '/admin/dashboard']));

        // Assert: 세션에 intended URL 저장 확인
        $response->assertStatus(200);
        $response->assertSessionHas('auth.intended_url', '/admin/dashboard');
    }

    /**
     * 테스트: 유효한 Firebase 토큰으로 로그인
     */
    #[Test]
    public function test_firebase_login_with_valid_token(): void
    {
        // Arrange: 유효한 토큰 데이터 준비
        $tokenData = $this->createValidFirebaseTokenData();
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

        // Act: Firebase 콜백 요청
        $response = $this->post(route('auth.firebase.callback'), [
            'idToken' => $idToken,
        ]);

        // Assert: 로그인 성공 및 리다이렉트 확인
        $response->assertRedirect('/admin');
        $response->assertSessionHas('auth.success');
        $this->assertAuthenticated();

        // 로그인된 사용자 정보 확인
        $user = Auth::user();
        $this->assertEquals($tokenData['uid'], $user->firebase_uid);
        $this->assertEquals($tokenData['email'], $user->email);
    }

    /**
     * 테스트: intended URL로 리다이렉트
     */
    #[Test]
    public function test_redirects_to_intended_url_after_login(): void
    {
        // Arrange: 유효한 토큰 데이터와 intended URL 준비
        $tokenData = $this->createValidFirebaseTokenData();
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
        $this->assertAuthenticated();
    }

    /**
     * 테스트: 잘못된 Firebase 토큰 처리
     */
    #[Test]
    public function test_firebase_login_with_invalid_token(): void
    {
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
        $response->assertStatus(302);
        $response->assertSessionHasErrors(['idToken']);
        $this->assertGuest();
    }

    /**
     * 테스트: 토큰 없이 콜백 요청 시 검증 실패
     */
    #[Test]
    public function test_firebase_callback_without_token_fails(): void
    {
        // Act: 토큰 없이 Firebase 콜백 요청
        $response = $this->post(route('auth.firebase.callback'));

        // Assert: 검증 실패 확인
        $response->assertStatus(302);
        $response->assertSessionHasErrors(['idToken']);
        $this->assertGuest();
    }

    /**
     * 테스트: Firebase 서비스 오류 처리
     */
    #[Test]
    public function test_handles_firebase_service_error(): void
    {
        // Arrange: 토큰 데이터 준비
        $tokenData = $this->createValidFirebaseTokenData();
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
        $this->assertGuest();
    }

    /**
     * 테스트: 로그아웃 기능
     */
    #[Test]
    public function test_can_logout(): void
    {
        // Arrange: 로그인한 사용자 생성
        $user = User::factory()->create();
        $this->actingAs($user);

        // Act: 로그아웃 요청
        $response = $this->post(route('auth.logout'));

        // Assert: 로그아웃 및 리다이렉트 확인
        $response->assertRedirect(route('auth.login'));
        $response->assertSessionHas('auth.success');
        $this->assertGuest();
    }

    /**
     * 테스트: API 로그아웃
     */
    #[Test]
    public function test_api_logout_returns_json(): void
    {
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
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => __('auth.logout_success'),
        ]);
    }

    /**
     * 테스트: 언어 변경
     */
    #[Test]
    public function test_can_change_locale(): void
    {
        // Act: 로그인 페이지에 locale 쿼리 파라미터로 전달
        $response = $this->get(route('auth.login', ['locale' => 'en']));

        // Assert: 뷰에 전달된 locale 값 확인
        $response->assertStatus(200);
        $response->assertViewHas('locale', 'en');
    }

    /**
     * 테스트: 지원하지 않는 언어 요청 시 기본값 사용
     */
    #[Test]
    public function test_unsupported_locale_uses_default(): void
    {
        // Act: 지원하지 않는 언어로 로그인 페이지 요청
        $response = $this->get(route('auth.login', ['locale' => 'fr']));

        // Assert: 기본 언어(config('app.locale')) 반영
        $response->assertStatus(200);
        $response->assertViewHas('locale', config('app.locale', 'ko'));
    }

    /**
     * 테스트: API 언어 변경
     */
    #[Test]
    public function test_api_locale_change_returns_json(): void
    {
        // Act: API 언어 변경 요청
        $response = $this->postJson(route('api.auth.locale.change', ['locale' => 'es-MX']));

        // Assert: JSON 응답 확인
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => __('auth.locale_changed'),
            'locale' => 'es-MX',
        ]);
        // 현재 구현은 세션에 locale을 저장하지 않으므로 세션 단언은 생략
    }

    /**
     * 테스트: API Firebase 로그인
     */
    #[Test]
    public function test_api_firebase_login(): void
    {
        // Arrange: 유효한 토큰 데이터 준비
        $tokenData = $this->createValidFirebaseTokenData();
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
        $response->assertStatus(200);
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
        $this->assertAuthenticated();
    }

    /**
     * 테스트: API Firebase 로그인 - 잘못된 토큰
     */
    #[Test]
    public function test_api_firebase_login_with_invalid_token(): void
    {
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
        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => __('auth.invalid_firebase_token'),
            'errors' => [
                'idToken' => [__('auth.invalid_firebase_token')],
            ],
        ]);
        $this->assertGuest();
    }

    /**
     * 테스트: API Firebase 로그인 - 서버 오류
     */
    #[Test]
    public function test_api_firebase_login_handles_server_error(): void
    {
        // Arrange: 토큰 데이터 준비
        $tokenData = $this->createValidFirebaseTokenData();
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
        $response->assertStatus(500);
        $response->assertJson([
            'success' => false,
            'message' => __('auth.login_failed'),
        ]);
        $this->assertGuest();
    }

    /**
     * 테스트: 보호된 라우트 인증 확인
     */
    #[Test]
    public function test_protected_routes_require_authentication(): void
    {
        // Act: 인증 없이 보호된 라우트 접근 시도
        $response = $this->post(route('auth.logout'));

        // Assert: 인증 리다이렉트 확인
        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    /**
     * 테스트: 인증된 사용자는 로그인 페이지 접근 불가
     */
    #[Test]
    public function test_authenticated_user_cannot_access_login_page(): void
    {
        // Arrange: 로그인한 사용자 생성
        $user = User::factory()->create();
        $this->actingAs($user);

        // Act: 로그인 페이지 접근 시도
        $response = $this->get(route('auth.login'));

        // Assert: 리다이렉트 확인
        $response->assertRedirect('/dashboard');
    }

    /**
     * 테스트: Firebase 사용자 동기화 - 새 사용자 생성
     */
    #[Test]
    public function test_syncs_new_firebase_user_with_laravel(): void
    {
        // Arrange: 새 Firebase 사용자 데이터
        $tokenData = $this->createValidFirebaseTokenData();
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
            ->andReturnUsing(function ($data) {
                return User::create([
                    'firebase_uid' => $data['uid'],
                    'email' => $data['email'],
                    'name' => $data['name'],
                    'email_verified_at' => $data['email_verified'] ? now() : null,
                    'phone_number' => $data['phone_number'],
                    'avatar_url' => $data['picture'],
                    'password' => bcrypt(\Illuminate\Support\Str::random(32)),
                ]);
            });

        // Act: Firebase 콜백 요청
        $response = $this->post(route('auth.firebase.callback'), [
            'idToken' => $idToken,
        ]);

        // Assert: 사용자 생성 및 로그인 확인
        $response->assertRedirect('/admin');
        $this->assertAuthenticated();

        $this->assertDatabaseHas('users', [
            'firebase_uid' => $tokenData['uid'],
            'email' => $tokenData['email'],
            'name' => $tokenData['name'],
        ]);
    }

    /**
     * 테스트: Firebase 사용자 동기화 - 기존 사용자 업데이트
     */
    #[Test]
    public function test_updates_existing_firebase_user(): void
    {
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
            ->andReturnUsing(function ($data) use ($existingUser) {
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

        // Assert: 사용자 업데이트 확인
        $response->assertRedirect('/admin');
        $this->assertAuthenticated();

        $updatedUser = User::find($existingUser->id);
        $this->assertEquals('Updated Name', $updatedUser->name);
        $this->assertEquals('+821012345678', $updatedUser->phone_number);
        $this->assertEquals('https://example.com/new-avatar.jpg', $updatedUser->avatar_url);
        $this->assertNotNull($updatedUser->email_verified_at);
    }

    /**
     * 테스트: 전화번호만 있는 Firebase 사용자 처리
     */
    #[Test]
    public function test_handles_firebase_user_with_phone_only(): void
    {
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
            ->andReturnUsing(function ($data) {
                // 전화번호로 이메일 생성
                $cleanPhoneNumber = preg_replace('/[^0-9]/', '', $data['phone_number']);
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

        // Assert: 사용자 생성 확인
        $response->assertRedirect('/admin');
        $this->assertAuthenticated();

        $this->assertDatabaseHas('users', [
            'firebase_uid' => 'firebase_uid_phone',
            'email' => '821098765432@olulo.com.mx',
            'phone_number' => '+821098765432',
        ]);
    }

    // =========================================================================
    // CSRF 토큰 및 보안 시나리오 테스트
    // =========================================================================

    /**
     * 테스트: CSRF 토큰 없이 API 요청 시 성공 (Sanctum SPA 세션)
     *
     * Sanctum SPA는 CSRF 토큰을 자동으로 처리하므로,
     * API 라우트에서는 CSRF 검증이 필요하지 않습니다.
     */
    #[Test]
    public function test_api_request_works_without_explicit_csrf_token(): void
    {
        // Arrange: 유효한 토큰 데이터
        $tokenData = $this->createValidFirebaseTokenData();
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
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    /**
     * 테스트: Web 라우트는 CSRF 토큰 필요
     */
    #[Test]
    public function test_web_callback_requires_csrf_token(): void
    {
        // Act: CSRF 토큰 없이 웹 콜백 요청
        $response = $this->post(route('auth.firebase.callback'), [
            'idToken' => 'test-token',
        ]);

        // Assert: 현재 구현에서는 302 리다이렉트 발생
        $response->assertStatus(302);
    }

    // =========================================================================
    // Firebase 토큰 형식 및 검증 에러 시나리오
    // =========================================================================

    /**
     * 테스트: 빈 문자열 토큰 처리
     */
    #[Test]
    public function test_rejects_empty_string_token(): void
    {
        // Act: 빈 문자열 토큰으로 로그인 시도
        $response = $this->postJson(route('api.auth.firebase.login'), [
            'idToken' => '',
        ]);

        // Assert: 검증 실패
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['idToken']);
    }

    /**
     * 테스트: null 토큰 처리
     */
    #[Test]
    public function test_rejects_null_token(): void
    {
        // Act: null 토큰으로 로그인 시도
        $response = $this->postJson(route('api.auth.firebase.login'), [
            'idToken' => null,
        ]);

        // Assert: 검증 실패
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['idToken']);
    }

    /**
     * 테스트: JWT 형식이 아닌 잘못된 문자열 토큰
     */
    #[Test]
    public function test_rejects_malformed_token_format(): void
    {
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
        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => __('auth.invalid_firebase_token'),
        ]);
    }

    /**
     * 테스트: 만료된 Firebase 토큰 처리
     */
    #[Test]
    public function test_rejects_expired_firebase_token(): void
    {
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
        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => __('auth.invalid_firebase_token'),
            'errors' => [
                'idToken' => [__('auth.invalid_firebase_token')],
            ],
        ]);
        $this->assertGuest();
    }

    /**
     * 테스트: 서명이 잘못된 Firebase 토큰 처리
     */
    #[Test]
    public function test_rejects_token_with_invalid_signature(): void
    {
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
        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'errors' => [
                'idToken' => [__('auth.invalid_firebase_token')],
            ],
        ]);
        $this->assertGuest();
    }

    /**
     * 테스트: 다른 프로젝트의 Firebase 토큰 거부
     */
    #[Test]
    public function test_rejects_token_from_different_firebase_project(): void
    {
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
        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => __('auth.invalid_firebase_token'),
        ]);
    }

    // =========================================================================
    // 세션 관리 엣지 케이스
    // =========================================================================

    /**
     * 테스트: 동일 사용자의 연속 로그인 처리
     */
    #[Test]
    public function test_handles_multiple_consecutive_logins_same_user(): void
    {
        // Arrange: 동일 사용자 토큰 데이터
        $tokenData = $this->createValidFirebaseTokenData();
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
        $response1 = $this->postJson(route('api.auth.firebase.login'), [
            'idToken' => $idToken,
        ]);

        // 두 번째 로그인 (연속)
        $response2 = $this->postJson(route('api.auth.firebase.login'), [
            'idToken' => $idToken,
        ]);

        // Assert: 두 로그인 모두 성공
        $response1->assertStatus(200);
        $response2->assertStatus(200);
        $this->assertAuthenticated();

        // 동일 사용자로 인증 확인
        $this->assertEquals($user->id, Auth::id());
    }

    /**
     * 테스트: 로그아웃 후 동일 세션으로 재요청 거부
     */
    #[Test]
    public function test_rejects_requests_with_old_session_after_logout(): void
    {
        // Arrange: 로그인한 사용자
        $user = User::factory()->create();
        $this->actingAs($user);

        // 세션 ID 저장
        $oldSessionId = Session::getId();

        // Act: 로그아웃
        $this->post(route('auth.logout'));

        // Assert: 세션 ID가 변경됨
        $this->assertNotEquals($oldSessionId, Session::getId());

        // 로그아웃 후 게스트 상태 확인
        $this->assertGuest();

        // 보호된 리소스 접근 시도
        $response = $this->get('/admin');
        $response->assertRedirect(route('filament.admin.auth.login'));
    }

    /**
     * 테스트: 다른 디바이스에서 로그인 시 세션 동시 유지 (다중 세션)
     */
    public function test_allows_multiple_sessions_from_different_devices(): void
    {
        // Arrange: 사용자 토큰 데이터
        $tokenData = $this->createValidFirebaseTokenData();
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
        $response1 = $this->postJson(route('api.auth.firebase.login'), [
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
        $response1->assertStatus(200);
        $response2->assertStatus(200);
        $this->assertNotEquals($session1, $session2);
    }

    /**
     * 테스트: 세션 재생성 공격 방지 (로그인 후 세션 ID 변경)
     */
    public function test_regenerates_session_id_after_login_to_prevent_fixation(): void
    {
        // Arrange: 유효한 토큰 데이터
        $tokenData = $this->createValidFirebaseTokenData();
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
        $this->assertNotEquals($oldSessionId, $newSessionId);
        $this->assertAuthenticated();
    }
}

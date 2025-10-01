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
 *
 * @group firebase
 */
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
     *
     * @test
     */
    public function test_can_display_login_page(): void
    {
        // Act: 로그인 페이지 요청
        $response = $this->get(route('auth.login'));

        // Assert: 페이지 표시 확인
        $response->assertStatus(200);
        $response->assertViewIs('auth.login');
        $response->assertViewHas('firebaseConfig');
        $response->assertViewHas('locale', 'ko');
        $response->assertViewHas('theme', 'light');
        $response->assertViewHas('supportedLocales', ['ko', 'en', 'es-MX']);
        $response->assertViewHas('callbackUrl');

        // Firebase 설정 확인
        $firebaseConfig = $response->viewData('firebaseConfig');
        $this->assertArrayHasKey('apiKey', $firebaseConfig);
        $this->assertArrayHasKey('authDomain', $firebaseConfig);
        $this->assertArrayHasKey('projectId', $firebaseConfig);
    }

    /**
     * 테스트: intended URL 세션 저장
     *
     * @test
     */
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
     *
     * @test
     */
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
     *
     * @test
     */
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
     *
     * @test
     */
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
     *
     * @test
     */
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
     *
     * @test
     */
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
     *
     * @test
     */
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
     *
     * @test
     */
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
     *
     * @test
     */
    public function test_can_change_locale(): void
    {
        // Act: 언어 변경 요청 (한국어 → 영어)
        $response = $this->post(route('auth.locale.change', ['locale' => 'en']));

        // Assert: 언어 변경 및 세션 저장 확인
        $response->assertRedirect();
        $response->assertSessionHas('locale', 'en');
        $response->assertSessionHas('auth.success');
    }

    /**
     * 테스트: 지원하지 않는 언어 요청 시 기본값 사용
     *
     * @test
     */
    public function test_unsupported_locale_uses_default(): void
    {
        // Act: 지원하지 않는 언어 변경 요청
        $response = $this->post(route('auth.locale.change', ['locale' => 'fr']));

        // Assert: 기본 언어(ko) 설정 확인
        $response->assertRedirect();
        $response->assertSessionHas('locale', 'ko');
    }

    /**
     * 테스트: API 언어 변경
     *
     * @test
     */
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
        $this->assertEquals('es-MX', Session::get('locale'));
    }

    /**
     * 테스트: API Firebase 로그인
     *
     * @test
     */
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
     *
     * @test
     */
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
     *
     * @test
     */
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
     *
     * @test
     */
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
     *
     * @test
     */
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
     *
     * @test
     */
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
     *
     * @test
     */
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
     *
     * @test
     */
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
}

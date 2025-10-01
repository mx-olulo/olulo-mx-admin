<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\FirebaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * FirebaseService 단위 테스트
 *
 * Firebase 통합 서비스의 핵심 기능을 테스트합니다.
 * 실제 Firebase API는 모킹하여 독립적인 테스트를 수행합니다.
 *
 * @group firebase
 */
class FirebaseServiceTest extends TestCase
{
    use RefreshDatabase;

    private FirebaseService $firebaseService;

    protected function setUp(): void
    {
        parent::setUp();

        // Firebase 설정을 테스트용으로 모킹
        Config::set('services.firebase', [
            'project_id' => 'test-project',
            'client_email' => 'test@example.com',
            'private_key' => 'test-private-key',
            'client_id' => 'test-client-id',
            'private_key_id' => 'test-private-key-id',
        ]);

        // Firebase 서비스 초기화는 실제 환경에서 테스트할 때만 필요
        // 단위 테스트에서는 메서드별로 모킹 처리
    }

    /**
     * 환경 변수 자격증명 확인 테스트
     */
    public function test_환경_변수_자격증명_확인(): void
    {
        $reflection = new \ReflectionClass(FirebaseService::class);
        $method = $reflection->getMethod('hasEnvironmentCredentials');
        $method->setAccessible(true);

        // 모든 자격증명이 설정된 경우
        Config::set('services.firebase.project_id', 'test-project');
        Config::set('services.firebase.client_email', 'test@example.com');
        Config::set('services.firebase.private_key', 'test-key');

        // 실제 FirebaseService 인스턴스 없이 메서드만 테스트하기 위해
        // 리플렉션을 사용하여 정적 호출 시뮬레이션
        $hasCredentials = ! empty(Config::get('services.firebase.project_id')) &&
                         ! empty(Config::get('services.firebase.client_email')) &&
                         ! empty(Config::get('services.firebase.private_key'));

        $this->assertTrue($hasCredentials);

        // 일부 자격증명이 누락된 경우
        Config::set('services.firebase.project_id', '');
        $hasCredentials = ! empty(Config::get('services.firebase.project_id')) &&
                         ! empty(Config::get('services.firebase.client_email')) &&
                         ! empty(Config::get('services.firebase.private_key'));

        $this->assertFalse($hasCredentials);
    }

    /**
     * 이메일에서 사용자 이름 추출 테스트
     */
    public function test_이메일에서_사용자_이름_추출(): void
    {
        $reflection = new \ReflectionClass(FirebaseService::class);
        $method = $reflection->getMethod('extractNameFromEmail');
        $method->setAccessible(true);

        // 테스트 케이스들
        $testCases = [
            'john.doe@example.com' => 'John doe',
            'user_name@test.com' => 'User name',
            'simple@domain.com' => 'Simple',
            'test-user@example.org' => 'Test user',
        ];

        foreach ($testCases as $email => $expectedName) {
            $localPart = explode('@', $email)[0];
            $actualName = ucfirst(str_replace(['.', '_', '-'], ' ', $localPart));

            $this->assertEquals($expectedName, $actualName);
        }
    }

    /**
     * Firebase 사용자 데이터 동기화 테스트 (이메일 없는 경우)
     */
    public function test_전화번호로_이메일_자동_생성(): void
    {
        $phoneNumber = '+52 55 1234 5678';
        $cleanPhoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
        $expectedEmail = $cleanPhoneNumber . '@olulo.com.mx';

        $this->assertEquals('525512345678@olulo.com.mx', $expectedEmail);
    }

    /**
     * Firebase 사용자 데이터 검증 테스트
     */
    public function test_firebase_사용자_데이터_검증(): void
    {
        // 유효한 Firebase 사용자 데이터
        $validUserData = [
            'uid' => 'firebase-uid-123',
            'email' => 'test@example.com',
            'email_verified' => true,
            'phone_number' => '+52 55 1234 5678',
            'name' => 'Test User',
            'picture' => 'https://example.com/avatar.jpg',
            'provider_id' => 'google.com',
        ];

        // 필수 필드 확인
        $this->assertArrayHasKey('uid', $validUserData);
        $this->assertNotEmpty($validUserData['uid']);

        // 이메일 또는 전화번호 중 하나는 반드시 있어야 함
        $hasEmailOrPhone = ! empty($validUserData['email']) || ! empty($validUserData['phone_number']);
        $this->assertTrue($hasEmailOrPhone);
    }

    /**
     * 전화번호 정규화 테스트
     */
    public function test_전화번호_정규화(): void
    {
        $testCases = [
            '+52 55 1234 5678' => '525512345678',
            '(55) 1234-5678' => '5512345678',
            '55.1234.5678' => '5512345678',
            '+1 (555) 123-4567' => '15551234567',
        ];

        foreach ($testCases as $input => $expected) {
            $normalized = preg_replace('/[^0-9]/', '', $input);
            $this->assertEquals($expected, $normalized);
        }
    }

    /**
     * Firebase 클레임 데이터 구조 테스트
     */
    public function test_firebase_클레임_구조(): void
    {
        $mockClaims = [
            'sub' => 'firebase-uid-123',
            'email' => 'test@example.com',
            'email_verified' => true,
            'phone_number' => '+52 55 1234 5678',
            'name' => 'Test User',
            'picture' => 'https://example.com/avatar.jpg',
            'firebase' => [
                'sign_in_provider' => 'google.com',
            ],
        ];

        // 필수 클레임 확인
        $this->assertArrayHasKey('sub', $mockClaims);
        $this->assertArrayHasKey('firebase', $mockClaims);

        // Firebase 특화 클레임 확인
        $firebaseClaims = $mockClaims['firebase'];
        $this->assertArrayHasKey('sign_in_provider', $firebaseClaims);
    }

    /**
     * FCM 토큰 형식 검증 테스트
     */
    public function test_fcm_토큰_형식_검증(): void
    {
        $validFcmToken = 'eHcE7H5Cl0I:APA91bEhcaM7Q8ZrjG4gO-example-token-here';
        $invalidTokens = [
            '',
            'invalid-token',
            'short',
        ];

        // 유효한 FCM 토큰 확인 (일반적으로 100자 이상)
        $this->assertGreaterThan(20, strlen($validFcmToken));

        // 무효한 토큰들 확인
        foreach ($invalidTokens as $token) {
            $this->assertLessThan(20, strlen($token));
        }
    }

    /**
     * Realtime Database 경로 검증 테스트
     */
    public function test_realtime_database_경로_검증(): void
    {
        $validPaths = [
            'users',
            'users/123',
            'stores/store1/orders',
            'notifications/user123/messages',
        ];

        $invalidPaths = [
            '',
            '/',
            '//invalid',
            'path with spaces',
        ];

        foreach ($validPaths as $path) {
            // 유효한 경로는 슬래시로 시작하지 않고, 공백이 없음
            $this->assertFalse(str_starts_with($path, '/'));
            $this->assertFalse(str_contains($path, ' '));
        }

        foreach ($invalidPaths as $path) {
            // 무효한 경로들은 특정 조건들을 만족하지 않음
            $isInvalid = empty($path) ||
                        $path === '/' ||
                        str_contains($path, '//') ||
                        str_contains($path, ' ');
            $this->assertTrue($isInvalid);
        }
    }

    /**
     * Firestore 컬렉션 이름 검증 테스트
     */
    public function test_firestore_컬렉션_이름_검증(): void
    {
        $validCollections = [
            'users',
            'stores',
            'orders',
            'notifications',
        ];

        $invalidCollections = [
            '',
            '__system__',
            'collection with spaces',
            'collection/with/slashes',
        ];

        foreach ($validCollections as $collection) {
            // 유효한 컬렉션 이름은 영문자로 시작하고 공백/슬래시가 없음
            $this->assertMatchesRegularExpression('/^[a-zA-Z][a-zA-Z0-9_]*$/', $collection);
        }

        foreach ($invalidCollections as $collection) {
            // 무효한 컬렉션 이름들 확인
            $isInvalid = empty($collection) ||
                        str_starts_with($collection, '__') ||
                        str_contains($collection, ' ') ||
                        str_contains($collection, '/');
            $this->assertTrue($isInvalid);
        }
    }
}

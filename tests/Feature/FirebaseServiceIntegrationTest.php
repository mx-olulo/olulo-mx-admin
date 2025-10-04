<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Services\FirebaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * FirebaseService 통합 테스트
 *
 * Firebase 서비스의 실제 사용 시나리오를 테스트합니다.
 * 실제 Firebase API 호출 없이 모킹을 통해 통합성을 검증합니다.
 */
#[Group('firebase')]
#[CoversClass(FirebaseService::class)]
class FirebaseServiceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // 테스트 환경 Firebase 설정
        Config::set('services.firebase', [
            'project_id' => 'test-project-id',
            'client_email' => 'test-service@test-project.iam.gserviceaccount.com',
            'private_key' => '-----BEGIN PRIVATE KEY-----\ntest-private-key\n-----END PRIVATE KEY-----',
            'client_id' => 'test-client-id',
            'private_key_id' => 'test-private-key-id',
        ]);
    }

    /**
     * Firebase 서비스 의존성 주입 테스트
     */
    public function test_firebase_서비스_의존성_주입(): void
    {
        // Laravel 컨테이너에서 FirebaseService를 해결할 수 있는지 확인
        $this->assertTrue($this->app->bound(FirebaseService::class));

        // 동일한 인스턴스가 반환되는지 확인 (싱글톤)
        $service1 = $this->app->make(FirebaseService::class);
        $service2 = $this->app->make(FirebaseService::class);

        $this->assertSame($service1, $service2);
        $this->assertInstanceOf(FirebaseService::class, $service1);
    }

    /**
     * Laravel 사용자 동기화 시나리오 테스트
     */
    public function test_firebase_사용자_동기화_시나리오(): void
    {
        // 기존 사용자가 없는 상태에서 Firebase 사용자 데이터로 동기화
        $firebaseUserData = [
            'uid' => 'firebase-test-uid-123',
            'email' => 'test@example.com',
            'email_verified' => true,
            'phone_number' => '+52 55 1234 5678',
            'name' => 'Test User',
            'picture' => 'https://example.com/avatar.jpg',
        ];

        $service = $this->app->make(FirebaseService::class);

        // 새 사용자 생성 시나리오
        $user = $service->syncFirebaseUserWithLaravel($firebaseUserData);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('firebase-test-uid-123', $user->firebase_uid);
        $this->assertEquals('test@example.com', $user->email);
        $this->assertEquals('Test User', $user->name);
        $this->assertEquals('+52 55 1234 5678', $user->phone_number);
        $this->assertEquals('https://example.com/avatar.jpg', $user->avatar_url);
        $this->assertNotNull($user->email_verified_at);

        // 기존 사용자 업데이트 시나리오
        $updatedFirebaseData = [
            'uid' => 'firebase-test-uid-123',
            'email' => 'test@example.com',
            'email_verified' => true,
            'phone_number' => '+52 55 9876 5432',
            'name' => 'Updated Test User',
            'picture' => 'https://example.com/new-avatar.jpg',
        ];

        $updatedUser = $service->syncFirebaseUserWithLaravel($updatedFirebaseData);

        $this->assertEquals($user->id, $updatedUser->id); // 동일한 사용자
        $this->assertEquals('Updated Test User', $updatedUser->name);
        $this->assertEquals('+52 55 9876 5432', $updatedUser->phone_number);
        $this->assertEquals('https://example.com/new-avatar.jpg', $updatedUser->avatar_url);
    }

    /**
     * 전화번호 전용 사용자 동기화 테스트
     */
    public function test_전화번호_전용_사용자_동기화(): void
    {
        // 이메일이 없고 전화번호만 있는 Firebase 사용자
        $firebaseUserData = [
            'uid' => 'firebase-phone-only-123',
            'email' => null,
            'email_verified' => false,
            'phone_number' => '+52 55 1111 2222',
            'name' => 'Phone User',
            'picture' => null,
        ];

        $service = $this->app->make(FirebaseService::class);
        $user = $service->syncFirebaseUserWithLaravel($firebaseUserData);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('firebase-phone-only-123', $user->firebase_uid);
        $this->assertEquals('525511112222@olulo.com.mx', $user->email); // 자동 생성된 이메일
        $this->assertEquals('Phone User', $user->name);
        $this->assertEquals('+52 55 1111 2222', $user->phone_number);
        $this->assertNull($user->email_verified_at); // 이메일 미인증
    }

    /**
     * 이메일에서 이름 추출 테스트
     */
    public function test_이메일에서_이름_추출_동작(): void
    {
        $firebaseUserData = [
            'uid' => 'firebase-no-name-123',
            'email' => 'john.doe@example.com',
            'email_verified' => true,
            'phone_number' => null,
            'name' => null, // 이름이 없는 경우
            'picture' => null,
        ];

        $service = $this->app->make(FirebaseService::class);
        $user = $service->syncFirebaseUserWithLaravel($firebaseUserData);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('John doe', $user->name); // 이메일에서 추출된 이름
    }

    /**
     * 예외 처리 테스트
     */
    public function test_이메일_전화번호_모두_없는_경우_예외(): void
    {
        $firebaseUserData = [
            'uid' => 'firebase-invalid-123',
            'email' => null,
            'email_verified' => false,
            'phone_number' => null,
            'name' => 'Invalid User',
            'picture' => null,
        ];

        $service = $this->app->make(FirebaseService::class);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('이메일 또는 전화번호가 필요합니다.');

        $service->syncFirebaseUserWithLaravel($firebaseUserData);
    }

    /**
     * User 모델의 Firebase 관련 메서드 테스트
     */
    public function test_user_모델_firebase_메서드(): void
    {
        $user = User::factory()->create([
            'firebase_uid' => 'test-firebase-uid',
            'firebase_claims' => [
                'role' => 'customer',
                'store_id' => 'store123',
            ],
        ]);

        // Firebase UID로 사용자 찾기
        $foundUser = User::findByFirebaseUid('test-firebase-uid');
        $this->assertNotNull($foundUser);
        $this->assertEquals($user->id, $foundUser->id);

        // Firebase 사용자 여부 확인
        $this->assertTrue($user->isFirebaseUser());

        // Firebase 클레임 가져오기
        $claims = $user->getFirebaseClaim();
        $this->assertIsArray($claims);
        $this->assertEquals('customer', $claims['role']);
        $this->assertEquals('store123', $claims['store_id']);

        // 특정 클레임 가져오기
        $role = $user->getFirebaseClaim('role');
        $this->assertEquals('customer', $role);

        // 존재하지 않는 클레임
        $nonExistent = $user->getFirebaseClaim('non_existent');
        $this->assertNull($nonExistent);
    }

    /**
     * Firebase 미사용 사용자 테스트
     */
    public function test_firebase_미사용_사용자(): void
    {
        $user = User::factory()->create([
            'firebase_uid' => null,
            'firebase_claims' => null,
        ]);

        $this->assertFalse($user->isFirebaseUser());
        $this->assertEmpty($user->getFirebaseClaim());

        $notFound = User::findByFirebaseUid('non-existent-uid');
        $this->assertNull($notFound);
    }
}

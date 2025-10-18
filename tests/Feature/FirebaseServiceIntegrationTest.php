<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\FirebaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;

/**
 * FirebaseService 통합 테스트
 *
 * Firebase 서비스의 실제 사용 시나리오를 테스트합니다.
 * 실제 Firebase API 호출 없이 모킹을 통해 통합성을 검증합니다.
 */
uses(RefreshDatabase::class);

beforeEach(function (): void {
    // 테스트 환경 Firebase 설정
    Config::set('services.firebase', [
        'project_id' => 'test-project-id',
        'client_email' => 'test-service@test-project.iam.gserviceaccount.com',
        'private_key' => '-----BEGIN PRIVATE KEY-----\ntest-private-key\n-----END PRIVATE KEY-----',
        'client_id' => 'test-client-id',
        'private_key_id' => 'test-private-key-id',
    ]);
});

describe('Firebase Service Dependency Injection', function (): void {
    /**
     * Firebase 서비스 의존성 주입 테스트
     */
    test('firebase 서비스 의존성 주입', function (): void {
        // Laravel 컨테이너에서 FirebaseService를 해결할 수 있는지 확인
        expect($this->app->bound(FirebaseService::class))->toBeTrue();

        // 동일한 인스턴스가 반환되는지 확인 (싱글톤)
        $firebaseService = $this->app->make(FirebaseService::class);
        $service2 = $this->app->make(FirebaseService::class);

        expect($firebaseService)->toBe($service2);
        expect($firebaseService)->toBeInstanceOf(FirebaseService::class);
    })->group('firebase', 'dependency-injection');
});

describe('Firebase User Synchronization', function (): void {
    /**
     * Laravel 사용자 동기화 시나리오 테스트
     */
    test('firebase 사용자 동기화 시나리오', function (): void {
        // 기존 사용자가 없는 상태에서 Firebase 사용자 데이터로 동기화
        $firebaseUserData = [
            'uid' => 'firebase-test-uid-123',
            'email' => 'test@example.com',
            'email_verified' => true,
            'phone_number' => '+52 55 1234 5678',
            'name' => 'Test User',
            'picture' => 'https://example.com/avatar.jpg',
        ];

        $firebaseService = $this->app->make(FirebaseService::class);

        // 새 사용자 생성 시나리오
        $user = $firebaseService->syncFirebaseUserWithLaravel($firebaseUserData);

        expect($user)->toBeInstanceOf(User::class);
        expect($user->firebase_uid)->toBe('firebase-test-uid-123');
        expect($user->email)->toBe('test@example.com');
        expect($user->name)->toBe('Test User');
        expect($user->phone_number)->toBe('+52 55 1234 5678');
        expect($user->avatar_url)->toBe('https://example.com/avatar.jpg');
        expect($user->email_verified_at)->not->toBeNull();

        // 기존 사용자 업데이트 시나리오
        $updatedFirebaseData = [
            'uid' => 'firebase-test-uid-123',
            'email' => 'test@example.com',
            'email_verified' => true,
            'phone_number' => '+52 55 9876 5432',
            'name' => 'Updated Test User',
            'picture' => 'https://example.com/new-avatar.jpg',
        ];

        $updatedUser = $firebaseService->syncFirebaseUserWithLaravel($updatedFirebaseData);

        expect($updatedUser->id)->toBe($user->id); // 동일한 사용자
        expect($updatedUser->name)->toBe('Updated Test User');
        expect($updatedUser->phone_number)->toBe('+52 55 9876 5432');
        expect($updatedUser->avatar_url)->toBe('https://example.com/new-avatar.jpg');
    })->group('firebase', 'user-sync');

    /**
     * 전화번호 전용 사용자 동기화 테스트
     */
    test('전화번호 전용 사용자 동기화', function (): void {
        // 이메일이 없고 전화번호만 있는 Firebase 사용자
        $firebaseUserData = [
            'uid' => 'firebase-phone-only-123',
            'email' => null,
            'email_verified' => false,
            'phone_number' => '+52 55 1111 2222',
            'name' => 'Phone User',
            'picture' => null,
        ];

        $firebaseService = $this->app->make(FirebaseService::class);
        $user = $firebaseService->syncFirebaseUserWithLaravel($firebaseUserData);

        expect($user)->toBeInstanceOf(User::class);
        expect($user->firebase_uid)->toBe('firebase-phone-only-123');
        expect($user->email)->toBe('525511112222@olulo.com.mx'); // 자동 생성된 이메일
        expect($user->name)->toBe('Phone User');
        expect($user->phone_number)->toBe('+52 55 1111 2222');
        expect($user->email_verified_at)->toBeNull(); // 이메일 미인증
    })->group('firebase', 'user-sync', 'phone-only');

    /**
     * 이메일에서 이름 추출 테스트
     */
    test('이메일에서 이름 추출 동작', function (): void {
        $firebaseUserData = [
            'uid' => 'firebase-no-name-123',
            'email' => 'john.doe@example.com',
            'email_verified' => true,
            'phone_number' => null,
            'name' => null, // 이름이 없는 경우
            'picture' => null,
        ];

        $firebaseService = $this->app->make(FirebaseService::class);
        $user = $firebaseService->syncFirebaseUserWithLaravel($firebaseUserData);

        expect($user)->toBeInstanceOf(User::class);
        expect($user->name)->toBe('John doe'); // 이메일에서 추출된 이름
    })->group('firebase', 'user-sync', 'name-extraction');
});

describe('Firebase Service Exception Handling', function (): void {
    /**
     * 예외 처리 테스트
     */
    test('이메일 전화번호 모두 없는 경우 예외', function (): void {
        $firebaseUserData = [
            'uid' => 'firebase-invalid-123',
            'email' => null,
            'email_verified' => false,
            'phone_number' => null,
            'name' => 'Invalid User',
            'picture' => null,
        ];

        $firebaseService = $this->app->make(FirebaseService::class);

        $firebaseService->syncFirebaseUserWithLaravel($firebaseUserData);
    })->throws(Exception::class, '이메일 또는 전화번호가 필요합니다.')
        ->group('firebase', 'exceptions');
});

describe('User Model Firebase Methods', function (): void {
    /**
     * User 모델의 Firebase 관련 메서드 테스트
     */
    test('user 모델 firebase 메서드', function (): void {
        $user = User::factory()->create([
            'firebase_uid' => 'test-firebase-uid',
            'firebase_claims' => [
                'role' => 'customer',
                'store_id' => 'store123',
            ],
        ]);

        // Firebase UID로 사용자 찾기
        $foundUser = User::findByFirebaseUid('test-firebase-uid');
        expect($foundUser)->not->toBeNull();
        expect($foundUser->id)->toBe($user->id);

        // Firebase 사용자 여부 확인
        expect($user->isFirebaseUser())->toBeTrue();

        // Firebase 클레임 가져오기
        $claims = $user->getFirebaseClaim();
        expect($claims)->toBeArray();
        expect($claims['role'])->toBe('customer');
        expect($claims['store_id'])->toBe('store123');

        // 특정 클레임 가져오기
        $role = $user->getFirebaseClaim('role');
        expect($role)->toBe('customer');

        // 존재하지 않는 클레임
        $nonExistent = $user->getFirebaseClaim('non_existent');
        expect($nonExistent)->toBeNull();
    })->group('firebase', 'user-model');

    /**
     * Firebase 미사용 사용자 테스트
     */
    test('firebase 미사용 사용자', function (): void {
        $user = User::factory()->create([
            'firebase_uid' => null,
            'firebase_claims' => null,
        ]);

        expect($user->isFirebaseUser())->toBeFalse();
        expect($user->getFirebaseClaim())->toBeEmpty();

        $notFound = User::findByFirebaseUid('non-existent-uid');
        expect($notFound)->toBeNull();
    })->group('firebase', 'user-model');
});

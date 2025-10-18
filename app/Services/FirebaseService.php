<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Services\Firebase\FirebaseAuthService;
use App\Services\Firebase\FirebaseClientFactory;
use App\Services\Firebase\FirebaseDatabaseService;
use App\Services\Firebase\FirebaseMessagingService;
use Exception;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;
use Kreait\Firebase\Exception\FirebaseException;

/**
 * Firebase 통합 서비스 (파사드 패턴)
 *
 * Firebase의 다양한 기능들을 통합하여 제공하는 파사드 서비스입니다.
 * 내부적으로 기능별로 분할된 서비스들을 조합하여 사용하며,
 * 기존 코드와의 호환성을 보장합니다.
 *
 * 주요 기능:
 * - Firebase ID Token 검증 (FirebaseAuthService)
 * - Firebase User와 Laravel User 동기화 (FirebaseAuthService)
 * - 사용자 생성/업데이트/조회 (FirebaseAuthService)
 * - FCM 푸시 알림 (FirebaseMessagingService)
 * - Realtime Database 연동 (FirebaseDatabaseService)
 *
 * 아키텍처 패턴: 파사드 (Facade Pattern)
 */
class FirebaseService
{
    private readonly FirebaseClientFactory $firebaseClientFactory;

    private ?FirebaseAuthService $firebaseAuthService = null;

    private ?FirebaseMessagingService $firebaseMessagingService = null;

    private ?FirebaseDatabaseService $firebaseDatabaseService = null;

    /**
     * Firebase 서비스 초기화
     */
    public function __construct()
    {
        $this->firebaseClientFactory = new FirebaseClientFactory;
    }

    /**
     * Firebase Auth 서비스 인스턴스 반환 (지연 로딩)
     *
     * @return FirebaseAuthService Firebase 인증 서비스
     */
    private function getAuthService(): FirebaseAuthService
    {
        if (! $this->firebaseAuthService instanceof \App\Services\Firebase\FirebaseAuthService) {
            $this->firebaseAuthService = new FirebaseAuthService($this->firebaseClientFactory);
        }

        return $this->firebaseAuthService;
    }

    /**
     * Firebase Messaging 서비스 인스턴스 반환 (지연 로딩)
     *
     * @return FirebaseMessagingService Firebase 메시징 서비스
     */
    private function getMessagingService(): FirebaseMessagingService
    {
        if (! $this->firebaseMessagingService instanceof \App\Services\Firebase\FirebaseMessagingService) {
            $this->firebaseMessagingService = new FirebaseMessagingService($this->firebaseClientFactory);
        }

        return $this->firebaseMessagingService;
    }

    /**
     * Firebase Database 서비스 인스턴스 반환 (지연 로딩)
     *
     * @return FirebaseDatabaseService Firebase 데이터베이스 서비스
     */
    private function getDatabaseService(): FirebaseDatabaseService
    {
        if (! $this->firebaseDatabaseService instanceof \App\Services\Firebase\FirebaseDatabaseService) {
            $this->firebaseDatabaseService = new FirebaseDatabaseService($this->firebaseClientFactory);
        }

        return $this->firebaseDatabaseService;
    }

    // =========================================================================
    // Firebase Auth 관련 메서드 (FirebaseAuthService로 위임)
    // =========================================================================

    /**
     * Firebase ID Token 검증
     *
     * 클라이언트에서 전송된 Firebase ID Token을 검증하고
     * 검증된 토큰 정보를 반환합니다.
     *
     * @param  string  $idToken  Firebase ID Token
     * @return array<string, mixed> 검증된 토큰 정보
     *
     * @throws FailedToVerifyToken 토큰 검증 실패 시
     */
    public function verifyIdToken(string $idToken): array
    {
        return $this->getAuthService()->verifyIdToken($idToken);
    }

    /**
     * 개발 환경(로컬/에뮬레이터)에서 서명되지 않은 토큰(alg=none)에 대한 관대한 검증 경로
     *
     * 1) 일반 검증 시도
     * 2) 실패하고 APP_ENV=local 이면 토큰의 payload만 디코드하여 최소 정보(sub/email 등) 추출
     *
     * 보안 주의: 이 경로는 로컬 개발에서만 사용해야 하며, 운영 환경에서는 절대 사용 금지
     *
     * @param  string  $idToken  Firebase ID Token
     * @return array<string, mixed> 검증된 토큰 정보
     *
     * @throws FailedToVerifyToken 토큰 검증 실패 시
     */
    public function verifyIdTokenLenient(string $idToken): array
    {
        return $this->getAuthService()->verifyIdTokenLenient($idToken);
    }

    /**
     * Firebase User 정보 조회
     *
     * Firebase UID를 통해 사용자 정보를 조회합니다.
     *
     * @param  string  $uid  Firebase UID
     * @return array<string, mixed>|null 사용자 정보 또는 null
     */
    public function getFirebaseUser(string $uid): ?array
    {
        return $this->getAuthService()->getFirebaseUser($uid);
    }

    /**
     * Firebase User와 Laravel User 동기화
     *
     * Firebase 사용자 정보를 기반으로 Laravel 사용자를 생성하거나 업데이트합니다.
     * 이메일이 없는 경우 전화번호를 사용하여 자동 생성합니다.
     *
     * @param  array<string, mixed>  $firebaseUserData  Firebase 사용자 데이터
     * @return User Laravel 사용자 모델
     *
     * @throws Exception 이메일과 전화번호가 모두 없는 경우
     */
    public function syncFirebaseUserWithLaravel(array $firebaseUserData): User
    {
        return $this->getAuthService()->syncFirebaseUserWithLaravel($firebaseUserData);
    }

    /**
     * Firebase에서 사용자 생성
     *
     * Firebase에 새로운 사용자를 생성합니다.
     *
     * @param  array<string, mixed>  $userData  사용자 데이터
     * @return string 생성된 사용자의 Firebase UID
     *
     * @throws FirebaseException 사용자 생성 실패 시
     */
    public function createFirebaseUser(array $userData): string
    {
        return $this->getAuthService()->createFirebaseUser($userData);
    }

    /**
     * Firebase 사용자 업데이트
     *
     * Firebase의 사용자 정보를 업데이트합니다.
     *
     * @param  string  $uid  Firebase UID
     * @param  array<string, mixed>  $updateData  업데이트할 데이터
     * @return bool 업데이트 성공 여부
     */
    public function updateFirebaseUser(string $uid, array $updateData): bool
    {
        return $this->getAuthService()->updateFirebaseUser($uid, $updateData);
    }

    /**
     * Firebase 사용자 삭제
     *
     * Firebase에서 사용자를 삭제합니다.
     *
     * @param  string  $uid  Firebase UID
     * @return bool 삭제 성공 여부
     */
    public function deleteFirebaseUser(string $uid): bool
    {
        return $this->getAuthService()->deleteFirebaseUser($uid);
    }

    // =========================================================================
    // FCM 푸시 알림 관련 메서드 (FirebaseMessagingService로 위임)
    // =========================================================================

    /**
     * FCM 푸시 알림 전송 (단일 디바이스)
     *
     * @param  string  $deviceToken  FCM 디바이스 토큰
     * @param  string  $title  알림 제목
     * @param  string  $body  알림 내용
     * @param  array<string, mixed>|null  $data  추가 데이터 페이로드
     * @param  array<string, mixed>|null  $options  알림 옵션 (배지, 소리 등)
     * @return bool 전송 성공 여부
     */
    public function sendPushNotification(
        string $deviceToken,
        string $title,
        string $body,
        ?array $data = null,
        ?array $options = null
    ): bool {
        return $this->getMessagingService()->sendPushNotification(
            $deviceToken,
            $title,
            $body,
            $data,
            $options
        );
    }

    /**
     * FCM 푸시 알림 전송 (여러 디바이스)
     *
     * @param  array<int, string>  $deviceTokens  FCM 디바이스 토큰 배열
     * @param  string  $title  알림 제목
     * @param  string  $body  알림 내용
     * @param  array<string, mixed>|null  $data  추가 데이터 페이로드
     * @param  array<string, mixed>|null  $options  알림 옵션
     * @return array<string, mixed> 전송 결과 (성공/실패 토큰 리스트)
     */
    public function sendPushNotificationToMultipleDevices(
        array $deviceTokens,
        string $title,
        string $body,
        ?array $data = null,
        ?array $options = null
    ): array {
        return $this->getMessagingService()->sendPushNotificationToMultipleDevices(
            $deviceTokens,
            $title,
            $body,
            $data,
            $options
        );
    }

    // =========================================================================
    // Realtime Database 관련 메서드 (FirebaseDatabaseService로 위임)
    // =========================================================================

    /**
     * Realtime Database에 데이터 저장
     *
     * @param  string  $path  데이터 경로
     * @param  mixed  $data  저장할 데이터
     * @return bool 저장 성공 여부
     */
    public function setRealtimeData(string $path, mixed $data): bool
    {
        return $this->getDatabaseService()->setRealtimeData($path, $data);
    }

    /**
     * Realtime Database에서 데이터 조회
     *
     * @param  string  $path  데이터 경로
     * @return mixed 조회된 데이터 또는 null
     */
    public function getRealtimeData(string $path): mixed
    {
        return $this->getDatabaseService()->getRealtimeData($path);
    }

    /**
     * Realtime Database에서 데이터 삭제
     *
     * @param  string  $path  데이터 경로
     * @return bool 삭제 성공 여부
     */
    public function deleteRealtimeData(string $path): bool
    {
        return $this->getDatabaseService()->deleteRealtimeData($path);
    }

    // =========================================================================
    // 추가 편의 메서드들 (FirebaseDatabaseService 확장 기능들)
    // =========================================================================

    /**
     * Realtime Database에서 데이터 업데이트
     *
     * @param  string  $path  데이터 경로
     * @param  array<string, mixed>  $updates  업데이트할 필드들
     * @return bool 업데이트 성공 여부
     */
    public function updateRealtimeData(string $path, array $updates): bool
    {
        return $this->getDatabaseService()->updateRealtimeData($path, $updates);
    }

    /**
     * Realtime Database에 새로운 자식 노드 추가
     *
     * @param  string  $path  부모 노드 경로
     * @param  mixed  $data  추가할 데이터
     * @return string|null 생성된 키 또는 null (실패 시)
     */
    public function pushRealtimeData(string $path, mixed $data): ?string
    {
        return $this->getDatabaseService()->pushRealtimeData($path, $data);
    }

    /**
     * FCM 주제로 푸시 알림 전송
     *
     * @param  string  $topic  주제 이름
     * @param  string  $title  알림 제목
     * @param  string  $body  알림 내용
     * @param  array<string, mixed>|null  $data  추가 데이터 페이로드
     * @param  array<string, mixed>|null  $options  알림 옵션
     * @return bool 전송 성공 여부
     */
    public function sendPushNotificationToTopic(
        string $topic,
        string $title,
        string $body,
        ?array $data = null,
        ?array $options = null
    ): bool {
        return $this->getMessagingService()->sendPushNotificationToTopic(
            $topic,
            $title,
            $body,
            $data,
            $options
        );
    }

    /**
     * 디바이스 토큰을 주제에 구독
     *
     * @param  array<int, string>|string  $deviceTokens  디바이스 토큰(들)
     * @param  string  $topic  구독할 주제
     * @return array<string, mixed> 구독 결과
     */
    public function subscribeToTopic(array|string $deviceTokens, string $topic): array
    {
        return $this->getMessagingService()->subscribeToTopic($deviceTokens, $topic);
    }

    /**
     * 디바이스 토큰을 주제에서 구독 해제
     *
     * @param  array<int, string>|string  $deviceTokens  디바이스 토큰(들)
     * @param  string  $topic  구독 해제할 주제
     * @return array<string, mixed> 구독 해제 결과
     */
    public function unsubscribeFromTopic(array|string $deviceTokens, string $topic): array
    {
        return $this->getMessagingService()->unsubscribeFromTopic($deviceTokens, $topic);
    }

    // =========================================================================
    // 서비스 관리 메서드들
    // =========================================================================

    /**
     * 모든 서비스 인스턴스 재설정
     *
     * 테스트 또는 설정 변경 시 서비스들을 재초기화하기 위해 사용합니다.
     */
    public function resetServices(): void
    {
        $this->firebaseClientFactory->reset();
        $this->firebaseAuthService = null;
        $this->firebaseMessagingService = null;
        $this->firebaseDatabaseService = null;
    }

    /**
     * 특정 서비스 직접 접근 (고급 사용자용)
     *
     * @return FirebaseAuthService Firebase 인증 서비스
     */
    public function auth(): FirebaseAuthService
    {
        return $this->getAuthService();
    }

    /**
     * 특정 서비스 직접 접근 (고급 사용자용)
     *
     * @return FirebaseMessagingService Firebase 메시징 서비스
     */
    public function messaging(): FirebaseMessagingService
    {
        return $this->getMessagingService();
    }

    /**
     * 특정 서비스 직접 접근 (고급 사용자용)
     *
     * @return FirebaseDatabaseService Firebase 데이터베이스 서비스
     */
    public function database(): FirebaseDatabaseService
    {
        return $this->getDatabaseService();
    }
}

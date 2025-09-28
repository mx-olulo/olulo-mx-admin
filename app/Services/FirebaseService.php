<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Kreait\Firebase\Contract\Auth;
use Kreait\Firebase\Contract\Database;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;
use Kreait\Firebase\Exception\FirebaseException;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

/**
 * Firebase 통합 서비스
 *
 * Firebase Admin SDK를 통한 인증, 사용자 관리, FCM 푸시 알림,
 * Realtime Database 연동을 담당하는 서비스 클래스입니다.
 *
 * 주요 기능:
 * - Firebase ID Token 검증
 * - Firebase User와 Laravel User 동기화
 * - 사용자 생성/업데이트/조회
 * - FCM 푸시 알림 (향후 확장)
 * - Realtime Database 연동 (향후 확장)
 */
class FirebaseService
{
    private Auth $auth;

    private ?Database $database = null;

    private ?Messaging $messaging = null;

    /**
     * Firebase 서비스 초기화
     *
     * @throws Exception Firebase 초기화 실패 시
     */
    public function __construct()
    {
        $this->initializeFirebase();
    }

    /**
     * Firebase Admin SDK 초기화
     *
     * 환경 변수 또는 서비스 어카운트 키 파일을 통해 Firebase를 초기화합니다.
     * Emulator 환경도 지원합니다.
     *
     * @throws Exception 초기화 실패 시
     */
    private function initializeFirebase(): void
    {
        try {
            $factory = (new Factory);

            // 환경 변수를 통한 설정 우선 시도
            if ($this->hasEnvironmentCredentials()) {
                $factory = $factory->withServiceAccount([
                    'type' => 'service_account',
                    'project_id' => Config::get('services.firebase.project_id'),
                    'private_key_id' => Config::get('services.firebase.private_key_id'),
                    'private_key' => str_replace('\\n', "\n", Config::get('services.firebase.private_key')),
                    'client_email' => Config::get('services.firebase.client_email'),
                    'client_id' => Config::get('services.firebase.client_id'),
                    'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
                    'token_uri' => 'https://oauth2.googleapis.com/token',
                    'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
                    'client_x509_cert_url' => sprintf(
                        'https://www.googleapis.com/robot/v1/metadata/x509/%s',
                        urlencode(Config::get('services.firebase.client_email'))
                    ),
                ]);
            } else {
                // 서비스 어카운트 키 파일 사용
                $serviceAccountPath = resource_path('firebase/mx-olulo-firebase-adminsdk-fbsvc-417ad72871.json');
                if (file_exists($serviceAccountPath)) {
                    $factory = $factory->withServiceAccount($serviceAccountPath);
                } else {
                    throw new Exception('Firebase 서비스 어카운트 키 파일이 존재하지 않습니다: ' . $serviceAccountPath);
                }
            }

            // Emulator 설정 (개발 환경)
            // Note: Emulator 메서드들은 Firebase SDK 버전에 따라 사용 가능 여부가 다를 수 있습니다.

            $this->auth = $factory->createAuth();
        } catch (Exception $e) {
            Log::error('Firebase 초기화 실패: ' . $e->getMessage());
            throw new Exception('Firebase 서비스 초기화에 실패했습니다: ' . $e->getMessage());
        }
    }

    /**
     * 환경 변수에 Firebase 자격증명이 설정되어 있는지 확인
     */
    private function hasEnvironmentCredentials(): bool
    {
        return ! empty(Config::get('services.firebase.project_id')) &&
               ! empty(Config::get('services.firebase.client_email')) &&
               ! empty(Config::get('services.firebase.private_key'));
    }

    /**
     * Firebase 클레임에서 sign_in_provider 추출
     *
     * @param  mixed  $firebaseClaims  Firebase 클레임 데이터
     * @return string|null 로그인 프로바이더 ID
     */
    private function extractFirebaseSignInProvider(mixed $firebaseClaims): ?string
    {
        if (is_array($firebaseClaims) && isset($firebaseClaims['sign_in_provider'])) {
            return (string) $firebaseClaims['sign_in_provider'];
        }

        return null;
    }

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
        try {
            $verifiedIdToken = $this->auth->verifyIdToken($idToken);

            return [
                'uid' => $verifiedIdToken->claims()->get('sub'),
                'email' => $verifiedIdToken->claims()->get('email'),
                'email_verified' => $verifiedIdToken->claims()->get('email_verified', false),
                'phone_number' => $verifiedIdToken->claims()->get('phone_number'),
                'name' => $verifiedIdToken->claims()->get('name'),
                'picture' => $verifiedIdToken->claims()->get('picture'),
                'provider_id' => $this->extractFirebaseSignInProvider($verifiedIdToken->claims()->get('firebase')),
            ];
        } catch (FailedToVerifyToken $e) {
            throw $e;
        }
    }

    /**
     * 개발 환경(로컬/에뮬레이터)에서 서명되지 않은 토큰(alg=none)에 대한 관대한 검증 경로
     *
     * 1) 일반 검증 시도
     * 2) 실패하고 APP_ENV=local 이면 토큰의 payload만 디코드하여 최소 정보(sub/email 등) 추출
     *
     * 보안 주의: 이 경로는 로컬 개발에서만 사용해야 하며, 운영 환경에서는 절대 사용 금지
     */
    public function verifyIdTokenLenient(string $idToken): array
    {
        try {
            return $this->verifyIdToken($idToken);
        } catch (FailedToVerifyToken $e) {
            if (config('app.env') !== 'local') {
                throw $e;
            }

            // 로컬 환경: 에뮬레이터 토큰은 서명 없이 발급되므로 payload만 신뢰(개발 용도)
            $parts = explode('.', $idToken);
            if (count($parts) < 2) {
                throw $e; // 형식 자체가 아님
            }
            $payload = $parts[1];
            $payload = strtr($payload, '-_', '+/');
            $padding = strlen($payload) % 4;
            if ($padding > 0) {
                $payload .= str_repeat('=', 4 - $padding);
            }
            $json = base64_decode($payload, true);
            $claims = json_decode($json, true);

            if (! is_array($claims) || ! isset($claims['sub'])) {
                throw $e;
            }

            return [
                'uid' => (string) ($claims['sub'] ?? ''),
                'email' => $claims['email'] ?? null,
                'email_verified' => (bool) ($claims['email_verified'] ?? false),
                'phone_number' => $claims['phone_number'] ?? null,
                'name' => $claims['name'] ?? null,
                'picture' => $claims['picture'] ?? null,
                'provider_id' => $this->extractFirebaseSignInProvider($claims['firebase'] ?? []),
            ];
        }
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
        try {
            $userRecord = $this->auth->getUser($uid);

            return [
                'uid' => $userRecord->uid,
                'email' => $userRecord->email,
                'email_verified' => $userRecord->emailVerified,
                'phone_number' => $userRecord->phoneNumber,
                'display_name' => $userRecord->displayName,
                'photo_url' => $userRecord->photoUrl,
                'disabled' => $userRecord->disabled,
                'created_at' => $userRecord->metadata->createdAt,
                'last_sign_in_at' => $userRecord->metadata->lastSignInTime ?? null,
                'provider_data' => array_map(function ($provider) {
                    return [
                        'provider_id' => $provider->providerId,
                        'uid' => $provider->uid,
                        'email' => $provider->email,
                        'display_name' => $provider->displayName,
                        'photo_url' => $provider->photoUrl,
                    ];
                }, $userRecord->providerData),
            ];
        } catch (FirebaseException $e) {
            Log::warning('Firebase 사용자 조회 실패', [
                'uid' => $uid,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Firebase User와 Laravel User 동기화
     *
     * Firebase 사용자 정보를 기반으로 Laravel 사용자를 생성하거나 업데이트합니다.
     * 이메일이 없는 경우 전화번호를 사용하여 자동 생성합니다.
     *
     * @param  array<string, mixed>  $firebaseUserData  Firebase 사용자 데이터
     * @return User Laravel 사용자 모델
     */
    public function syncFirebaseUserWithLaravel(array $firebaseUserData): User
    {
        $uid = $firebaseUserData['uid'];
        $email = $firebaseUserData['email'];
        $phoneNumber = $firebaseUserData['phone_number'];

        // 이메일이 없는 경우 전화번호로 자동 생성
        if (empty($email) && ! empty($phoneNumber)) {
            $cleanPhoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
            $email = $cleanPhoneNumber . '@olulo.com.mx';
        }

        if (empty($email)) {
            throw new Exception('이메일 또는 전화번호가 필요합니다.');
        }

        // 기존 사용자 조회 (Firebase UID 또는 이메일로)
        $user = User::where('firebase_uid', $uid)
            ->orWhere('email', $email)
            ->first();

        if ($user) {
            // 기존 사용자 업데이트
            $user->update([
                'firebase_uid' => $uid,
                'name' => $firebaseUserData['name'] ?? $user->name,
                'email' => $email,
                'email_verified_at' => $firebaseUserData['email_verified'] ? now() : null,
                'phone_number' => $phoneNumber,
                'avatar_url' => $firebaseUserData['picture'] ?? $user->avatar_url,
            ]);

            Log::info('기존 Laravel 사용자 업데이트 완료', [
                'user_id' => $user->id,
                'firebase_uid' => $uid,
                'email' => $email,
            ]);
        } else {
            // 새 사용자 생성
            $user = User::create([
                'firebase_uid' => $uid,
                'name' => $firebaseUserData['name'] ?? $this->extractNameFromEmail($email),
                'email' => $email,
                'email_verified_at' => $firebaseUserData['email_verified'] ? now() : null,
                'phone_number' => $phoneNumber,
                'avatar_url' => $firebaseUserData['picture'],
                'password' => bcrypt(Str::random(32)), // 랜덤 비밀번호 (Firebase 인증 우선)
            ]);

            Log::info('새 Laravel 사용자 생성 완료', [
                'user_id' => $user->id,
                'firebase_uid' => $uid,
                'email' => $email,
            ]);
        }

        return $user;
    }

    /**
     * 이메일에서 사용자 이름 추출
     *
     * @param  string  $email  이메일 주소
     * @return string 추출된 이름
     */
    private function extractNameFromEmail(string $email): string
    {
        $localPart = explode('@', $email)[0];

        return ucfirst(str_replace(['.', '_', '-'], ' ', $localPart));
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
        try {
            $userCreationRequest = $this->auth->createUserRequest();

            if (! empty($userData['email'])) {
                $userCreationRequest = $userCreationRequest->withEmail($userData['email']);
            }

            if (! empty($userData['phone_number'])) {
                $userCreationRequest = $userCreationRequest->withPhoneNumber($userData['phone_number']);
            }

            if (! empty($userData['display_name'])) {
                $userCreationRequest = $userCreationRequest->withDisplayName($userData['display_name']);
            }

            if (! empty($userData['photo_url'])) {
                $userCreationRequest = $userCreationRequest->withPhotoUrl($userData['photo_url']);
            }

            if (! empty($userData['password'])) {
                $userCreationRequest = $userCreationRequest->withPassword($userData['password']);
            }

            $userRecord = $this->auth->createUser($userCreationRequest);

            Log::info('Firebase 사용자 생성 완료', [
                'uid' => $userRecord->uid,
                'email' => $userData['email'] ?? null,
            ]);

            return $userRecord->uid;
        } catch (FirebaseException $e) {
            Log::error('Firebase 사용자 생성 실패', [
                'error' => $e->getMessage(),
                'user_data' => $userData,
            ]);
            throw $e;
        }
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
        try {
            $userUpdateRequest = $this->auth->updateUserRequest($uid);

            if (array_key_exists('email', $updateData)) {
                $userUpdateRequest = $userUpdateRequest->withEmail($updateData['email']);
            }

            if (array_key_exists('phone_number', $updateData)) {
                $userUpdateRequest = $userUpdateRequest->withPhoneNumber($updateData['phone_number']);
            }

            if (array_key_exists('display_name', $updateData)) {
                $userUpdateRequest = $userUpdateRequest->withDisplayName($updateData['display_name']);
            }

            if (array_key_exists('photo_url', $updateData)) {
                $userUpdateRequest = $userUpdateRequest->withPhotoUrl($updateData['photo_url']);
            }

            if (array_key_exists('password', $updateData)) {
                $userUpdateRequest = $userUpdateRequest->withPassword($updateData['password']);
            }

            if (array_key_exists('disabled', $updateData)) {
                if ($updateData['disabled']) {
                    $userUpdateRequest = $userUpdateRequest->markAsDisabled();
                } else {
                    $userUpdateRequest = $userUpdateRequest->markAsEnabled();
                }
            }

            $this->auth->updateUser($userUpdateRequest);

            Log::info('Firebase 사용자 업데이트 완료', [
                'uid' => $uid,
                'updated_fields' => array_keys($updateData),
            ]);

            return true;
        } catch (FirebaseException $e) {
            Log::error('Firebase 사용자 업데이트 실패', [
                'uid' => $uid,
                'error' => $e->getMessage(),
                'update_data' => $updateData,
            ]);

            return false;
        }
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
        try {
            $this->auth->deleteUser($uid);

            Log::info('Firebase 사용자 삭제 완료', ['uid' => $uid]);

            return true;
        } catch (FirebaseException $e) {
            Log::error('Firebase 사용자 삭제 실패', [
                'uid' => $uid,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    // =========================================================================
    // FCM 푸시 알림 관련 메서드 (향후 확장용)
    // =========================================================================

    /**
     * FCM Messaging 인스턴스 초기화 (지연 로딩)
     */
    private function getMessaging(): Messaging
    {
        if ($this->messaging === null) {
            $factory = (new Factory);

            if ($this->hasEnvironmentCredentials()) {
                $factory = $factory->withServiceAccount([
                    'type' => 'service_account',
                    'project_id' => Config::get('services.firebase.project_id'),
                    'private_key_id' => Config::get('services.firebase.private_key_id'),
                    'private_key' => str_replace('\\n', "\n", Config::get('services.firebase.private_key')),
                    'client_email' => Config::get('services.firebase.client_email'),
                    'client_id' => Config::get('services.firebase.client_id'),
                    'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
                    'token_uri' => 'https://oauth2.googleapis.com/token',
                    'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
                    'client_x509_cert_url' => sprintf(
                        'https://www.googleapis.com/robot/v1/metadata/x509/%s',
                        urlencode(Config::get('services.firebase.client_email'))
                    ),
                ]);
            } else {
                $serviceAccountPath = resource_path('firebase/mx-olulo-firebase-adminsdk-fbsvc-417ad72871.json');
                $factory = $factory->withServiceAccount($serviceAccountPath);
            }

            $this->messaging = $factory->createMessaging();
        }

        return $this->messaging;
    }

    /**
     * FCM 푸시 알림 전송 (단일 디바이스)
     *
     * @param  string  $deviceToken  FCM 디바이스 토큰
     * @param  string  $title  알림 제목
     * @param  string  $body  알림 내용
     * @param  array<string, mixed>|null  $data  추가 데이터
     * @return bool 전송 성공 여부
     */
    public function sendPushNotification(string $deviceToken, string $title, string $body, ?array $data = null): bool
    {
        try {
            $notification = Notification::create($title, $body);

            $message = CloudMessage::withTarget('token', $deviceToken)
                ->withNotification($notification);

            if ($data) {
                $message = $message->withData($data);
            }

            $this->getMessaging()->send($message);

            Log::info('FCM 푸시 알림 전송 완료', [
                'device_token' => substr($deviceToken, 0, 20) . '...',
                'title' => $title,
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('FCM 푸시 알림 전송 실패', [
                'error' => $e->getMessage(),
                'device_token' => substr($deviceToken, 0, 20) . '...',
                'title' => $title,
            ]);

            return false;
        }
    }

    /**
     * FCM 푸시 알림 전송 (여러 디바이스)
     *
     * @param  array<int, string>  $deviceTokens  FCM 디바이스 토큰 배열
     * @param  string  $title  알림 제목
     * @param  string  $body  알림 내용
     * @param  array<string, mixed>|null  $data  추가 데이터
     * @return array<string, mixed> 전송 결과 (성공/실패 토큰 리스트)
     */
    public function sendPushNotificationToMultipleDevices(array $deviceTokens, string $title, string $body, ?array $data = null): array
    {
        try {
            $notification = Notification::create($title, $body);

            $message = CloudMessage::new()->withNotification($notification);

            if ($data) {
                $message = $message->withData($data);
            }

            $sendReport = $this->getMessaging()->sendMulticast($message, $deviceTokens);

            $results = [
                'success_count' => $sendReport->successes()->count(),
                'failure_count' => $sendReport->failures()->count(),
                'success_tokens' => [],
                'failed_tokens' => [],
            ];

            foreach ($sendReport->successes() as $result) {
                $results['success_tokens'][] = $result->target()->value();
            }

            foreach ($sendReport->failures() as $result) {
                $results['failed_tokens'][] = [
                    'token' => $result->target()->value(),
                    'error' => $result->error()->getMessage(),
                ];
            }

            Log::info('FCM 다중 푸시 알림 전송 완료', [
                'total_tokens' => count($deviceTokens),
                'success_count' => $results['success_count'],
                'failure_count' => $results['failure_count'],
                'title' => $title,
            ]);

            return $results;
        } catch (Exception $e) {
            Log::error('FCM 다중 푸시 알림 전송 실패', [
                'error' => $e->getMessage(),
                'token_count' => count($deviceTokens),
                'title' => $title,
            ]);

            return [
                'success_count' => 0,
                'failure_count' => count($deviceTokens),
                'success_tokens' => [],
                'failed_tokens' => array_map(function ($token) use ($e) {
                    return [
                        'token' => $token,
                        'error' => $e->getMessage(),
                    ];
                }, $deviceTokens),
            ];
        }
    }

    // =========================================================================
    // Realtime Database 관련 메서드 (향후 확장용)
    // =========================================================================

    /**
     * Realtime Database 인스턴스 초기화 (지연 로딩)
     */
    private function getDatabase(): Database
    {
        if ($this->database === null) {
            $factory = (new Factory);

            if ($this->hasEnvironmentCredentials()) {
                $factory = $factory->withServiceAccount([
                    'type' => 'service_account',
                    'project_id' => Config::get('services.firebase.project_id'),
                    'private_key_id' => Config::get('services.firebase.private_key_id'),
                    'private_key' => str_replace('\\n', "\n", Config::get('services.firebase.private_key')),
                    'client_email' => Config::get('services.firebase.client_email'),
                    'client_id' => Config::get('services.firebase.client_id'),
                    'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
                    'token_uri' => 'https://oauth2.googleapis.com/token',
                    'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
                    'client_x509_cert_url' => sprintf(
                        'https://www.googleapis.com/robot/v1/metadata/x509/%s',
                        urlencode(Config::get('services.firebase.client_email'))
                    ),
                ]);
            } else {
                $serviceAccountPath = resource_path('firebase/mx-olulo-firebase-adminsdk-fbsvc-417ad72871.json');
                $factory = $factory->withServiceAccount($serviceAccountPath);
            }

            // Emulator 설정 (개발 환경)
            // Note: Database Emulator 설정은 Firebase SDK 버전에 따라 다를 수 있습니다.

            $this->database = $factory->createDatabase();
        }

        return $this->database;
    }

    /**
     * Realtime Database에 데이터 저장
     *
     * @param  string  $path  데이터 경로
     * @param  mixed  $data  저장할 데이터
     * @return bool 저장 성공 여부
     */
    public function setRealtimeData(string $path, mixed $data): bool
    {
        try {
            $this->getDatabase()->getReference($path)->set($data);

            Log::info('Realtime Database 데이터 저장 완료', ['path' => $path]);

            return true;
        } catch (Exception $e) {
            Log::error('Realtime Database 데이터 저장 실패', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Realtime Database에서 데이터 조회
     *
     * @param  string  $path  데이터 경로
     * @return mixed 조회된 데이터 또는 null
     */
    public function getRealtimeData(string $path): mixed
    {
        try {
            $snapshot = $this->getDatabase()->getReference($path)->getSnapshot();

            Log::info('Realtime Database 데이터 조회 완료', ['path' => $path]);

            return $snapshot->getValue();
        } catch (Exception $e) {
            Log::error('Realtime Database 데이터 조회 실패', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Realtime Database에서 데이터 삭제
     *
     * @param  string  $path  데이터 경로
     * @return bool 삭제 성공 여부
     */
    public function deleteRealtimeData(string $path): bool
    {
        try {
            $this->getDatabase()->getReference($path)->remove();

            Log::info('Realtime Database 데이터 삭제 완료', ['path' => $path]);

            return true;
        } catch (Exception $e) {
            Log::error('Realtime Database 데이터 삭제 실패', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    // =========================================================================
    // Firestore 관련 메서드 (향후 확장용 - 별도 서비스로 분리 예정)
    // =========================================================================

    /**
     * Firestore 기능은 향후 별도의 FirestoreService로 분리될 예정입니다.
     * 현재는 Firebase Admin SDK의 Auth와 Database 기능에 집중합니다.
     */
}

<?php

declare(strict_types=1);

namespace App\Services\Firebase;

use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Kreait\Firebase\Contract\Auth;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;
use Kreait\Firebase\Exception\FirebaseException;

/**
 * Firebase 인증 서비스
 *
 * Firebase Authentication 관련 기능을 담당하는 서비스 클래스입니다.
 * ID Token 검증, 사용자 관리, Laravel User 동기화 등의 기능을 제공합니다.
 *
 * 주요 기능:
 * - Firebase ID Token 검증 (일반/관대한 검증)
 * - Firebase User CRUD operations
 * - Firebase User와 Laravel User 동기화
 * - 사용자 정보 추출 및 변환
 */
class FirebaseAuthService
{
    private Auth $auth;

    /**
     * Firebase 인증 서비스 초기화
     *
     * @param  FirebaseClientFactory  $clientFactory  Firebase 클라이언트 팩토리
     */
    public function __construct(private readonly FirebaseClientFactory $clientFactory)
    {
        $this->auth = $this->clientFactory->createAuth();
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
     *
     * @param  string  $idToken  Firebase ID Token
     * @return array<string, mixed> 검증된 토큰 정보
     *
     * @throws FailedToVerifyToken 토큰 검증 실패 시
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
     *
     * @throws Exception 이메일과 전화번호가 모두 없는 경우
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
}

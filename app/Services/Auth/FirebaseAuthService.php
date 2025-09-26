<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\User;
use App\Services\Auth\Contracts\FirebaseAuthInterface;
use App\Services\Auth\Exceptions\FirebaseAuthException;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Auth as FirebaseAuth;
use Kreait\Firebase\Auth\UserRecord;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;
use Kreait\Firebase\Exception\Auth\UserNotFound;
use Kreait\Firebase\Factory;
use Throwable;

/**
 * Firebase 인증 서비스 구현
 *
 * Firebase Admin SDK를 사용한 서버 측 인증 처리
 */
class FirebaseAuthService implements FirebaseAuthInterface
{
    private FirebaseAuth $auth;

    public function __construct()
    {
        $this->initializeFirebase();
    }

    /**
     * Firebase Admin SDK 초기화
     *
     * @throws FirebaseAuthException
     */
    private function initializeFirebase(): void
    {
        try {
            // 환경변수에서 Firebase 설정 가져오기
            $projectId = config('services.firebase.project_id');
            $clientEmail = config('services.firebase.client_email');
            $privateKey = config('services.firebase.private_key');

            if (! $projectId || ! $clientEmail || ! $privateKey) {
                throw FirebaseAuthException::configurationError(
                    'Firebase 환경변수가 설정되지 않았습니다.'
                );
            }

            // Firebase Factory를 사용하여 인증 인스턴스 생성
            $factory = (new Factory)
                ->withServiceAccount([
                    'type' => 'service_account',
                    'project_id' => $projectId,
                    'client_email' => $clientEmail,
                    'private_key' => $privateKey,
                    'client_id' => config('services.firebase.client_id'),
                    'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
                    'token_uri' => 'https://oauth2.googleapis.com/token',
                    'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
                ]);

            $this->auth = $factory->createAuth();
        } catch (Throwable $e) {
            Log::error('Firebase 초기화 실패', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw FirebaseAuthException::configurationError($e->getMessage());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function verifyIdToken(string $idToken): array
    {
        try {
            // ID 토큰 검증 (체크섬 및 만료 시간 확인 포함)
            $verifiedIdToken = $this->auth->verifyIdToken($idToken, true);

            // 토큰 클레임 추출
            $claims = $verifiedIdToken->claims()->all();

            return [
                'uid' => $claims['sub'] ?? $claims['user_id'],
                'email' => $claims['email'] ?? null,
                'name' => $claims['name'] ?? null,
                'picture' => $claims['picture'] ?? null,
                'email_verified' => $claims['email_verified'] ?? false,
            ];
        } catch (FailedToVerifyToken $e) {
            Log::warning('유효하지 않은 Firebase ID 토큰', ['error' => $e->getMessage()]);
            throw FirebaseAuthException::invalidToken($e->getMessage());
        } catch (Throwable $e) {
            Log::error('Firebase ID 토큰 검증 실패', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw FirebaseAuthException::serviceError($e->getMessage());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getUserByUid(string $uid): UserRecord
    {
        try {
            return $this->auth->getUser($uid);
        } catch (UserNotFound $e) {
            throw FirebaseAuthException::userNotFound($uid);
        } catch (Throwable $e) {
            Log::error('Firebase 사용자 조회 실패', [
                'uid' => $uid,
                'error' => $e->getMessage(),
            ]);
            throw FirebaseAuthException::serviceError($e->getMessage());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getUserByEmail(string $email): ?UserRecord
    {
        try {
            return $this->auth->getUserByEmail($email);
        } catch (UserNotFound) {
            return null;
        } catch (Throwable $e) {
            Log::error('Firebase 이메일로 사용자 조회 실패', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function syncUser(array $firebaseUser): User
    {
        // Firebase UID로 기존 사용자 찾기
        $user = User::where('firebase_uid', $firebaseUser['uid'])->first();

        if (! $user && isset($firebaseUser['email'])) {
            // 이메일로도 찾기 (기존 사용자 마이그레이션 지원)
            $user = User::where('email', $firebaseUser['email'])->first();
        }

        // 사용자 데이터 준비
        $userData = [
            'firebase_uid' => $firebaseUser['uid'],
            'email' => $firebaseUser['email'] ?? null,
            'name' => $firebaseUser['name'] ?? null,
        ];

        // email_verified 추가 (배열에 있을 경우만)
        if (array_key_exists('email_verified', $firebaseUser) && $firebaseUser['email_verified']) {
            $userData['email_verified_at'] = now();
        }

        if ($user) {
            // 기존 사용자 업데이트
            $user->update(array_filter($userData));
        } else {
            // 새 사용자 생성
            $userData['password'] = bcrypt(uniqid()); // 임시 비밀번호 (Firebase 인증 사용)
            $user = User::create($userData);
        }

        return $user;
    }

    /**
     * {@inheritdoc}
     */
    public function createCustomToken(string $uid, array $claims = []): string
    {
        try {
            return $this->auth->createCustomToken($uid, $claims)->toString();
        } catch (Throwable $e) {
            Log::error('Firebase 커스텀 토큰 생성 실패', [
                'uid' => $uid,
                'error' => $e->getMessage(),
            ]);
            throw FirebaseAuthException::serviceError($e->getMessage());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function disableUser(string $uid): bool
    {
        try {
            $this->auth->disableUser($uid);

            return true;
        } catch (Throwable $e) {
            Log::error('Firebase 사용자 비활성화 실패', [
                'uid' => $uid,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function enableUser(string $uid): bool
    {
        try {
            $this->auth->enableUser($uid);

            return true;
        } catch (Throwable $e) {
            Log::error('Firebase 사용자 활성화 실패', [
                'uid' => $uid,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}

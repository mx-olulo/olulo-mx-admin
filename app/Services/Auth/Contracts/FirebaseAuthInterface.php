<?php

declare(strict_types=1);

namespace App\Services\Auth\Contracts;

use App\Models\User;
use Kreait\Firebase\Auth\UserRecord;

/**
 * Firebase 인증 서비스 인터페이스
 *
 * Firebase Admin SDK를 통한 인증 토큰 검증 및 사용자 관리 기능 정의
 */
interface FirebaseAuthInterface
{
    /**
     * Firebase ID 토큰 검증
     *
     * @param  string  $idToken  Firebase로부터 받은 ID 토큰
     * @return array{uid: string, email: string|null, name: string|null, picture: string|null} 검증된 토큰 클레임
     *
     * @throws \App\Services\Auth\Exceptions\FirebaseAuthException
     */
    public function verifyIdToken(string $idToken): array;

    /**
     * Firebase UID로 사용자 정보 조회
     *
     * @param  string  $uid  Firebase 사용자 UID
     * @return UserRecord Firebase 사용자 레코드
     *
     * @throws \App\Services\Auth\Exceptions\FirebaseAuthException
     */
    public function getUserByUid(string $uid): UserRecord;

    /**
     * 이메일로 Firebase 사용자 조회
     *
     * @param  string  $email  사용자 이메일
     * @return UserRecord|null Firebase 사용자 레코드
     */
    public function getUserByEmail(string $email): ?UserRecord;

    /**
     * Firebase 사용자와 로컬 User 모델 동기화
     *
     * @param  array{uid: string, email: string|null, name: string|null, email_verified?: bool}  $firebaseUser  Firebase 사용자 정보
     * @return User 생성되거나 업데이트된 User 모델
     */
    public function syncUser(array $firebaseUser): User;

    /**
     * Firebase 커스텀 토큰 생성
     *
     * @param  string  $uid  Firebase 사용자 UID
     * @param  array<string, mixed>  $claims  추가 클레임
     * @return string 생성된 커스텀 토큰
     *
     * @throws \App\Services\Auth\Exceptions\FirebaseAuthException
     */
    public function createCustomToken(string $uid, array $claims = []): string;

    /**
     * Firebase 사용자 비활성화
     *
     * @param  string  $uid  Firebase 사용자 UID
     * @return bool 성공 여부
     */
    public function disableUser(string $uid): bool;

    /**
     * Firebase 사용자 활성화
     *
     * @param  string  $uid  Firebase 사용자 UID
     * @return bool 성공 여부
     */
    public function enableUser(string $uid): bool;
}

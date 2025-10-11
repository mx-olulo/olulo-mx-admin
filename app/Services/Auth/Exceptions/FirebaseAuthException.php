<?php

declare(strict_types=1);

namespace App\Services\Auth\Exceptions;

use Exception;

/**
 * Firebase 인증 예외 클래스
 *
 * Firebase 인증 과정에서 발생하는 예외 처리
 */
class FirebaseAuthException extends Exception
{
    /**
     * 잘못된 토큰 예외 생성
     */
    public static function invalidToken(string $reason = ''): self
    {
        $message = '유효하지 않은 Firebase ID 토큰입니다.';
        if ($reason !== '' && $reason !== '0') {
            $message .= ' 사유: ' . $reason;
        }

        return new self($message, 401);
    }

    /**
     * 만료된 토큰 예외 생성
     */
    public static function expiredToken(): self
    {
        return new self('Firebase ID 토큰이 만료되었습니다.', 401);
    }

    /**
     * 사용자를 찾을 수 없음 예외 생성
     */
    public static function userNotFound(string $identifier): self
    {
        return new self("Firebase 사용자를 찾을 수 없습니다: {$identifier}", 404);
    }

    /**
     * Firebase 서비스 오류 예외 생성
     */
    public static function serviceError(string $message): self
    {
        return new self("Firebase 서비스 오류: {$message}", 503);
    }

    /**
     * 설정 오류 예외 생성
     */
    public static function configurationError(string $message): self
    {
        return new self("Firebase 설정 오류: {$message}", 500);
    }

    /**
     * 권한 부족 예외 생성
     */
    public static function insufficientPermissions(): self
    {
        return new self('이 작업을 수행할 권한이 없습니다.', 403);
    }

    /**
     * Rate limit 초과 예외 생성
     */
    public static function rateLimitExceeded(): self
    {
        return new self('너무 많은 요청이 발생했습니다. 잠시 후 다시 시도해주세요.', 429);
    }
}

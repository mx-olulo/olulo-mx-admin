<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * Rate Limit 상수 정의
 *
 * 애플리케이션 전반에서 사용되는 rate limiting 설정을 중앙 관리합니다.
 * Laravel ThrottleRequests 미들웨어와 함께 사용됩니다.
 *
 * @see \Illuminate\Routing\Middleware\ThrottleRequests
 */
final readonly class RateLimit
{
    /**
     * 인증 엔드포인트 최대 시도 횟수
     *
     * 로그인, 회원가입, 비밀번호 재설정 등 인증 관련 엔드포인트에 적용됩니다.
     * 브루트 포스 공격을 방지하면서도 정상 사용자의 편의성을 고려합니다.
     */
    public const int AUTH_MAX_ATTEMPTS = 10;

    /**
     * 인증 엔드포인트 제한 시간(분)
     *
     * AUTH_MAX_ATTEMPTS 횟수 초과 시 대기해야 하는 시간입니다.
     */
    public const int AUTH_DECAY_MINUTES = 1;

    /**
     * 일반 API 최대 요청 횟수
     *
     * 일반적인 API 엔드포인트에 적용되는 기본 제한입니다.
     */
    public const int API_MAX_REQUESTS = 60;

    /**
     * 일반 API 제한 시간(분)
     *
     * API_MAX_REQUESTS 횟수 초과 시 대기해야 하는 시간입니다.
     */
    public const int API_DECAY_MINUTES = 1;

    /**
     * 민감한 작업(결제, 주문 등) 최대 요청 횟수
     *
     * 결제 처리, 주문 생성 등 민감한 작업에 적용됩니다.
     */
    public const int SENSITIVE_MAX_REQUESTS = 10;

    /**
     * 민감한 작업 제한 시간(분)
     */
    public const int SENSITIVE_DECAY_MINUTES = 1;

    /**
     * 인증 throttle 미들웨어 설정 문자열 생성
     *
     * bootstrap/app.php에서 미들웨어 alias 등록 시 사용됩니다.
     */
    public static function authThrottle(): string
    {
        return self::AUTH_MAX_ATTEMPTS . ',' . self::AUTH_DECAY_MINUTES;
    }

    /**
     * API throttle 미들웨어 설정 문자열 생성
     */
    public static function apiThrottle(): string
    {
        return self::API_MAX_REQUESTS . ',' . self::API_DECAY_MINUTES;
    }

    /**
     * 민감한 작업 throttle 미들웨어 설정 문자열 생성
     */
    public static function sensitiveThrottle(): string
    {
        return self::SENSITIVE_MAX_REQUESTS . ',' . self::SENSITIVE_DECAY_MINUTES;
    }
}

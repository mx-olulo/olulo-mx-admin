<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | API Rate Limiting 설정
    |--------------------------------------------------------------------------
    |
    | API 엔드포인트의 Rate Limiting 정책을 정의합니다.
    | 환경변수로 오버라이드 가능합니다.
    |
    */

    'rate_limit' => [
        'auth' => [
            'max_attempts' => env('RATE_LIMIT_AUTH_MAX', 10),
            'decay_minutes' => env('RATE_LIMIT_AUTH_DECAY', 1),
        ],
    ],
];

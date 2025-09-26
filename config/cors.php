<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | 서브도메인 기반 멀티테넌시를 지원하는 CORS 설정
    | Firebase + Sanctum SPA 세션을 위해 credentials를 허용합니다.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',
        'auth/*',
    ],

    'allowed_methods' => ['*'],

    /*
    | 환경별 허용 오리진 설정
    | 로컬: localhost 도메인
    | 스테이징: demo.olulo.com.mx 서브도메인
    | 프로덕션: olulo.com.mx 서브도메인
    */
    'allowed_origins' => env('APP_ENV') === 'local'
        ? [
            'http://localhost:3000',
            'http://localhost:8000',
            'http://admin.localhost',
            'http://menu.localhost',
        ]
        : (env('APP_ENV') === 'staging'
            ? [
                'https://admin.demo.olulo.com.mx',
                'https://menu.demo.olulo.com.mx',
                'https://api.demo.olulo.com.mx',
            ]
            : [
                'https://admin.olulo.com.mx',
                'https://menu.olulo.com.mx',
                'https://api.olulo.com.mx',
            ]),

    /*
    | 패턴 기반 오리진 매칭
    | 서브도메인 와일드카드 지원
    */
    'allowed_origins_patterns' => env('APP_ENV') === 'production'
        ? ['#^https://[\w\-]+\.olulo\.com\.mx$#']
        : (env('APP_ENV') === 'staging'
            ? ['#^https://[\w\-]+\.demo\.olulo\.com\.mx$#']
            : []),

    'allowed_headers' => [
        'Content-Type',
        'X-Requested-With',
        'X-XSRF-TOKEN',
        'Authorization',
        'Accept',
        'Accept-Language',
        'X-Firebase-Token',
    ],

    'exposed_headers' => [
        'X-CSRF-TOKEN',
    ],

    /*
    | Preflight 요청 캐시 시간 (초)
    | 브라우저가 OPTIONS 요청 결과를 캐시하는 시간
    */
    'max_age' => 3600,

    /*
    | 쿠키/인증 정보 포함 허용
    | Sanctum SPA 세션을 위해 필수
    */
    'supports_credentials' => true,

];

<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Firebase Configuration
    |--------------------------------------------------------------------------
    |
    | Firebase Admin SDK 및 Web SDK 설정
    | 환경별로 다른 프로젝트를 사용할 수 있습니다.
    |
    */

    // Firebase Admin SDK (서버 측)
    'project_id' => env('FIREBASE_PROJECT_ID'),
    'client_email' => env('FIREBASE_CLIENT_EMAIL'),
    'client_id' => env('FIREBASE_CLIENT_ID'),
    'private_key' => env('FIREBASE_PRIVATE_KEY'),

    // Firebase Web SDK (클라이언트 측)
    'web' => [
        'api_key' => env('FIREBASE_WEB_API_KEY'),
        'auth_domain' => env('FIREBASE_AUTH_DOMAIN'),
        'project_id' => env('FIREBASE_PROJECT_ID'),
        'storage_bucket' => env('FIREBASE_STORAGE_BUCKET'),
        'messaging_sender_id' => env('FIREBASE_MESSAGING_SENDER_ID'),
        'app_id' => env('FIREBASE_APP_ID'),
        'measurement_id' => env('FIREBASE_MEASUREMENT_ID'),
    ],

    // Firebase 인증 설정
    'auth' => [
        // ID 토큰 검증 시 리토큰 여부
        'check_revoked' => env('FIREBASE_CHECK_REVOKED', true),

        // 세션 쿠키 수명 (초)
        'session_lifetime' => env('FIREBASE_SESSION_LIFETIME', 3600 * 24 * 5), // 5일

        // 커스텀 클레임 네임스페이스
        'custom_claims_namespace' => 'olulo',
    ],

    // Firebase Storage 설정
    'storage' => [
        'default_bucket' => env('FIREBASE_STORAGE_BUCKET'),
    ],

    // Firebase Cloud Messaging 설정
    'fcm' => [
        'server_key' => env('FIREBASE_SERVER_KEY'),
        'sender_id' => env('FIREBASE_MESSAGING_SENDER_ID'),
    ],
];

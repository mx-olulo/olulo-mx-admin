<?php

return [
    /**
     * Olulo 플랫폼 기본 설정
     */
    'platform' => [
        'name' => env('PLATFORM_NAME', 'Olulo MX'),
        'version' => '1.0.0',
        'country' => 'MX',
        'locale' => 'es_MX',
        'timezone' => 'America/Mexico_City',
        'currency' => 'MXN',
    ],

    /**
     * 비즈니스 규칙
     */
    'business' => [
        /**
         * 플랫폼 수수료 (%)
         */
        'platform_fee_percentage' => env('PLATFORM_FEE_PERCENTAGE', 15),

        /**
         * 최소 주문 금액
         */
        'minimum_order_amount' => env('MINIMUM_ORDER_AMOUNT', 50.00),

        /**
         * 최대 배달 거리 (미터)
         */
        'maximum_delivery_distance' => env('MAX_DELIVERY_DISTANCE', 10000),

        /**
         * 주문 취소 가능 시간 (분)
         */
        'order_cancellation_minutes' => env('ORDER_CANCELLATION_MINUTES', 5),

        /**
         * 리뷰 작성 가능 기간 (일)
         */
        'review_period_days' => env('REVIEW_PERIOD_DAYS', 7),
    ],

    /**
     * 결제 설정
     */
    'payment' => [
        /**
         * 활성화된 결제 수단
         */
        'enabled_methods' => [
            'cash',
            'card',
            'online_transfer',
            'wallet',
        ],

        /**
         * 기본 결제 게이트웨이
         */
        'default_gateway' => env('PAYMENT_GATEWAY', 'operacionesenlinea'),

        /**
         * 결제 게이트웨이별 설정
         */
        'gateways' => [
            'operacionesenlinea' => [
                'merchant_id' => env('OEL_MERCHANT_ID'),
                'api_key' => env('OEL_API_KEY'),
                'api_secret' => env('OEL_API_SECRET'),
                'sandbox' => env('OEL_SANDBOX', true),
                'webhook_secret' => env('OEL_WEBHOOK_SECRET'),
            ],
            'stripe' => [
                'key' => env('STRIPE_KEY'),
                'secret' => env('STRIPE_SECRET'),
                'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
                'enabled' => false,
            ],
        ],

        /**
         * 세금 설정
         */
        'tax' => [
            'enabled' => true,
            'rate' => env('TAX_RATE', 16), // IVA in Mexico
            'included_in_price' => false,
        ],
    ],

    /**
     * 배달 설정
     */
    'delivery' => [
        /**
         * 배달 방식
         */
        'methods' => [
            'platform_delivery',  // 플랫폼 자체 배달
            'restaurant_delivery', // 레스토랑 자체 배달
            'third_party',        // 서드파티 배달
        ],

        /**
         * 기본 배달료 계산 방식
         */
        'fee_calculation' => env('DELIVERY_FEE_CALCULATION', 'distance_based'),

        /**
         * 거리별 배달료 (거리: 미터, 요금: MXN)
         */
        'distance_fees' => [
            ['up_to' => 1000, 'fee' => 20],
            ['up_to' => 3000, 'fee' => 30],
            ['up_to' => 5000, 'fee' => 40],
            ['up_to' => 10000, 'fee' => 60],
        ],

        /**
         * 예상 배달 시간 설정 (분)
         */
        'estimated_time' => [
            'preparation' => 20,
            'per_kilometer' => 5,
            'minimum' => 30,
            'maximum' => 90,
        ],

        /**
         * 배달원 추적 설정
         */
        'tracking' => [
            'enabled' => true,
            'update_interval' => 30, // seconds
            'share_location' => true,
        ],
    ],

    /**
     * 알림 설정
     */
    'notifications' => [
        /**
         * 활성화된 채널
         */
        'channels' => [
            'database',
            'mail',
            'whatsapp',
            'push',
        ],

        /**
         * WhatsApp Business API
         */
        'whatsapp' => [
            'enabled' => env('WHATSAPP_ENABLED', true),
            'api_url' => env('WHATSAPP_API_URL'),
            'api_token' => env('WHATSAPP_API_TOKEN'),
            'phone_number' => env('WHATSAPP_PHONE_NUMBER'),
        ],

        /**
         * Push 알림 (Firebase)
         */
        'push' => [
            'enabled' => env('PUSH_ENABLED', true),
            'service' => 'firebase',
        ],

        /**
         * 알림 템플릿
         */
        'templates' => [
            'order_placed' => [
                'channels' => ['database', 'whatsapp', 'push'],
                'delay' => 0,
            ],
            'order_confirmed' => [
                'channels' => ['database', 'whatsapp', 'push'],
                'delay' => 0,
            ],
            'order_preparing' => [
                'channels' => ['database', 'push'],
                'delay' => 0,
            ],
            'order_out_for_delivery' => [
                'channels' => ['database', 'whatsapp', 'push'],
                'delay' => 0,
            ],
            'order_delivered' => [
                'channels' => ['database', 'whatsapp'],
                'delay' => 0,
            ],
        ],
    ],

    /**
     * 지도 및 위치 서비스
     */
    'location' => [
        /**
         * 지도 제공자
         */
        'provider' => env('MAP_PROVIDER', 'google'),

        /**
         * Google Maps 설정
         */
        'google' => [
            'api_key' => env('GOOGLE_MAPS_API_KEY'),
            'default_center' => [
                'lat' => 19.4326077,  // Mexico City
                'lng' => -99.133208,
            ],
            'default_zoom' => 13,
        ],

        /**
         * 지오코딩 설정
         */
        'geocoding' => [
            'enabled' => true,
            'cache_results' => true,
            'cache_ttl' => 86400, // 1 day
        ],
    ],

    /**
     * 미디어 설정
     */
    'media' => [
        /**
         * 이미지 크기 제한 (MB)
         */
        'max_image_size' => 5,

        /**
         * 허용 이미지 포맷
         */
        'allowed_image_formats' => ['jpg', 'jpeg', 'png', 'webp'],

        /**
         * 이미지 변환 설정
         */
        'conversions' => [
            'thumbnail' => [
                'width' => 150,
                'height' => 150,
                'quality' => 75,
            ],
            'preview' => [
                'width' => 500,
                'height' => 500,
                'quality' => 85,
            ],
            'banner' => [
                'width' => 1200,
                'height' => 400,
                'quality' => 90,
            ],
        ],

        /**
         * CDN 설정
         */
        'cdn' => [
            'enabled' => env('CDN_ENABLED', false),
            'url' => env('CDN_URL'),
        ],
    ],

    /**
     * 보안 설정
     */
    'security' => [
        /**
         * API Rate Limiting
         */
        'rate_limit' => [
            'enabled' => true,
            'requests_per_minute' => 60,
            'requests_per_hour' => 1000,
        ],

        /**
         * 2FA 설정
         */
        'two_factor' => [
            'enabled' => env('2FA_ENABLED', true),
            'required_for_admins' => true,
            'methods' => ['authenticator', 'sms'],
        ],

        /**
         * 비밀번호 정책
         */
        'password_policy' => [
            'min_length' => 8,
            'require_uppercase' => true,
            'require_lowercase' => true,
            'require_numbers' => true,
            'require_special_chars' => false,
        ],
    ],

    /**
     * 기능 플래그
     */
    'features' => [
        'multi_language' => env('FEATURE_MULTI_LANGUAGE', false),
        'loyalty_program' => env('FEATURE_LOYALTY_PROGRAM', true),
        'promotions' => env('FEATURE_PROMOTIONS', true),
        'subscription_plans' => env('FEATURE_SUBSCRIPTIONS', false),
        'table_reservation' => env('FEATURE_RESERVATIONS', false),
        'pickup_orders' => env('FEATURE_PICKUP', true),
        'scheduled_orders' => env('FEATURE_SCHEDULED_ORDERS', true),
        'group_orders' => env('FEATURE_GROUP_ORDERS', false),
    ],
];
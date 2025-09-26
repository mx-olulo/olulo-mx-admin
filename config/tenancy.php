<?php

return [
    /**
     * 테넌시 기본 설정
     */
    'enabled' => env('TENANCY_ENABLED', true),

    /**
     * 마스터 도메인 (관리자용)
     */
    'master_domain' => env('MASTER_DOMAIN', 'admin.olulo.mx'),

    /**
     * 테넌트 식별 방법
     * - 'subdomain': 서브도메인 기반 (restaurant.olulo.mx)
     * - 'domain': 커스텀 도메인 기반 (restaurant.com)
     * - 'path': 경로 기반 (/restaurant/...)
     */
    'identification' => env('TENANCY_IDENTIFICATION', 'subdomain'),

    /**
     * 기본 테넌트 도메인 템플릿
     * {tenant}는 테넌트 슬러그로 치환됨
     */
    'domain_template' => env('TENANCY_DOMAIN_TEMPLATE', '{tenant}.olulo.mx'),

    /**
     * 테넌트별 데이터베이스 분리
     * true: 테넌트별 독립 데이터베이스
     * false: 단일 데이터베이스 + 테넌트 ID 필터링
     */
    'database_per_tenant' => env('TENANCY_DATABASE_PER_TENANT', false),

    /**
     * 테넌트 테이블 설정
     */
    'tables' => [
        'tenants' => 'tenants',
        'tenant_domains' => 'tenant_domains',
        'tenant_settings' => 'tenant_settings',
    ],

    /**
     * 테넌트 모델 설정
     */
    'models' => [
        'tenant' => \App\Models\Tenant::class,
    ],

    /**
     * 테넌트 스코프가 적용될 모델들
     * 이 모델들은 자동으로 현재 테넌트로 필터링됨
     */
    'tenant_aware_models' => [
        \App\Models\Restaurant::class,
        \App\Models\Menu::class,
        \App\Models\Order::class,
        \App\Models\Customer::class,
        \App\Models\DeliveryPerson::class,
        \App\Models\Payment::class,
    ],

    /**
     * 전역 모델 (테넌트 스코프 제외)
     */
    'shared_models' => [
        \App\Models\User::class,  // 시스템 관리자
        \App\Models\SystemSetting::class,
        \App\Models\Currency::class,
        \App\Models\Country::class,
    ],

    /**
     * 테넌트 미들웨어 설정
     */
    'middleware' => [
        /**
         * 테넌트 식별 실패 시 리다이렉트 URL
         */
        'redirect_on_fail' => env('TENANCY_REDIRECT_ON_FAIL', '/'),

        /**
         * 테넌트 식별 실패 시 예외 발생 여부
         */
        'throw_on_fail' => env('TENANCY_THROW_ON_FAIL', false),

        /**
         * 제외할 경로 패턴
         */
        'exclude_paths' => [
            'api/health',
            'api/status',
            'telescope/*',
            'horizon/*',
            'pulse/*',
        ],
    ],

    /**
     * 테넌트 캐시 설정
     */
    'cache' => [
        /**
         * 테넌트 정보 캐시 TTL (초)
         */
        'ttl' => env('TENANCY_CACHE_TTL', 3600),

        /**
         * 테넌트 캐시 키 프리픽스
         */
        'prefix' => 'tenant',

        /**
         * 캐시 드라이버
         */
        'driver' => env('TENANCY_CACHE_DRIVER', 'redis'),
    ],

    /**
     * 테넌트 파일 저장소 설정
     */
    'filesystem' => [
        /**
         * 테넌트별 파일 분리
         */
        'separate_by_tenant' => true,

        /**
         * 테넌트 파일 루트 경로
         */
        'root' => storage_path('app/tenants'),

        /**
         * 공용 파일 디스크
         */
        'shared_disk' => 'public',
    ],

    /**
     * 테넌트 큐 설정
     */
    'queue' => [
        /**
         * 테넌트별 큐 분리
         */
        'separate_by_tenant' => false,

        /**
         * 테넌트 큐 프리픽스
         */
        'prefix' => 'tenant',
    ],

    /**
     * 테넌트 생성 시 기본 설정
     */
    'defaults' => [
        /**
         * 기본 언어
         */
        'locale' => 'es_MX',

        /**
         * 기본 시간대
         */
        'timezone' => 'America/Mexico_City',

        /**
         * 기본 통화
         */
        'currency' => 'MXN',

        /**
         * 기본 상태
         */
        'status' => 'pending',

        /**
         * 기본 설정값
         */
        'settings' => [
            'order_minimum' => 50.00,
            'delivery_fee' => 25.00,
            'service_fee_percentage' => 10,
            'tax_percentage' => 16,
            'max_delivery_distance' => 5000, // meters
            'operating_hours' => [
                'mon' => ['09:00', '22:00'],
                'tue' => ['09:00', '22:00'],
                'wed' => ['09:00', '22:00'],
                'thu' => ['09:00', '22:00'],
                'fri' => ['09:00', '23:00'],
                'sat' => ['09:00', '23:00'],
                'sun' => ['09:00', '21:00'],
            ],
        ],
    ],

    /**
     * 테넌트 제한 설정
     */
    'limits' => [
        /**
         * 최대 메뉴 아이템 수
         */
        'max_menu_items' => env('TENANCY_MAX_MENU_ITEMS', 500),

        /**
         * 최대 일일 주문 수
         */
        'max_daily_orders' => env('TENANCY_MAX_DAILY_ORDERS', 1000),

        /**
         * 최대 배달원 수
         */
        'max_delivery_persons' => env('TENANCY_MAX_DELIVERY_PERSONS', 50),

        /**
         * 최대 파일 저장 용량 (MB)
         */
        'max_storage_mb' => env('TENANCY_MAX_STORAGE_MB', 5000),
    ],

    /**
     * 테넌트 이벤트 설정
     */
    'events' => [
        /**
         * 테넌트 생성 시 실행할 작업
         */
        'tenant_created' => [
            \App\Jobs\Tenancy\CreateTenantDatabase::class,
            \App\Jobs\Tenancy\SeedTenantData::class,
            \App\Jobs\Tenancy\SendWelcomeEmail::class,
        ],

        /**
         * 테넌트 삭제 시 실행할 작업
         */
        'tenant_deleted' => [
            \App\Jobs\Tenancy\DeleteTenantDatabase::class,
            \App\Jobs\Tenancy\CleanupTenantFiles::class,
        ],
    ],
];

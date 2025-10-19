<?php

declare(strict_types=1);

// @CODE:I18N-001 | SPEC: SPEC-I18N-001.md | TEST: tests/Feature/I18n/TranslationCompletenessTest.php

/**
 * Filament 관련 다국어 메시지 (한국어)
 *
 * 각 Panel의 Dashboard 제목과 부제목을 정의합니다.
 */
return [
    'platform' => [
        'dashboard' => [
            'title' => '플랫폼 대시보드',
            'subheading' => '전체 시스템의 주요 메트릭과 통계를 확인할 수 있습니다',
        ],
    ],

    'system' => [
        'dashboard' => [
            'title' => '시스템 대시보드',
            'subheading' => '시스템 설정 및 운영 현황을 확인할 수 있습니다',
        ],
    ],

    'organization' => [
        'dashboard' => [
            'title' => '조직 대시보드',
            'subheading' => '조직 전체의 운영 현황과 주요 지표를 확인할 수 있습니다',
        ],
    ],

    'brand' => [
        'dashboard' => [
            'title' => '브랜드 대시보드',
            'subheading' => '브랜드 전체의 매출, 주문 현황 및 통계를 확인할 수 있습니다',
        ],
    ],

    'store' => [
        'dashboard' => [
            'title' => '매장 대시보드',
            'subheading' => '실시간 주문 현황, 매출 통계, 재고 상태를 확인할 수 있습니다',
        ],
    ],

    'organizations' => [
        'resource' => [
            'label' => '조직',
            'plural_label' => '조직',
            'navigation_label' => '조직',
        ],
        'fields' => [
            'name' => '이름',
            'description' => '설명',
            'contact_email' => '연락처 이메일',
            'contact_phone' => '연락처 전화번호',
            'is_active' => '활성 상태',
        ],
        'columns' => [
            'name' => '이름',
            'contact_email' => '이메일',
            'contact_phone' => '전화번호',
            'is_active' => '활성',
            'created_at' => '생성일',
            'updated_at' => '수정일',
        ],
        'actions' => [
            'activities' => '활동 로그',
            'back' => '조직으로 돌아가기',
        ],
        'activities' => [
            'title' => '활동 로그: :name',
            'event_types' => [
                'created' => '생성됨',
                'updated' => '수정됨',
                'deleted' => '삭제됨',
            ],
            'filters' => [
                'event_type' => '이벤트 유형',
            ],
            'columns' => [
                'event' => '이벤트',
                'user' => '사용자',
                'changes' => '변경사항',
                'date' => '날짜',
            ],
        ],
    ],

    'common' => [
        'actions' => [
            'view' => '보기',
            'edit' => '수정',
            'delete' => '삭제',
        ],
    ],
];

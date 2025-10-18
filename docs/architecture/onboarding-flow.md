# 사용자 온보딩 플로우 아키텍처

작성일: 2025-10-19
관련 문서: `/opt/GitHub/olulo-mx-admin/docs/research/user-onboarding-wizard.md`

## 시퀀스 다이어그램

### 1. 전체 온보딩 플로우

```mermaid
sequenceDiagram
    participant U as 사용자
    participant F as Firebase Auth
    participant L as Laravel/Sanctum
    participant M as Middleware
    participant W as Wizard Page
    participant S as OnboardingService
    participant DB as Database

    U->>F: 로그인 (FirebaseUI)
    F-->>U: ID Token 반환
    U->>L: POST /api/auth/firebase-login {idToken}
    L->>L: Firebase 토큰 검증
    L->>DB: User 조회/생성 (firebase_uid)
    L-->>U: 세션 확립 (Set-Cookie)

    U->>L: GET /store (패널 접근)
    L->>M: EnsureUserHasTenant 실행
    M->>DB: User::getTenants() 조회
    DB-->>M: 빈 컬렉션
    M-->>U: 302 Redirect /onboarding

    U->>W: GET /onboarding (위자드 표시)
    W-->>U: Step 1: 유형 선택
    U->>W: entity_type = 'organization'
    W-->>U: Step 2: 이름 입력
    U->>W: name = 'My Restaurant'

    U->>S: submit() 호출
    S->>DB: BEGIN TRANSACTION
    S->>DB: INSERT organizations (name)
    DB-->>S: organization.id = 1
    S->>DB: INSERT/SELECT roles (scope_type=ORG, scope_ref_id=1)
    DB-->>S: role.id = 5
    S->>DB: INSERT model_has_roles (user, role)
    S->>DB: COMMIT
    S-->>U: 302 Redirect /organization/1/dashboard

    U->>L: GET /organization/1/dashboard
    L->>M: EnsureUserHasTenant 실행
    M->>DB: User::getTenants() 조회
    DB-->>M: [Organization #1]
    M-->>U: 대시보드 표시 ✓
```

### 2. 테넌트 확인 로직

```mermaid
flowchart TD
    A[사용자 패널 접근] --> B{인증됨?}
    B -->|No| C[로그인 페이지]
    B -->|Yes| D{패널 타입?}

    D -->|Platform/System| E[글로벌 역할 확인]
    E -->|있음| F[패널 진입 ✓]
    E -->|없음| C

    D -->|Org/Brand/Store| G[getTenants 실행]
    G --> H{테넌트 있음?}
    H -->|Yes| F
    H -->|No| I[온보딩 위자드]

    I --> J[엔티티 생성]
    J --> K[Owner Role 부여]
    K --> L[대시보드 리디렉션]
    L --> F
```

### 3. Role 생성 및 할당

```mermaid
flowchart LR
    A[OnboardingService::createOrganization] --> B[Organization::create]
    B --> C{Owner Role 존재?}
    C -->|Yes| D[기존 Role 조회]
    C -->|No| E[Role::create]
    D --> F[User::assignRole]
    E --> F
    F --> G[model_has_roles INSERT]
    G --> H[트랜잭션 커밋]
```

## 데이터 모델 관계도

```mermaid
erDiagram
    USERS ||--o{ MODEL_HAS_ROLES : has
    ROLES ||--o{ MODEL_HAS_ROLES : assigned_to
    ROLES }o--|| ORGANIZATIONS : "scopeable (ORG)"
    ROLES }o--|| STORES : "scopeable (STORE)"
    ROLES }o--|| BRANDS : "scopeable (BRAND)"

    ORGANIZATIONS ||--o{ BRANDS : owns
    ORGANIZATIONS ||--o{ STORES : "owns (direct)"
    BRANDS ||--o{ STORES : owns

    USERS {
        bigint id PK
        string firebase_uid UK
        string email UK
        string name
        timestamp last_login_at
    }

    ROLES {
        bigint id PK
        string name
        string scope_type "ORG|STORE|BRAND"
        bigint scope_ref_id FK
        string guard_name
    }

    MODEL_HAS_ROLES {
        bigint role_id FK
        string model_type
        bigint model_id FK
    }

    ORGANIZATIONS {
        bigint id PK
        string name
        boolean is_active
    }

    STORES {
        bigint id PK
        bigint organization_id FK "nullable"
        bigint brand_id FK "nullable"
        string name
        boolean is_active
    }

    BRANDS {
        bigint id PK
        bigint organization_id FK
        string name
        boolean is_active
    }
```

## 컴포넌트 아키텍처

```mermaid
flowchart TB
    subgraph "Presentation Layer"
        A[OnboardingWizard Page]
        B[Wizard Component]
        C[Step: 유형 선택]
        D[Step: 정보 입력]
    end

    subgraph "Application Layer"
        E[EnsureUserHasTenant Middleware]
        F[OnboardingService]
    end

    subgraph "Domain Layer"
        G[User Model]
        H[Organization Model]
        I[Store Model]
        J[Role Model]
    end

    subgraph "Infrastructure Layer"
        K[(PostgreSQL)]
        L[Spatie Permission]
        M[Filament Tenancy]
    end

    A --> B
    B --> C
    B --> D
    A --> F
    E --> G
    F --> H
    F --> I
    F --> J
    G --> L
    H --> K
    I --> K
    J --> L
    L --> K
    M --> G
```

## API 엔드포인트

### 온보딩 관련 라우트

GET /onboarding
- 설명: 온보딩 위자드 페이지 표시
- 인증: 필수 (auth:web)
- 조건: 테넌트 없음
- 응답: Livewire 컴포넌트 렌더링

POST /livewire/message/onboarding-wizard
- 설명: Wizard 폼 제출 (Livewire 자동 생성)
- 인증: 필수 (auth:web)
- 바디:
  - entity_type: 'organization' | 'store'
  - name: string (max 255)
- 응답: 302 Redirect to dashboard

### 패널 진입 라우트

GET /organization/{tenant}/dashboard
- 설명: 조직 대시보드
- 인증: 필수 (auth:web)
- 미들웨어: EnsureUserHasTenant
- 조건: User가 해당 Organization의 멤버

GET /store/{tenant}/dashboard
- 설명: 매장 대시보드
- 인증: 필수 (auth:web)
- 미들웨어: EnsureUserHasTenant
- 조건: User가 해당 Store의 멤버

## 상태 전이도

```mermaid
stateDiagram-v2
    [*] --> NotAuthenticated
    NotAuthenticated --> Authenticated: Firebase Login
    Authenticated --> HasTenant: 기존 소속 있음
    Authenticated --> NoTenant: 소속 없음

    NoTenant --> OnboardingStep1: 위자드 진입
    OnboardingStep1 --> OnboardingStep2: 유형 선택
    OnboardingStep2 --> CreatingEntity: 제출
    CreatingEntity --> HasTenant: 생성 성공
    CreatingEntity --> OnboardingStep2: 실패 (재시도)

    HasTenant --> Dashboard: 패널 접근
    Dashboard --> [*]: 로그아웃
```

## 디렉터리 구조

```
app/
├── Filament/
│   ├── Pages/
│   │   └── OnboardingWizard.php          # 위자드 페이지
│   ├── Organization/
│   │   └── Pages/
│   │       └── Dashboard.php             # 조직 대시보드
│   └── Store/
│       └── Pages/
│           └── Dashboard.php             # 매장 대시보드
├── Http/
│   ├── Controllers/
│   │   └── Auth/
│   │       └── AuthController.php        # Firebase 인증
│   └── Middleware/
│       └── EnsureUserHasTenant.php       # 테넌트 확인
├── Models/
│   ├── User.php                          # getTenants, canAccessTenant
│   ├── Organization.php                  # HasCurrentTenantLabel
│   ├── Store.php                         # HasCurrentTenantLabel
│   └── Role.php                          # scopeable MorphTo
├── Providers/
│   └── Filament/
│       ├── OrganizationPanelProvider.php
│       └── StorePanelProvider.php
└── Services/
    └── OnboardingService.php             # 엔티티 생성 로직

resources/
└── views/
    └── filament/
        └── pages/
            ├── onboarding-wizard.blade.php
            └── onboarding-submit-button.blade.php

database/
└── migrations/
    ├── 2025_10_11_053015_create_organizations_table.php
    ├── 2025_10_11_053017_create_stores_table.php
    └── 2025_09_26_152355_create_permission_tables.php

tests/
└── Feature/
    └── Onboarding/
        ├── OnboardingWizardTest.php
        ├── CreateOrganizationTest.php
        └── CreateStoreTest.php
```

## 에러 처리 시나리오

### 1. 중복 생성 시도

**상황**: 사용자가 위자드를 여러 번 제출

**해결**:
- OnboardingService::createOrganization()에서 DB 트랜잭션 사용
- 실패 시 자동 롤백
- Filament 폼 에러 메시지 표시

### 2. 네트워크 타임아웃

**상황**: 생성 중 연결 끊김

**해결**:
- DB 트랜잭션 자동 롤백
- Livewire 재시도 메커니즘
- 사용자에게 재시도 안내

### 3. 이미 테넌트 보유

**상황**: 온보딩 중 다른 창에서 초대 수락

**해결**:
- OnboardingWizard::mount()에서 재확인
- 이미 소속이 있으면 대시보드로 리디렉션
- 중복 생성 방지

### 4. 권한 불일치

**상황**: Role 부여 실패

**해결**:
- DB 트랜잭션으로 엔티티 생성도 함께 롤백
- 감사 로그(Activity Log)에 오류 기록
- 관리자에게 알림

## 성능 메트릭

### 목표 응답 시간

- 위자드 페이지 로드: < 500ms
- Step 전환: < 200ms (Livewire 인메모리)
- 엔티티 생성: < 1000ms
- 대시보드 리디렉션: < 300ms

### 데이터베이스 쿼리 최적화

1. **getTenants()**:
   - Eager Loading: `roles()->with('scopeable')`
   - 인덱스: `(scope_type, scope_ref_id)`
   - 예상 쿼리 수: 2개 (roles 조회 + scopeable 조회)

2. **createOrganization()**:
   - 쿼리 수: 3개 (INSERT org, SELECT/INSERT role, INSERT model_has_roles)
   - 트랜잭션 내 실행으로 일관성 보장

3. **canAccessTenant()**:
   - 단일 EXISTS 쿼리
   - 인덱스 활용으로 < 10ms

## 보안 체크포인트

### OWASP Top 10 대응

1. **A01:2021 – Broken Access Control**
   - EnsureUserHasTenant 미들웨어로 방지
   - canAccessTenant() 이중 확인

2. **A03:2021 – Injection**
   - Eloquent ORM 사용 (파라미터 바인딩)
   - 폼 입력 검증 (Filament 내장)

3. **A04:2021 – Insecure Design**
   - 온보딩 플로우 상태 전이 명확히 정의
   - 롤백 가능한 트랜잭션 설계

4. **A05:2021 – Security Misconfiguration**
   - CSRF 보호 (Sanctum 자동)
   - HTTPS 강제 (환경별 설정)

5. **A07:2021 – Identification and Authentication Failures**
   - Firebase Auth 2FA 지원
   - 세션 만료 정책 (config/session.php)

### 감사 로그

Spatie Activity Log 활용:

- Organization 생성: `organization.created`
- Store 생성: `store.created`
- Role 부여: `role.assigned` (커스텀 이벤트)
- 온보딩 완료: `user.onboarded` (커스텀 이벤트)

## 테스트 전략

### Unit Tests

- `OnboardingService::createOrganization()` 단위 테스트
- `OnboardingService::createStore()` 단위 테스트
- `User::getTenants()` 결과 검증
- `User::canAccessTenant()` 권한 로직 검증

### Feature Tests

- 소속 없는 사용자 패널 접근 시 온보딩 리디렉션
- 조직 생성 후 Owner Role 부여 확인
- 매장 생성 후 Owner Role 부여 확인
- 이미 소속이 있는 사용자 온보딩 건너뜀
- 위자드 폼 검증 (빈 이름, 긴 이름 등)

### Integration Tests

- Firebase Auth → Laravel 세션 → 온보딩 전체 플로우
- Livewire 컴포넌트 상호작용 테스트

### E2E Tests (Dusk)

- 브라우저에서 전체 온보딩 시나리오
- FirebaseUI 모달 상호작용
- 위자드 단계별 진행 및 뒤로가기
- 대시보드 진입 확인

## 참조

- 기술 연구 보고서: `/opt/GitHub/olulo-mx-admin/docs/research/user-onboarding-wizard.md`
- Filament Tenancy: https://filamentphp.com/docs/4.x/panels/tenancy
- Spatie Permission: https://spatie.be/docs/laravel-permission/v6
- Laravel 12 Middleware: https://laravel.com/docs/12.x/middleware

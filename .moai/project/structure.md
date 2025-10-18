---
id: STRUCTURE-001
version: 0.2.0
status: active
created: 2025-10-01
updated: 2025-10-19
author: @Goos
priority: high
category: feature
labels:
  - architecture
  - multi-tenant
  - hybrid
  - laravel
  - react
---

# olulo-mx-admin Structure Design

## HISTORY

### v0.2.0 (2025-10-19)
- **UPDATED**: 템플릿 기본값을 실제 프로젝트 아키텍처로 전면 갱신
- **AUTHOR**: @Goos
- **SECTIONS**: Architecture, Modules, Integration, Traceability 모두 실제 내용 반영
- **REASON**: 레거시 프로젝트 도입으로 MoAI-ADK 초기화 작업

### v0.1.1 (2025-10-17)
- **UPDATED**: 템플릿 버전 동기화 (v0.3.8)
- **AUTHOR**: @Alfred
- **SECTIONS**: 메타데이터 표준화 (author 필드 단수형, priority 추가)

### v0.1.0 (2025-10-01)
- **INITIAL**: 프로젝트 구조 설계 문서 작성
- **AUTHOR**: @architect
- **SECTIONS**: Architecture, Modules, Integration, Traceability

---

## @DOC:ARCHITECTURE-001 시스템 아키텍처

### 아키텍처 전략

**하이브리드 멀티 테넌시 아키텍처**: Laravel 백엔드 + React SPA + Filament Admin + Nova Master Admin

이 프로젝트는 **5단계 멀티 테넌시 계층화**와 **듀얼 프론트엔드**(관리자용 Filament, 고객용 React)를 결합한 하이브리드 아키텍처를 채택합니다.

Olulo MX Admin Architecture
├── Backend (Laravel 12)                # API & Admin Backend
│   ├── Filament 4 (5 Panels)          # Multi-tenant Admin UI
│   │   ├── System Panel               # Superadmin (전체 시스템)
│   │   ├── Platform Panel             # Platform Admin (플랫폼 운영)
│   │   ├── Organization Panel         # Organization Admin (파트너사)
│   │   ├── Brand Panel                # Brand Manager (브랜드)
│   │   └── Store Panel (Default)     # Store Owner (매장, Tenant)
│   ├── Laravel Nova 5                 # Master Admin (통합 모니터링)
│   ├── Firebase Auth Integration      # Firebase UID → Laravel User
│   ├── Sanctum SPA Authentication     # Session-based auth for SPA
│   └── Spatie Packages                # Permission, Activity Log, Media
│
├── Frontend (React 19.1 + Inertia.js) # Customer Web App
│   ├── Firebase Authentication        # Google, Phone 간편 로그인
│   ├── Inertia.js 2.0                 # Laravel-React 통합
│   ├── Tailwind 4 + lucide-react      # UI Components
│   └── Vite 7                         # Fast HMR & Build
│
├── Infrastructure
│   ├── PostgreSQL                     # Primary Database
│   ├── Redis                          # Cache & Queue
│   ├── Laravel Horizon                # Queue Monitoring
│   └── Laravel Telescope              # Debug & Monitoring
│
└── Quality Assurance
    ├── Pest 3.8 + PHPUnit 11.5        # Testing Framework
    ├── Laravel Pint 1.24               # Code Formatter
    ├── Larastan 3.7 (PHPStan Level 8) # Static Analysis
    └── Rector 2.2                      # Automated Refactoring

**선택 이유**:
- **멀티 테넌시**: Filament의 테넌트 기능으로 매장(Store)을 기준으로 데이터 격리
- **하이브리드 프론트엔드**: 관리자는 Filament (서버 렌더링), 고객은 React SPA (빠른 UX)
- **Firebase + Laravel 통합**: Firebase의 간편 인증 + Laravel의 강력한 권한 관리
- **품질 우선**: Pest, Pint, Larastan, Rector로 품질 게이트 자동화

**트레이드오프**:
- **복잡성 증가**: 5개 Filament 패널 + Nova + React 관리 필요
- **학습 곡선**: Laravel + React + Filament + Firebase 모두 이해 필요
- **배포 복잡도**: Backend와 Frontend 빌드 프로세스 분리
- **이점**: 역할별 최적화된 UI/UX, 확장성, 유지보수성

## @DOC:MODULES-001 모듈별 책임 구분

### 1. Filament Multi-Panel Module (관리자 UI)

- **책임**: 5단계 계층화된 관리자 인터페이스 제공
- **입력**: 사용자 역할 (System Admin, Platform Admin, Organization Admin, Brand Manager, Store Owner)
- **처리**: 역할별 접근 가능한 패널 및 리소스 필터링
- **출력**: 역할 맞춤형 관리 대시보드 및 CRUD

| 컴포넌트 | 역할 | 주요 기능 |
|----------|------|-----------|
| **SystemPanelProvider** | System Admin 패널 | - 전체 시스템 설정 관리<br>- 사용자 권한 관리 (Spatie Permission)<br>- 활동 로그 모니터링 (Spatie Activity Log) |
| **PlatformPanelProvider** | Platform Admin 패널 | - 플랫폼 전체 통계<br>- 신규 파트너사 승인<br>- 고객 지원 및 분쟁 조정 |
| **OrganizationPanelProvider** | Organization Admin 패널 | - 조직 산하 브랜드 관리<br>- 전체 매출 및 수수료 정산<br>- 조직 정책 설정 |
| **BrandPanelProvider** | Brand Manager 패널 | - 브랜드 산하 매장 관리<br>- 메뉴 표준화 및 동기화<br>- 브랜드 프로모션 설정 |
| **StorePanelProvider** (Default) | Store Owner 패널 | - 매장 주문 관리<br>- 메뉴 및 재고 관리<br>- 매출 리포트 |
| **ConfiguresFilamentPanel** (Trait) | 공통 설정 | - 미들웨어 체인 (세션, CSRF, 인증)<br>- Firebase 로그인 리디렉션<br>- 공통 위젯 (AccountWidget, FilamentInfoWidget) |

### 2. Laravel Nova Module (마스터 관리자)

- **책임**: 전체 시스템 모니터링 및 고급 분석
- **입력**: Nova Admin 권한 사용자
- **처리**: 모든 테넌트 데이터 조회 및 분석
- **출력**: 통합 대시보드, 매출 분석, 사용자 통계

| 컴포넌트 | 역할 | 주요 기능 |
|----------|------|-----------|
| **Nova Resources** | 모든 모델 리소스 | - 전체 테넌트 데이터 조회<br>- 고급 필터링 및 검색<br>- 벌크 작업 |
| **Nova Dashboards** | 통합 대시보드 | - 매출 통계<br>- 주문 트렌드<br>- 사용자 활동 |
| **Nova Actions** | 관리 작업 | - 일괄 상태 변경<br>- 데이터 엑스포트<br>- 알림 발송 |

### 3. Firebase Auth Integration Module

- **책임**: Firebase 인증과 Laravel User 동기화
- **입력**: Firebase ID Token (클라이언트 전송)
- **처리**:
  - ID Token 검증 (verifyIdToken / verifyIdTokenLenient)
  - Firebase User → Laravel User 동기화
  - Laravel Sanctum SPA 세션 생성
- **출력**: 인증된 Laravel User 객체, Sanctum Token

| 컴포넌트 | 역할 | 주요 기능 |
|----------|------|-----------|
| **FirebaseAuthService** | 인증 핵심 로직 | - ID Token 검증<br>- Firebase User CRUD<br>- Laravel User 동기화 |
| **FirebaseClientFactory** | Firebase 클라이언트 생성 | - Auth 클라이언트 생성<br>- Messaging 클라이언트 생성<br>- Database 클라이언트 생성 |
| **AuthController** | 인증 엔드포인트 | - POST /auth/login<br>- POST /auth/logout<br>- GET /auth/user |

### 4. React SPA Module (고객 웹앱)

- **책임**: 고객용 음식 주문 인터페이스
- **입력**: 고객 상호작용 (메뉴 조회, 주문, 결제)
- **처리**:
  - Firebase 간편 로그인 (Google, Phone)
  - 레스토랑/메뉴 검색
  - 주문 및 결제 플로우
- **출력**: 주문 완료, 결제 완료, 알림 수신

| 컴포넌트 | 역할 | 주요 기능 |
|----------|------|-----------|
| **Firebase Auth (Client)** | 클라이언트 인증 | - FirebaseUI 로그인 UI<br>- ID Token 발급<br>- 세션 유지 |
| **Inertia Pages** | React 페이지 컴포넌트 | - 레스토랑 목록<br>- 메뉴 상세<br>- 주문 카트<br>- 결제 페이지 |
| **Inertia Layouts** | 공통 레이아웃 | - 헤더/푸터<br>- 네비게이션<br>- 로딩 상태 |

### 5. Quality Assurance Module

- **책임**: 코드 품질 자동 검증 및 개선
- **입력**: 소스 코드 (PHP, TypeScript)
- **처리**:
  - 테스트 실행 (Pest)
  - 코드 포매팅 (Pint)
  - 정적 분석 (Larastan)
  - 리팩토링 제안 (Rector)
- **출력**: 테스트 리포트, 품질 지표, 개선 제안

| 컴포넌트 | 역할 | 주요 기능 |
|----------|------|-----------|
| **Pest 3.8** | PHP 테스트 프레임워크 | - Feature/Unit 테스트<br>- 병렬 실행 지원<br>- 커버리지 측정 |
| **Laravel Pint 1.24** | 코드 포매터 | - PSR-12 준수<br>- 자동 포매팅<br>- CI 통합 |
| **Larastan 3.7** | 정적 분석 | - PHPStan Level 8<br>- 타입 안전성 검증<br>- 버그 사전 감지 |
| **Rector 2.2** | 자동 리팩토링 | - 코드 현대화<br>- 베스트 프랙티스 적용<br>- 기술 부채 감소 |

## @DOC:INTEGRATION-001 외부 시스템 통합

### Firebase Authentication 연동

- **인증 방식**: Firebase ID Token (JWT) 기반 인증
- **데이터 교환**:
  - 클라이언트 → 백엔드: ID Token (HTTP Header: Authorization Bearer)
  - 백엔드 → Firebase: Token Verification API
- **장애 시 대체**:
  - 프로덕션: Token 검증 실패 시 401 Unauthorized
  - 로컬 개발: verifyIdTokenLenient()로 에뮬레이터 토큰 허용 (서명 없는 토큰)
- **위험도**: 중간
  - **리스크**: Firebase 서비스 장애 시 신규 로그인 불가
  - **완화**: 기존 Laravel 세션 유지로 인증된 사용자는 계속 사용 가능

### operacionesenlinea.com (멕시코 결제 게이트웨이) 연동

- **용도**: 고객 주문 결제 처리
- **인증 방식**: API Key 기반 인증
- **데이터 교환**: REST API (JSON)
- **의존성 수준**: 높음 (결제 필수)
  - **대안**: Twilio Payments (향후 확장)
- **성능 요구사항**:
  - 응답 시간: < 5초
  - 처리량: 초당 100건

### WhatsApp Cloud API (Meta) 연동

- **용도**: 주문 상태 알림 발송
- **인증 방식**: WhatsApp Business API Token
- **데이터 교환**: REST API (JSON)
- **장애 시 대체**: Twilio WhatsApp API (대안)
- **위험도**: 낮음
  - **리스크**: 알림 미발송
  - **완화**: 앱 내 알림으로 보완

### PostgreSQL 연동

- **용도**: Primary Database
- **인증 방식**: Database Credentials (.env)
- **데이터 교환**: PostgreSQL Protocol
- **성능 요구사항**:
  - 쿼리 응답: < 100ms
  - 동시 연결: 최대 200개
- **위험도**: 높음
  - **리스크**: DB 장애 시 전체 서비스 중단
  - **완화**: Read Replica, 백업 자동화

## @DOC:TRACEABILITY-001 추적성 전략

### TAG 체계 적용

**TDD 완벽 정렬**: SPEC → 테스트 → 구현 → 문서
- `@SPEC:ID` (.moai/specs/) → `@TEST:ID` (tests/) → `@CODE:ID` (app/) → `@DOC:ID` (docs/)

**구현 세부사항**: @CODE:ID 내부 주석 레벨
- `@CODE:ID:API` - REST API, GraphQL 엔드포인트 (routes/api.php, routes/web.php)
- `@CODE:ID:UI` - Filament Resources, React Components (app/Filament/*, resources/js/Pages/*)
- `@CODE:ID:DATA` - Eloquent Models, Migrations (app/Models/*, database/migrations/*)
- `@CODE:ID:DOMAIN` - Services, Actions (app/Services/*, app/Actions/*)
- `@CODE:ID:INFRA` - Providers, Middleware (app/Providers/*, app/Http/Middleware/*)

### TAG 추적성 관리 (코드 스캔 방식)

- **검증 방법**: `/alfred:3-sync` 실행 시 `rg '@(SPEC|TEST|CODE|DOC):' -n`으로 코드 전체 스캔
- **추적 범위**:
  - `.moai/specs/` - SPEC 문서
  - `tests/` - Pest/PHPUnit 테스트
  - `app/` - Laravel 애플리케이션 코드
  - `resources/js/` - React 컴포넌트
  - `docs/` - 기술 문서
- **유지 주기**: 코드 변경 시점마다 실시간 검증
- **CODE-FIRST 원칙**: TAG의 진실은 코드 자체에만 존재

### Spatie Activity Log 통합

- **추적 대상**: 모든 주요 관리자 작업
- **로그 내용**:
  - 사용자 ID
  - 작업 타입 (created, updated, deleted)
  - 변경된 모델 및 필드
  - IP 주소, User Agent
  - 타임스탬프
- **보존 기간**: 1년 (이후 아카이빙)

## Legacy Context

### 기존 시스템 현황

이 프로젝트는 **신규 프로젝트**이지만, 다음 기존 자산을 활용합니다:

**현재 구현된 구조**:

app/
├── Providers/Filament/                # Filament 패널 Provider
│   ├── SystemPanelProvider.php        # System Admin 패널
│   ├── PlatformPanelProvider.php      # Platform Admin 패널
│   ├── OrganizationPanelProvider.php  # Organization Admin 패널
│   ├── BrandPanelProvider.php         # Brand Manager 패널
│   ├── StorePanelProvider.php         # Store Owner 패널 (Default)
│   └── Concerns/
│       └── ConfiguresFilamentPanel.php # 공통 패널 설정 Trait
├── Services/Firebase/                 # Firebase 통합 서비스
│   ├── FirebaseAuthService.php        # 인증 서비스
│   ├── FirebaseClientFactory.php      # 클라이언트 팩토리
│   ├── FirebaseMessagingService.php   # 메시징 서비스
│   └── FirebaseDatabaseService.php    # 데이터베이스 서비스
├── Http/Controllers/Auth/             # 인증 컨트롤러
│   └── AuthController.php             # Firebase 로그인/로그아웃
├── Models/                            # Eloquent 모델
│   ├── User.php                       # firebase_uid 필드 포함
│   └── Store.php                      # Tenant 모델
└── Filament/                          # Filament 리소스
    ├── System/Resources/              # System 패널 리소스
    ├── Platform/Resources/            # Platform 패널 리소스
    ├── Organization/Resources/        # Organization 패널 리소스
    ├── Brand/Resources/               # Brand 패널 리소스
    └── Store/                         # Store 패널 (Default)
        ├── Resources/                 # 리소스
        ├── Pages/                     # 커스텀 페이지
        │   └── OnboardingWizard.php   # 온보딩 마법사
        └── Widgets/                   # 위젯

resources/js/
├── Pages/                             # Inertia.js React Pages
│   ├── Auth/                          # 인증 페이지
│   ├── Restaurant/                    # 레스토랑 목록/상세
│   ├── Order/                         # 주문 페이지
│   └── Payment/                       # 결제 페이지
└── Components/                        # 공통 컴포넌트
    ├── Layout/                        # 레이아웃
    └── UI/                            # UI 컴포넌트

### 마이그레이션 고려사항

1. **Firebase Emulator 로컬 환경 설정** - 로컬 개발 경험 개선
2. **테스트 커버리지 확보** - 현재 테스트 코드 부족, 목표 85%
3. **문서 동기화 자동화** - Review Checks 워크플로우 확장

## TODO:STRUCTURE-001 구조 개선 계획

1. **모듈 간 인터페이스 정의** - Firebase Service 인터페이스 추상화
2. **의존성 관리 전략** - Service Container 활용, 테스트 가능성 향상
3. **확장성 확보 방안** - Repository Pattern 도입 고려

## EARS 아키텍처 요구사항 작성법

### 구조 설계에서의 EARS 활용

아키텍처와 모듈 설계 시 EARS 구문을 활용하여 명확한 요구사항을 정의하세요:

#### 시스템 아키텍처 EARS 예시

### Ubiquitous Requirements (아키텍처 기본 요구사항)
- 시스템은 5단계 멀티 테넌시 계층화를 제공해야 한다 (System → Platform → Organization → Brand → Store)
- 시스템은 하이브리드 프론트엔드 아키텍처를 채택해야 한다 (Filament Admin + React SPA)

### Event-driven Requirements (이벤트 기반 구조)
- WHEN Firebase 인증이 성공하면, 시스템은 Laravel User를 동기화하고 Sanctum 세션을 생성해야 한다
- WHEN 고객이 주문을 완료하면, 시스템은 매장 패널에 실시간 알림을 전송해야 한다

### State-driven Requirements (상태 기반 구조)
- WHILE 사용자가 Store Owner 역할일 때, 시스템은 Store Panel만 접근을 허용해야 한다
- WHILE 로컬 개발 환경일 때, 시스템은 Firebase Emulator 토큰을 허용해야 한다 (verifyIdTokenLenient)

### Optional Features (선택적 구조)
- WHERE Nova Admin 권한이 있으면, 시스템은 모든 테넌트 데이터 조회를 허용할 수 있다
- WHERE 브랜드가 여러 매장을 보유하면, 시스템은 통합 대시보드를 제공할 수 있다

### Constraints (구조적 제약사항)
- IF 역할이 Store Owner이면, 시스템은 다른 매장 데이터 접근을 차단해야 한다
- 각 Filament 패널은 독립적인 라우트와 미들웨어를 가져야 한다
- 모든 Firebase 인증 요청은 HTTPS를 사용해야 한다

---

_이 구조는 `/alfred:2-build` 실행 시 TDD 구현의 가이드라인이 됩니다._

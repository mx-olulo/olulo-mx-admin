# 구현 계획: 3티어 사용자 권한 모델 리팩토링

**브랜치**: `001-refactor-user-rbac-model` | **날짜**: 2025-10-20 | **스펙**: [spec.md](./spec.md)
**입력**: `/specs/001-refactor-user-rbac-model/spec.md`의 기능 명세

**참고**: 이 템플릿은 `/speckit.plan` 명령어로 작성됩니다. 실행 워크플로우는 `.specify/templates/commands/plan.md`를 참조하세요.

## 요약

현재 Spatie Permissions 패키지에 의존하는 권한 관리 시스템을 제거하고, Admin/User/Customer 3티어 사용자 모델 기반의 경량화된 자체 권한 시스템으로 전환합니다. Admin은 tenant_users 피벗 테이블을 통해 Organization/Brand/Store 멀티테넌트에 M:N 관계로 접근하며, 각 테넌트별로 독립적인 역할(owner/manager/viewer)을 보유합니다. User는 Platform/System 글로벌 패널만 접근 가능하며, Customer는 Firebase 인증을 통한 API 접근만 허용됩니다. Policy 클래스를 모두 제거하고 User 모델의 메서드로 권한 체크를 단순화하여 유지보수성과 성능을 개선합니다.

## 기술적 컨텍스트

**언어/버전**: PHP 8.2+
**주요 의존성**:
  - Laravel Framework 12.x
  - Filament v4 (Admin 패널 - Organization/Brand/Store)
  - Laravel Nova v5 (User 패널 - Platform/System)
  - Laravel Sanctum v4 (API 인증 - Admin/User 세션)
  - Kreait Firebase PHP v7.22 (Customer 인증)
  - Spatie Activity Log v4.10 (감사 로깅 - 유지)
  - **제거 예정**: Spatie Laravel Permission v6.10

**저장소**: PostgreSQL 15+ (RDBMS), Redis (세션/캐시/큐)

**테스트**: Pest v3 (Feature/Unit 테스트), Laravel Dusk v8.3 (E2E 테스트)

**대상 플랫폼**: Linux server (Laravel 12 호스팅 환경)

**프로젝트 타입**: 웹 애플리케이션 (Monolithic Laravel with Filament + Nova)

**성능 목표**:
  - Admin 테넌트 전환 평균 응답 <500ms
  - 권한 체크 쿼리 평균 2개 이하
  - Spatie 제거 후 composer 의존성 크기 15% 이상 감소

**제약사항**:
  - Filament 세션 기반 테넌트 컨텍스트 (탭별 독립성 불가)
  - Laravel morphMap 폴리모픽 관계 (테넌트 타입: ORG/BRD/STR)
  - Firebase 인증과 Sanctum 세션 guard 분리 필요
  - 기존 Customer Firebase 인증 무중단 유지

**규모/범위**:
  - 사용자: ~1000명 (Admin 500, User 50, Customer 450)
  - 테넌트: Organization 20개, Brand 50개, Store 200개
  - 코드 제거: Policy 클래스 3개 + Spatie 관련 300+ LOC

## 헌장 준수 검토

*게이트: Phase 0 연구 전에 통과해야 함. Phase 1 설계 후 재확인.*

**Olulo MX Admin 프로젝트 헌장 준수 체크리스트**:

- [x] **I. 문서 우선 개발**: spec.md 작성 완료, 이제 plan.md 작성 중
- [x] **II. 한국어 의사소통**: 모든 문서 및 커밋 메시지 한국어 작성
- [x] **III. 코드 품질 & 정적 분석**: Rector, Pint, PHPStan 적용 예정 (구현 시 검증)
- [x] **IV. 멀티테넌시 & 도메인 모델**: tenant_users 피벗으로 테넌트 격리 유지
- [x] **V. 보안 & 컴플라이언스**: Firebase/Sanctum 인증 분리, 감사 로그 유지
- [x] **VI. 원자적 변경 & PR 규율**: 1 PR = 1 목적 (권한 시스템 리팩토링)

**프로젝트별 추가 고려사항**:
- [x] **멀티테넌시 격리**: tenant_users 테이블로 M:N 관계 및 역할 정의 (tenant_type, tenant_id, role)
- [x] **관찰성 (Activity Log)**: 권한 변경(역할 할당/제거) 시 Spatie Activity Log 기록
- [N/A] **드라이버 패턴**: 이 기능은 인증 리팩토링으로 드라이버 패턴 해당 없음
- [x] **성능 최적화**: Spatie 제거로 쿼리 단순화, 평균 2개 이하 쿼리로 권한 체크
- [x] **Firebase 인증 유지**: Customer의 Firebase 인증 흐름 무중단 유지

**위반사항 및 정당화**: 없음 - 모든 헌장 원칙 준수

## 프로젝트 구조

### 문서 (이 기능)

```
specs/[###-feature]/
├── plan.md              # 이 파일 (/speckit.plan 명령어 출력)
├── research.md          # Phase 0 출력 (/speckit.plan 명령어)
├── data-model.md        # Phase 1 출력 (/speckit.plan 명령어)
├── quickstart.md        # Phase 1 출력 (/speckit.plan 명령어)
├── contracts/           # Phase 1 출력 (/speckit.plan 명령어)
└── tasks.md             # Phase 2 출력 (/speckit.tasks 명령어 - /speckit.plan으로 생성되지 않음)
```

### 소스 코드 (저장소 루트)

**Laravel Monolithic 구조 (Filament + Nova 통합)**:

```
app/
├── Models/
│   ├── User.php                    # 수정: HasRoles trait 제거, 자체 관계 메서드 추가
│   ├── Role.php                    # 제거 예정 (Spatie 기반)
│   ├── TenantUser.php              # 신규: 피벗 모델 (user_id, tenant_type, tenant_id, role)
│   ├── Organization.php            # 수정: tenantUsers 관계 추가
│   ├── Brand.php                   # 수정: tenantUsers 관계 추가
│   └── Store.php                   # 수정: tenantUsers 관계 추가
│
├── Enums/
│   ├── UserType.php                # 신규: Admin, User, Customer enum
│   └── TenantRole.php              # 신규: owner, manager, viewer enum
│
├── Policies/                       # 제거 예정 디렉토리
│   ├── OrganizationPolicy.php     # 제거
│   ├── BrandPolicy.php            # 제거
│   └── StorePolicy.php            # 제거
│
├── Services/
│   ├── TenantAuthService.php      # 신규: 테넌트 권한 검증 로직
│   └── FirebaseAuthService.php    # 유지: Customer 인증
│
├── Filament/                       # Admin 패널 (Organization/Brand/Store)
│   ├── Organization/
│   ├── Brand/
│   └── Store/
│
└── Nova/                           # User 패널 (Platform/System)
    ├── Platform/
    └── System/

database/
├── migrations/
│   ├── 2025_09_26_152355_create_permission_tables.php    # 제거
│   ├── YYYY_MM_DD_create_tenant_users_table.php          # 신규
│   ├── YYYY_MM_DD_add_user_type_to_users_table.php       # 신규
│   └── YYYY_MM_DD_migrate_roles_to_tenant_users.php      # 신규 (데이터 마이그레이션)
│
└── seeders/
    ├── TenantRoleSeeder.php        # 신규: owner/manager/viewer 역할 시드
    └── GlobalRoleSeeder.php        # 신규: platform_admin/system_admin 역할 시드

tests/
├── Feature/
│   ├── Auth/
│   │   ├── AdminTenantAccessTest.php      # 신규
│   │   ├── UserGlobalAccessTest.php       # 신규
│   │   └── CustomerFirebaseAuthTest.php   # 수정
│   └── Tenancy/
│       ├── TenantUserTest.php             # 신규
│       └── MultiTenantRoleTest.php        # 신규
│
└── Unit/
    ├── Models/
    │   ├── UserTest.php                   # 수정
    │   └── TenantUserTest.php             # 신규
    └── Services/
        └── TenantAuthServiceTest.php      # 신규
```

**구조 결정**: Laravel Monolithic 아키텍처를 유지하며, Spatie Permissions 의존성을 제거하고 자체 tenant_users 피벗 테이블 기반 권한 시스템으로 전환합니다. Policy 클래스 디렉토리를 제거하고 User 모델에 권한 체크 메서드를 직접 구현합니다.

## 복잡도 추적

*원칙 검토에서 정당화가 필요한 위반사항이 있는 경우에만 작성*

**위반사항 없음** - 모든 헌장 원칙 준수


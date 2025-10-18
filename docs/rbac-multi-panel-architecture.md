# RBAC 다중 Panel 아키텍처

## 개요

스코프 타입(Platform/System/Organization/Brand/Store)별로 독립적인 Filament Panel을 구성하여, 각 관리 영역에 맞는 UI/UX와 권한 분리를 제공합니다.

---

## 설계 원칙

### 1. 스코프 타입별 Panel 분리

- **Platform Panel**: 플랫폼 운영사 (고객사 관리, 정산, 통계, 모니터링)
- **System Panel**: 시스템 관리자 (서버 설정, 사용자 관리, 로그 조회, 배포)
- **Organization Panel**: 조직 전체 관리 (조직 정보, 소속 브랜드 관리, 조직 사용자 관리)
- **Brand Panel**: 브랜드 관리 (브랜드 정보, 소속 매장 관리, 브랜드 상품 관리)
- **Store Panel**: 매장 운영 (매장 정보, 재고 관리, 주문 처리, 매출 관리)

### 2. URL 구조

각 Panel은 명확한 URL 경로를 가집니다:

- Platform: `/platform/{platform_id}/dashboard`
- System: `/system/{system_id}/dashboard`
- Organization: `/organization/{organization_id}/dashboard`
- Brand: `/brand/{brand_id}/dashboard`
- Store: `/store/{store_id}/dashboard`

이를 통해:
- 사용자가 현재 어떤 관리 영역에 있는지 URL만으로 즉시 파악 가능
- 브라우저 히스토리/북마크에서 컨텍스트 유지
- 각 Panel 간 명확한 경계 설정

### 3. 리소스 분리

각 Panel은 독립적인 리소스/페이지/위젯을 소유합니다:

```
app/Filament/
├── Platform/
│   ├── Resources/     (고객사 관리, 정산)
│   ├── Pages/         (플랫폼 대시보드)
│   └── Widgets/       (플랫폼 통계 위젯)
├── System/
│   ├── Resources/     (시스템 설정, 사용자)
│   ├── Pages/         (시스템 대시보드)
│   └── Widgets/       (시스템 모니터링 위젯)
├── Organization/
│   ├── Resources/     (조직 관련 리소스)
│   ├── Pages/         (조직 대시보드/설정)
│   └── Widgets/       (조직 통계 위젯)
├── Brand/
│   ├── Resources/     (브랜드 관련 리소스)
│   ├── Pages/         (브랜드 대시보드/설정)
│   └── Widgets/       (브랜드 통계 위젯)
└── Store/
    ├── Resources/     (매장 관련 리소스)
    ├── Pages/         (매장 대시보드/설정)
    └── Widgets/       (매장 통계 위젯)
```

---

## 아키텍처 구성요소

### 1. Panel Provider

각 스코프 타입마다 독립적인 PanelProvider를 생성합니다:

- **PlatformPanelProvider**: 플랫폼 Panel 설정
  - Panel ID: `platform`
  - 경로: `/platform`
  - 테넌트 라우트 프리픽스: `platform`
  - 리소스 디렉터리: `app/Filament/Platform`

- **SystemPanelProvider**: 시스템 Panel 설정
  - Panel ID: `system`
  - 경로: `/system`
  - 테넌트 라우트 프리픽스: `system`
  - 리소스 디렉터리: `app/Filament/System`

- **OrganizationPanelProvider**: 조직 Panel 설정
  - Panel ID: `organization`
  - 경로: `/organization`
  - 테넌트 라우트 프리픽스: `organization`
  - 리소스 디렉터리: `app/Filament/Organization`

- **BrandPanelProvider**: 브랜드 Panel 설정
  - Panel ID: `brand`
  - 경로: `/brand`
  - 테넌트 라우트 프리픽스: `brand`
  - 리소스 디렉터리: `app/Filament/Brand`

- **StorePanelProvider**: 매장 Panel 설정
  - Panel ID: `store`
  - 경로: `/store`
  - 테넌트 라우트 프리픽스: `store`
  - 리소스 디렉터리: `app/Filament/Store`

### 2. 스코프 검증 미들웨어

각 Panel에는 해당 스코프 타입만 접근 가능하도록 검증 미들웨어를 추가합니다:

- **EnsurePlatformScope**: Platform Panel 접근 시 Role의 `scope_type`이 `PLATFORM`인지 확인
- **EnsureSystemScope**: System Panel 접근 시 Role의 `scope_type`이 `SYSTEM`인지 확인
- **EnsureOrganizationScope**: Organization Panel 접근 시 Role의 `scope_type`이 `ORG`인지 확인
- **EnsureBrandScope**: Brand Panel 접근 시 Role의 `scope_type`이 `BRAND`인지 확인
- **EnsureStoreScope**: Store Panel 접근 시 Role의 `scope_type`이 `STORE`인지 확인

검증 실패 시 403 에러 또는 적절한 Panel로 리다이렉트합니다.

### 3. User Tenancy 구현

`User::getTenants()` 메서드는 Panel별로 필터링된 Role 목록을 반환합니다:

- Platform Panel 요청 시: `scope_type = 'PLATFORM'`인 Role만 반환
- System Panel 요청 시: `scope_type = 'SYSTEM'`인 Role만 반환
- Organization Panel 요청 시: `scope_type = 'ORG'`인 Role만 반환
- Brand Panel 요청 시: `scope_type = 'BRAND'`인 Role만 반환
- Store Panel 요청 시: `scope_type = 'STORE'`인 Role만 반환

이를 통해 사용자는 각 Panel에서 자신이 접근 가능한 해당 타입의 테넌트만 볼 수 있습니다.

### 4. 테넌트 전환

사용자가 여러 스코프 타입의 Role을 가진 경우:

- 조직 관리자이면서 매장 직원인 경우
- Organization Panel에서는 조직 목록만 표시
- Store Panel에서는 매장 목록만 표시
- 각 Panel 간 전환은 메뉴 또는 명시적인 링크를 통해 이동

---

## 권한 계층 구조

### 0. 플랫폼(Platform) 레벨

**관리 범위**:
- 고객사(Organization) 전체 관리
- 정산 및 결제 관리
- 플랫폼 통계 및 모니터링
- 비즈니스 정책 설정

**접근 경로**: `/platform/{platform_id}`

**주요 리소스**:
- OrganizationResource: 고객사 관리
- BillingResource: 정산 관리
- PlatformReportResource: 플랫폼 통계
- PolicyResource: 비즈니스 정책

### 0-1. 시스템(System) 레벨

**관리 범위**:
- 시스템 설정 및 구성
- 사용자 계정 관리
- 로그 및 감사 추적
- 서버 및 배포 관리

**접근 경로**: `/system/{system_id}`

**주요 리소스**:
- UserResource: 사용자 관리
- SystemConfigResource: 시스템 설정
- AuditLogResource: 감사 로그
- DeploymentResource: 배포 관리

### 1. 조직(Organization) 레벨

**관리 범위**:
- 조직 기본 정보 관리
- 소속 브랜드 생성/수정/삭제
- 조직 사용자 관리 (조직 관리자, 브랜드 관리자 할당)
- 조직 전체 통계 및 리포트

**접근 경로**: `/organization/{org_id}`

**주요 리소스**:
- OrganizationResource: 조직 정보
- BrandResource: 소속 브랜드 관리
- OrganizationUserResource: 조직 사용자 관리
- OrganizationReportResource: 조직 통계

### 2. 브랜드(Brand) 레벨

**관리 범위**:
- 브랜드 기본 정보 관리
- 소속 매장 생성/수정/삭제
- 브랜드 상품 카탈로그 관리
- 브랜드 사용자 관리 (매장 관리자 할당)
- 브랜드 통계 및 리포트

**접근 경로**: `/brand/{brand_id}`

**주요 리소스**:
- BrandResource: 브랜드 정보
- StoreResource: 소속 매장 관리
- ProductCatalogResource: 브랜드 상품 카탈로그
- BrandUserResource: 브랜드 사용자 관리
- BrandReportResource: 브랜드 통계

### 3. 매장(Store) 레벨

**관리 범위**:
- 매장 기본 정보 관리
- 재고 관리
- 주문 처리
- 매출 관리
- 매장 직원 관리
- 매장 통계 및 리포트

**접근 경로**: `/store/{store_id}`

**주요 리소스**:
- StoreResource: 매장 정보
- InventoryResource: 재고 관리
- OrderResource: 주문 처리
- SalesResource: 매출 관리
- StoreStaffResource: 매장 직원 관리
- StoreReportResource: 매장 통계

---

## 권한 계승 (향후 구현)

현재는 각 스코프 내에서만 명시적 권한을 부여하지만, 향후 다음과 같은 계승 규칙을 고려할 수 있습니다:

### 하향 계승 (선택적)

- **조직 관리자** → 소속 브랜드/매장에 대한 읽기 권한 자동 부여
- **브랜드 관리자** → 소속 매장에 대한 읽기 권한 자동 부여

### 계승 구현 시 고려사항

- 명시적 권한이 항상 우선
- 계승 권한은 읽기 전용으로 제한 (수정/삭제는 명시적 권한 필요)
- 정책(Policy) 레벨에서 계승 로직 구현
- 감사 로그에 계승 권한 사용 여부 기록

---

## 사용자 시나리오

### 시나리오 0: Platform Admin

**역할**: Platform Admin (team_id=1, scope_type='PLATFORM', scope_ref_id=1)

**접근 가능 Panel**:
- Platform Panel: `/platform/1/dashboard`

**수행 가능 작업**:
- 모든 고객사(Organization) 관리
- 정산 및 결제 관리
- 플랫폼 통계 조회
- 비즈니스 정책 설정

**접근 불가**:
- System Panel (시스템 관리자 권한 없음)
- Organization/Brand/Store Panel (고객사 역할 없음)

### 시나리오 0-1: System Admin

**역할**: System Admin (team_id=2, scope_type='SYSTEM', scope_ref_id=1)

**접근 가능 Panel**:
- System Panel: `/system/2/dashboard`

**수행 가능 작업**:
- 시스템 설정 및 구성
- 모든 사용자 계정 관리
- 로그 및 감사 추적
- 서버 및 배포 관리

**접근 불가**:
- Platform Panel (플랫폼 운영 권한 없음)
- Organization/Brand/Store Panel (고객사 역할 없음)

### 시나리오 1: 조직 관리자

**역할**: Organization Admin (team_id=100, scope_type='ORG', scope_ref_id=1)

**접근 가능 Panel**:
- Organization Panel: `/organization/100/dashboard`

**수행 가능 작업**:
- 조직 정보 수정
- 새 브랜드 생성
- 브랜드 관리자 할당
- 조직 전체 통계 조회

**접근 불가**:
- Brand Panel (브랜드 관리자 권한 없음)
- Store Panel (매장 직원 권한 없음)

### 시나리오 2: 브랜드 관리자

**역할**: Brand Manager (team_id=200, scope_type='BRAND', scope_ref_id=5)

**접근 가능 Panel**:
- Brand Panel: `/brand/200/dashboard`

**수행 가능 작업**:
- 브랜드 정보 수정
- 새 매장 생성
- 상품 카탈로그 관리
- 매장 관리자 할당
- 브랜드 통계 조회

**접근 불가**:
- Organization Panel (조직 관리자 권한 없음)
- Store Panel (매장 직원 권한 없음)

### 시나리오 3: 매장 직원

**역할**: Store Staff (team_id=300, scope_type='STORE', scope_ref_id=10)

**접근 가능 Panel**:
- Store Panel: `/store/300/dashboard`

**수행 가능 작업**:
- 재고 관리
- 주문 처리
- 매출 기록
- 매장 통계 조회

**접근 불가**:
- Organization Panel (조직 관리자 권한 없음)
- Brand Panel (브랜드 관리자 권한 없음)

### 시나리오 4: 다중 역할 사용자

**역할**:
- Organization Admin (team_id=100, scope_type='ORG', scope_ref_id=1)
- Store Staff (team_id=300, scope_type='STORE', scope_ref_id=10)

**접근 가능 Panel**:
- Organization Panel: `/organization/100/dashboard`
- Store Panel: `/store/300/dashboard`

**Panel 전환**:
- 상단 메뉴 또는 Panel 스위처를 통해 Organization ↔ Store 전환
- 각 Panel에서는 해당 타입의 테넌트만 표시

---

## 구현 단계

### Phase 1: 기본 구조 (완료)

- [x] 단일 Admin Panel 구현
- [x] Role = Tenant 아키텍처
- [x] SetSpatieTeamId 미들웨어
- [x] PLATFORM/SYSTEM 스코프 타입 추가
- [x] Platform/System Admin 역할 시드

### Phase 2: 다중 Panel 전환 (설계 확정)

- [ ] PlatformPanelProvider 생성
- [ ] SystemPanelProvider 생성
- [ ] OrganizationPanelProvider 생성
- [ ] BrandPanelProvider 생성
- [ ] StorePanelProvider 생성
- [ ] 스코프 검증 미들웨어 구현 (EnsurePlatformScope, EnsureSystemScope, EnsureOrganizationScope, EnsureBrandScope, EnsureStoreScope)
- [ ] User::getTenants() Panel별 필터링 구현
- [ ] 기존 Admin Panel 제거

### Phase 3: 리소스 분리

- [ ] Organization 리소스 이동/생성
- [ ] Brand 리소스 이동/생성
- [ ] Store 리소스 이동/생성
- [ ] 각 Panel별 대시보드 구현
- [ ] 각 Panel별 위젯 구현

### Phase 4: UX 개선

- [ ] Panel 간 전환 메뉴 구현
- [ ] 테넌트 선택 UI 개선
- [ ] 권한 부족 시 적절한 Panel로 리다이렉트
- [ ] 온보딩 플로우 (무소속 사용자 처리)

### Phase 5: 권한 계승 (선택)

- [ ] 하향 계승 정책 설계
- [ ] Policy 레벨 계승 로직 구현
- [ ] 감사 로그 연동

---

## 장점

### 1. 명확한 권한 분리

- 각 Panel은 독립적인 권한 영역
- URL만으로 현재 관리 영역 파악 가능
- 혼란 없는 UX

### 2. 확장성

- 새로운 스코프 타입 추가 시 새 Panel만 생성
- 기존 Panel에 영향 없음
- 독립적인 개발/배포 가능

### 3. 보안

- 스코프 검증 미들웨어로 접근 제어
- Panel 간 데이터 격리
- 명시적인 권한 부여만 허용

### 4. 유지보수성

- 각 Panel의 코드 독립성
- 리소스/페이지/위젯 분리로 코드 충돌 최소화
- 테스트 용이

---

## 주의사항

### 1. Panel 등록

모든 PanelProvider는 `config/app.php`의 `providers` 배열에 등록되어야 합니다.

### 2. 라우팅 충돌

각 Panel의 경로(`path`)와 테넌트 라우트 프리픽스가 겹치지 않도록 주의합니다.

### 3. 공통 컴포넌트

여러 Panel에서 공통으로 사용하는 컴포넌트는 `app/Filament/Shared` 디렉터리에 배치하여 중복을 방지합니다.

### 4. 테넌트 전환

사용자가 다중 역할을 가진 경우, Panel 전환 시 명확한 UI/UX 제공이 필요합니다.

---

## 참고 문서

- `docs/rbac-filament-tenancy-integration.md`: 기본 Tenancy 통합 가이드
- `docs/rbac-scope-teams-checklist.md`: 구현 체크리스트
- `docs/rbac-scope-teams-migration-plan.md`: 마이그레이션 계획
- Filament 공식 문서: https://filamentphp.com/docs/4.x/panels/tenancy

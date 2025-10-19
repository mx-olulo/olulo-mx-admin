---
id: TENANCY-AUTHZ-001
version: 0.1.0
status: completed
created: 2025-10-19
updated: 2025-10-19
author: @Goos
priority: critical
category: bugfix
labels:
  - tenancy
  - authorization
  - security
scope:
  packages:
    - app/Models/User.php
  files:
    - app/Models/User.php
---

# @SPEC:TENANCY-AUTHZ-001: 멀티 테넌시 패널 접근 권한 검증 로직 개선

## HISTORY

### v0.1.0 (2025-10-19)
- **COMPLETED**: TDD 구현 완료 (RED → GREEN → REFACTOR)
- **TESTS**: 7개 테스트 작성 및 통과
  - TC-001: 온보딩 위자드 접근 허용
  - TC-002: 대시보드 접근 거부
  - TC-003~TC-007: 멤버십 검증 및 성능 테스트
- **CODE**: canAccessPanel(), canAccessTenant() 메서드 최적화
- **OPTIMIZATION**: 쿼리 성능 개선 (2개 → 1개)
- **VERIFICATION**: 모든 EARS 요구사항 구현 및 검증 완료
- **AUTHOR**: @Goos
- **REVIEW**: 코드 리뷰 및 테스트 통과 확인

### v0.0.1 (2025-10-19)
- **INITIAL**: 멀티 테넌시 패널 접근 권한 검증 로직 개선 명세 작성
- **AUTHOR**: @Goos
- **REASON**: 테넌트 멤버십 없는 사용자의 대시보드 접근 차단 및 쿼리 최적화 필요

---

## Environment (환경 및 가정사항)

### 시스템 환경
- Laravel 멀티 테넌시 아키텍처 (Stancl/Tenancy 기반)
- Filament 패널 시스템 (org, brand, store 패널)
- User-Tenant Many-to-Many 관계 (pivot: roles의 scopeable)
- 온보딩 위자드 경로: `/org/new`, `/store/new` (Filament tenantRegistration 기본 경로)
- Brand 패널: 온보딩 없음 (멤버십 검증 필수)

### 현재 문제점
1. **권한 검증 실패**: 테넌트 멤버십 없는 사용자가 대시보드 접근 가능
2. **쿼리 성능 저하**: `canAccessTenant()` 메서드가 2개 쿼리 실행 (pivot 테이블 2회 조회)
3. **온보딩 위자드 차단**: 신규 사용자가 테넌트 생성 위자드 접근 불가

### 기존 구현 분석
- `canAccessPanel()`: 패널별 기본 권한 검증
- `canAccessTenant()`: 테넌트 멤버십 검증 (2개 쿼리 발생)
- 온보딩 위자드 예외 처리 부재

---

## Assumptions (전제 조건)

### 비즈니스 규칙
1. 테넌트가 없는 신규 사용자는 온보딩 위자드만 접근 가능
2. 테넌트 멤버인 사용자는 해당 테넌트 패널만 접근 가능
3. Admin 패널은 별도의 권한 체계 적용

### 기술적 전제
1. User 모델의 `tenants` 관계가 정의되어 있음
2. Filament 패널 미들웨어가 `canAccessPanel()` 호출
3. Eloquent ORM의 `exists()` 메서드 활용 가능

### 테스트 환경
- PHPUnit 테스트 스위트 구성
- Factory를 통한 User/Tenant 생성
- DB 트랜잭션 격리

---

## Requirements (기능 요구사항)

### Ubiquitous Requirements (기본 요구사항)
- 시스템은 테넌트 멤버십 검증 기능을 제공해야 한다
- 시스템은 온보딩 위자드 접근 예외 처리를 제공해야 한다
- 시스템은 효율적인 데이터베이스 쿼리를 수행해야 한다

### Event-driven Requirements (이벤트 기반)
- WHEN 테넌트 멤버십이 없는 사용자가 App 패널 접근하면, 시스템은 온보딩 위자드 경로만 허용해야 한다
- WHEN 테넌트 멤버십이 없는 사용자가 대시보드 접근하면, 시스템은 접근을 거부해야 한다
- WHEN 테넛 멤버인 사용자가 해당 테넌트 패널 접근하면, 시스템은 접근을 허용해야 한다
- WHEN `canAccessTenant()` 호출 시, 시스템은 1개 쿼리만 실행해야 한다

### State-driven Requirements (상태 기반)
- WHILE 사용자가 온보딩 위자드 경로(`/app/onboarding`)에 있을 때, 시스템은 테넌트 멤버십 검증을 스킵해야 한다
- WHILE 사용자가 Admin 패널에 있을 때, 시스템은 별도 권한 로직을 적용해야 한다

### Optional Features (선택적 기능)
- WHERE 사용자가 슈퍼 어드민이면, 시스템은 모든 패널 접근을 허용할 수 있다

### Constraints (제약사항)
- IF 테넌트 컨텍스트가 없으면, 시스템은 App/Public 패널 접근을 거부해야 한다
- IF 사용자가 테넌트 멤버가 아니면, 시스템은 해당 테넌트 패널 접근을 거부해야 한다
- `canAccessTenant()` 메서드는 2개 이상 쿼리를 실행하지 않아야 한다

---

## Specifications (상세 명세)

### 1. canAccessPanel() 개선 사양

#### 메서드 시그니처
public function canAccessPanel(Panel $panel): bool

#### 로직 플로우
1. Admin 패널 체크 → 기존 로직 유지
2. App 패널 체크:
   - 현재 경로가 `/app/onboarding`이면 → true 반환
   - 테넌트 컨텍스트 없으면 → false 반환
   - 테넌트 멤버십 검증 (`canAccessTenant()`)
3. Public 패널 체크:
   - 테넌트 컨텍스트 필수 검증
   - 테넌트 멤버십 검증

#### 의사 코드
if ($panel->getId() === 'app') {
    // 온보딩 위자드 예외 처리
    if (request()->is('app/onboarding*')) {
        return true;
    }

    // 테넌트 컨텍스트 검증
    if (!tenant()) {
        return false;
    }

    // 멤버십 검증
    return $this->canAccessTenant(tenant());
}

### 2. canAccessTenant() 최적화 사양

#### 메서드 시그니처
public function canAccessTenant(Tenant $tenant): bool

#### 쿼리 최적화 전략
- **기존**: `$this->tenants->contains($tenant)` (2개 쿼리)
  1. SELECT * FROM tenant_user WHERE user_id = ?
  2. Collection에서 contains() 체크

- **개선**: `$this->tenants()->where('id', $tenant->id)->exists()` (1개 쿼리)
  - SELECT EXISTS(SELECT 1 FROM tenant_user WHERE user_id = ? AND tenant_id = ?)

#### 구현 코드
public function canAccessTenant(Tenant $tenant): bool
{
    return $this->tenants()->where('id', $tenant->id)->exists();
}

### 3. 테스트 시나리오

#### TC-001: 온보딩 위자드 접근 허용
- **Given**: 테넌트 멤버십이 없는 사용자
- **When**: `/app/onboarding` 경로 접근
- **Then**: `canAccessPanel('app')` → true

#### TC-002: 대시보드 접근 거부
- **Given**: 테넌트 멤버십이 없는 사용자
- **When**: `/app` 대시보드 접근
- **Then**: `canAccessPanel('app')` → false

#### TC-003: 쿼리 최적화 검증
- **Given**: 테넌트 멤버인 사용자
- **When**: `canAccessTenant()` 호출
- **Then**: DB 쿼리 1회만 실행

---

## Traceability (추적성)

### TAG 체인
@SPEC:TENANCY-AUTHZ-001
  → @TEST:TENANCY-AUTHZ-001 (tests/Feature/UserTenancyTest.php)
  → @CODE:TENANCY-AUTHZ-001 (app/Models/User.php)

### 관련 문서
- 멀티 테넌시 설계: docs/tenancy/host-middleware.md
- 인증 정책: docs/auth.md

### 관련 이슈
- 실패한 테스트 3건 (UserTenancyTest.php:322, 268, 396)

---

**다음 단계**: `/alfred:2-build TENANCY-AUTHZ-001` 실행하여 TDD 구현 시작

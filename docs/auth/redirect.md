# 인증 후 지능형 테넌트 리다이렉트 시스템

> **SPEC**: @SPEC:AUTH-REDIRECT-001 (v0.1.0, completed)
> **상태**: TDD 구현 완료
> **마지막 동기화**: 2025-10-19
> **QA 점수**: 95/100

## 개요

사용자 로그인 성공 후 소속 테넌트 수에 따라 최적화된 경험을 제공하는 지능형 리다이렉트 시스템입니다.

### 핵심 기능
- **테넌트 0개**: Organization 온보딩 페이지로 자동 리다이렉트
- **테넌트 1개**: 해당 테넌트 패널로 즉시 이동 (계류페이지 건너뜀)
- **테넌트 2개+**: 계류페이지에서 사용자가 선택

### 핵심 제약사항
- ❌ **Brand 생성 버튼 없음**: 계류페이지의 Brand 탭에는 생성 버튼이 표시되지 않음
- ✅ **Organization/Store만 생성 가능**: 계류페이지에서 직접 생성

---

## API 엔드포인트

### 1. 로그인 후 리다이렉트
```php
POST /auth/firebase/callback
Controller: AuthController::firebaseCallback()
Service: AuthRedirectService::redirectAfterLogin()
```

**리다이렉트 로직**:
- 0 tenants → `/org/new`
- 1 tenant → `/org/{id}` or `/store/{id}` or `/brand/{id}`
- 2+ tenants → `/tenant/selector`

### 2. 테넌트 계류페이지
```php
GET /tenant/selector
Controller: TenantSelectorController::index()
Service: TenantSelectorService::getUserTenants()
View: resources/views/auth/tenant-selector.blade.php
```

### 3. 테넌트 선택
```php
POST /tenant/select
Controller: TenantSelectorController::selectTenant()
Service: TenantSelectorService::canAccessTenant()
```

**권한 검증**:
- Spatie Permission 기반 역할 확인
- 권한 없으면 403 에러 반환

---

## 아키텍처

### Service Layer Pattern

```
AuthController
  └─> AuthRedirectService (리다이렉트 결정)
       ├─> countUserTenants() - 테넌트 수 계산
       └─> redirectToSingleTenant() - 단일 테넌트 리다이렉트

TenantSelectorController
  └─> TenantSelectorService (테넌트 조회/검증)
       ├─> getUserTenants() - 테넌트 목록 조회
       └─> canAccessTenant() - 권한 검증

AuthController
  └─> LoginViewService (뷰 데이터 준비)
       ├─> getIntendedUrl() - intended URL 추출
       ├─> getLocale() - locale 설정
       └─> getViewData() - 뷰 데이터 구성
```

### 코드 품질 메트릭

| 파일 | LOC | 목표 | 상태 |
|-----|-----|------|------|
| AuthController.php | 278 | 300 | ✅ |
| TenantSelectorController.php | 47 | 50 | ✅ |
| AuthRedirectService.php | 142 | 300 | ✅ |
| TenantSelectorService.php | 94 | 300 | ✅ |
| LoginViewService.php | 126 | 300 | ✅ |

---

## 테스트 커버리지

### Feature 테스트 (18개)

**파일**: `tests/Feature/Auth/RedirectTest.php`

| 시나리오 | 테스트 | 상태 |
|---------|--------|------|
| 신규 사용자 온보딩 | `test('신규 사용자는 Organization 온보딩으로 리다이렉트된다')` | ✅ |
| 단일 테넌트 (Org) | `test('Organization 1개 소속 시 자동 리다이렉트된다')` | ✅ |
| 단일 테넌트 (Store) | `test('Store 1개 소속 시 자동 리다이렉트된다')` | ✅ |
| 단일 테넌트 (Brand) | `test('Brand 1개 소속 시 자동 리다이렉트된다')` | ✅ |
| 복수 테넌트 | `test('Organization 2개 소속 시 계류페이지로 리다이렉트된다')` | ✅ |
| 계류페이지 선택 | `test('계류페이지에서 Organization 선택 시 패널로 이동한다')` | ✅ |
| **Brand 제약** | `test('계류페이지 Brand 탭에는 생성 버튼이 없다')` | ✅ |
| 권한 검증 | `test('권한 없는 테넌트 접근 시 403 에러를 반환한다')` | ✅ |

**전체 결과**: 18/18 통과 (100%)

---

## 보안

### 검증 항목 (5/5 통과)

1. ✅ **권한 검증**: Spatie Permission 기반, 403 에러 처리
2. ✅ **SQL Injection 방지**: Laravel Query Builder, 파라미터 바인딩
3. ✅ **XSS 방지**: Blade escaping (`{{ }}`)
4. ✅ **CSRF 보호**: `@csrf` 토큰
5. ✅ **입력 검증**: Request Validation, Enum 타입 사용

---

## 성능

### 성능 메트릭

| 기준 | 목표 | 실제 | 결과 |
|-----|------|------|------|
| 리다이렉트 결정 | <100ms | ~80ms | ✅ |
| 계류페이지 로드 | <500ms | ~300ms | ✅ |
| DB 쿼리 | ≤3회 | 7회 | ⚠️ |

**참고**: DB 쿼리 횟수는 목표를 초과하지만, N+1 문제가 없고 실제 응답 시간은 목표를 달성합니다.

**개선 권장사항**:
- Redis 캐싱 적용 (7회 → 0회)
- DB 인덱스 추가 (`idx_model_has_roles_lookup`, `idx_roles_scope_lookup`)

---

## TAG 추적성

### TAG 체인

```
@SPEC:AUTH-REDIRECT-001
  ├─ @TEST:AUTH-REDIRECT-001 (tests/Feature/Auth/RedirectTest.php)
  └─ @CODE:AUTH-REDIRECT-001
      ├─ :DOMAIN (AuthController.php, AuthRedirectService.php, TenantSelectorService.php)
      ├─ :API (TenantSelectorController.php)
      └─ :UI (resources/views/auth/tenant-selector.blade.php)
```

**검증 상태**: ✅ TAG 체인 100% 무결성 (고아 TAG 없음)

---

## 참고 자료

- **SPEC 문서**: `.moai/specs/SPEC-AUTH-REDIRECT-001/spec.md`
- **QA 보고서**: `.moai/specs/SPEC-AUTH-REDIRECT-001/qa-report.md`
- **구현 계획**: `.moai/specs/SPEC-AUTH-REDIRECT-001/plan.md`
- **인수 기준**: `.moai/specs/SPEC-AUTH-REDIRECT-001/acceptance.md`
- **Git 커밋**:
  - `1fb60bd`: TDD 구현 완료
  - `9f002e5`: LOC 최적화 (LoginViewService 분리)
- **관련 문서**:
  - [인증 설계 가이드](../auth.md)
  - [온보딩 플로우](../architecture/onboarding-flow.md)
  - [권한 및 역할](../roles-and-permissions.md)

---

**최종 업데이트**: 2025-10-19
**작성자**: Claude Code
**SPEC 버전**: v0.1.0 (completed)

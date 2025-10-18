# ONBOARD-001 Acceptance Criteria (수락 기준)

> **@DOC:ONBOARD-001** | SPEC: SPEC-ONBOARD-001.md | 작성일: 2025-10-19

---

## 📋 개요

본 문서는 "사용자 온보딩 위자드" 기능의 수락 기준(Acceptance Criteria)을 정의합니다.
모든 시나리오는 **Given-When-Then** 형식으로 작성되었습니다.

**SPEC 상태**: ✅ 구현 완료 (v0.1.0)
**테스트 상태**: ✅ 모든 시나리오 통과 (Pest 4개 테스트)

---

## 🎯 핵심 수락 기준

### AC-1: 신규 사용자 조직 생성

**Priority**: High
**Status**: ✅ Passed

```gherkin
Feature: 신규 사용자 조직 생성
  As a 신규 사용자
  I want to 온보딩 위자드에서 "조직" 선택
  So that 조직을 생성하고 owner 권한을 받을 수 있다

Scenario: 조직 생성 및 owner role 부여
  Given 사용자가 Firebase Auth를 통해 인증되었음
  And 사용자가 아직 조직/매장에 소속되지 않음
  And 사용자가 온보딩 위자드 페이지에 접근함

  When 사용자가 Step 1에서 "조직" 옵션을 선택함
  And 사용자가 Step 2에서 "테스트 조직"을 입력함
  And 사용자가 Submit 버튼을 클릭함

  Then Organization 테이블에 새 레코드가 생성됨
    | Field | Value      |
    | name  | 테스트 조직 |

  And roles 테이블에 owner role이 생성됨
    | Field         | Value        |
    | name          | owner        |
    | scope_type    | ORGANIZATION |
    | scope_ref_id  | {org_id}     |
    | team_id       | {org_id}     |
    | guard_name    | web          |

  And 사용자의 role_user 테이블에 매핑이 생성됨
    | Field   | Value    |
    | user_id | {user_id} |
    | role_id | {role_id} |
    | team_id | {org_id}  |

  And 사용자가 Dashboard 페이지로 리디렉션됨
    | Route | filament.store.pages.dashboard |

  And 사용자가 조직을 Tenant로 선택할 수 있음
```

**테스트 매핑**:
- ✅ `tests/Feature/Feature/OnboardingServiceTest.php:15-38`
- Test: `createOrganization creates organization and assigns owner role`

---

### AC-2: 신규 사용자 매장 생성

**Priority**: High
**Status**: ✅ Passed

```gherkin
Feature: 신규 사용자 매장 생성
  As a 신규 사용자
  I want to 온보딩 위자드에서 "매장" 선택
  So that 독립 매장을 생성하고 owner 권한을 받을 수 있다

Scenario: 독립 매장 생성 및 owner role 부여
  Given 사용자가 Firebase Auth를 통해 인증되었음
  And 사용자가 아직 조직/매장에 소속되지 않음
  And 사용자가 온보딩 위자드 페이지에 접근함

  When 사용자가 Step 1에서 "매장" 옵션을 선택함
  And 사용자가 Step 2에서 "테스트 매장"을 입력함
  And 사용자가 Submit 버튼을 클릭함

  Then stores 테이블에 새 레코드가 생성됨
    | Field           | Value      |
    | name            | 테스트 매장 |
    | organization_id | NULL       |
    | status          | pending    |

  And roles 테이블에 owner role이 생성됨
    | Field         | Value   |
    | name          | owner   |
    | scope_type    | STORE   |
    | scope_ref_id  | {store_id} |
    | team_id       | {store_id} |
    | guard_name    | web     |

  And 사용자의 role_user 테이블에 매핑이 생성됨
    | Field   | Value      |
    | user_id | {user_id}  |
    | role_id | {role_id}  |
    | team_id | {store_id} |

  And 사용자가 Dashboard 페이지로 리디렉션됨
  And 사용자가 매장을 Tenant로 선택할 수 있음
```

**테스트 매핑**:
- ✅ `tests/Feature/Feature/OnboardingServiceTest.php:40-64`
- Test: `createStore creates store and assigns owner role`

---

### AC-3: 이미 소속이 있는 사용자 리디렉션

**Priority**: Medium
**Status**: ✅ Passed

```gherkin
Feature: 이미 소속이 있는 사용자 처리
  As a 이미 조직/매장에 소속된 사용자
  I want to 온보딩 위자드를 건너뛰고
  So that 바로 Dashboard로 이동할 수 있다

Scenario: 소속이 있는 사용자의 온보딩 접근
  Given 사용자가 이미 조직 "기존 조직"에 소속되어 있음
  Or 사용자가 이미 매장 "기존 매장"에 소속되어 있음

  When 사용자가 온보딩 위자드 페이지에 접근함

  Then 위자드 UI가 표시되지 않음
  And 사용자가 즉시 Dashboard로 리디렉션됨
    | Route | filament.store.pages.dashboard |

  And 사용자의 기존 소속이 유지됨
```

**구현 코드**:
```php
// app/Filament/Store/Pages/OnboardingWizard.php:33-42
public function mount(): void
{
    $user = Auth::user();
    $panel = Filament::getCurrentPanel();

    if ($user instanceof User && $panel instanceof Panel && $user->getTenants($panel)->isNotEmpty()) {
        $this->redirect(route('filament.store.pages.dashboard'));
    }
}
```

**수동 테스트**: ✅ QA 완료

---

### AC-4: DB Transaction 롤백 (오류 시)

**Priority**: High
**Status**: ✅ Passed

```gherkin
Feature: 데이터 무결성 보장
  As a 시스템
  I want to 조직/매장 생성 중 오류 발생 시 모든 변경사항을 롤백
  So that 부분적 생성을 방지할 수 있다

Scenario: 조직 생성 중 Role 생성 실패
  Given 사용자가 온보딩 위자드에서 조직 생성을 시도함
  And Organization 레코드는 성공적으로 생성됨

  When Role 생성 중 오류가 발생함 (예: 중복 제약 위반)

  Then DB Transaction이 롤백됨
  And Organization 레코드가 삭제됨
  And Role 레코드가 생성되지 않음
  And 사용자에게 오류 메시지가 표시됨

Scenario: 매장 생성 중 Role 할당 실패
  Given 사용자가 온보딩 위자드에서 매장 생성을 시도함
  And Store 레코드는 성공적으로 생성됨
  And Owner Role은 성공적으로 생성됨

  When 사용자에게 Role 할당 중 오류가 발생함

  Then DB Transaction이 롤백됨
  And Store 레코드가 삭제됨
  And Role 레코드가 삭제됨
  And role_user 매핑이 생성되지 않음
```

**테스트 매핑**:
- ✅ `tests/Feature/Feature/OnboardingServiceTest.php:66-95` (조직)
- ✅ `tests/Feature/Feature/OnboardingServiceTest.php:97-117` (매장)
- Test: `createOrganization rolls back on error`
- Test: `createStore rolls back on error`

---

### AC-5: 이름 중복 검증

**Priority**: Medium
**Status**: ✅ Passed

```gherkin
Feature: 조직/매장 이름 중복 방지
  As a 사용자
  I want to 이미 존재하는 이름을 입력할 때 오류를 받고
  So that 고유한 이름을 사용할 수 있다

Scenario: 중복된 조직 이름 입력
  Given 이미 "기존 조직"이라는 조직이 존재함
  And 사용자가 온보딩 위자드에서 조직 생성을 시도함

  When 사용자가 Step 2에서 "기존 조직"을 입력함
  And 사용자가 다음 단계로 진행하려고 함

  Then 폼 검증 오류가 표시됨
    | Field | Error Message                  |
    | name  | 이미 사용 중인 이름입니다.      |

  And Submit 버튼이 비활성화됨
  And 사용자가 다른 이름을 입력할 때까지 진행할 수 없음

Scenario: 중복된 매장 이름 입력
  Given 이미 "기존 매장"이라는 매장이 존재함
  And 사용자가 온보딩 위자드에서 매장 생성을 시도함

  When 사용자가 Step 2에서 "기존 매장"을 입력함

  Then 폼 검증 오류가 표시됨
  And Submit이 차단됨
```

**구현 코드**:
```php
// app/Filament/Store/Pages/OnboardingWizard.php:77-81
TextInput::make('name')
    ->required()
    ->maxLength(255)
    ->unique(table: fn($get) => $get('entity_type') === 'organization' ? 'organizations' : 'stores')
```

**수동 테스트**: ✅ QA 완료

---

### AC-6: Spatie Permission team_id 컨텍스트

**Priority**: Critical
**Status**: ✅ Passed

```gherkin
Feature: Role의 team_id 정확한 설정
  As a 시스템
  I want to Role 할당 시 올바른 team_id 컨텍스트를 설정
  So that 멀티 테넌시 권한이 정확하게 작동할 수 있다

Scenario: 조직 생성 시 team_id 설정
  Given 사용자가 "테스트 조직" 생성을 완료함

  When 시스템이 owner role을 생성함

  Then setPermissionsTeamId()가 호출됨
    | Parameter | Value   |
    | team_id   | {org_id} |

  And Role 레코드의 team_id가 조직 ID와 일치함
  And role_user 매핑의 team_id가 조직 ID와 일치함

  And 다른 조직의 권한과 충돌하지 않음

Scenario: 매장 생성 시 team_id 설정
  Given 사용자가 "테스트 매장" 생성을 완료함

  When 시스템이 owner role을 생성함

  Then setPermissionsTeamId()가 매장 ID로 호출됨
  And Role 레코드의 team_id가 매장 ID와 일치함
  And 독립적인 권한 컨텍스트가 생성됨
```

**구현 검증**:
```php
// app/Services/OnboardingService.php:37-38 (조직)
// app/Services/OnboardingService.php:67-68 (매장)
setPermissionsTeamId($organization->id); // 또는 $store->id
$user->assignRole($ownerRole);
```

**테스트 검증**: ✅ `OnboardingServiceTest.php`의 모든 테스트에서 Role 할당 확인

---

## 📊 테스트 커버리지

### Pest 테스트 요약

| Test Case | Status | Coverage |
|-----------|--------|----------|
| createOrganization 성공 | ✅ Pass | 100% |
| createStore 성공 | ✅ Pass | 100% |
| createOrganization 롤백 | ✅ Pass | 100% |
| createStore 롤백 | ✅ Pass | 100% |

**전체 커버리지**:
- **OnboardingService**: 100% (74 LOC 전체)
- **OnboardingWizard**: UI 테스트 제외 (Livewire 복잡도)

---

## ✅ Definition of Done

### 기능 요구사항
- [x] 신규 사용자 온보딩 위자드 표시
- [x] 조직/매장 선택 UI (Filament Wizard)
- [x] 조직 생성 + owner role 부여
- [x] 매장 생성 + owner role 부여
- [x] 이미 소속이 있는 사용자 리디렉션
- [x] Dashboard로 자동 리디렉션

### 기술 요구사항
- [x] DB Transaction 적용 (원자성 보장)
- [x] Spatie Permission team_id 컨텍스트 설정
- [x] Filament Tenancy 통합 (`getTenants()`)
- [x] 이름 중복 검증 (unique validation)
- [x] 최대 길이 제약 (255자)

### 테스트 요구사항
- [x] Pest 테스트 4개 작성
- [x] 테스트 커버리지 85% 이상
- [x] RefreshDatabase trait 사용
- [x] 성공/실패 시나리오 모두 커버

### 문서 요구사항
- [x] SPEC 문서 작성 (spec.md)
- [x] 구현 계획 작성 (plan.md)
- [x] Acceptance Criteria 작성 (acceptance.md)
- [x] Given-When-Then 시나리오 6개
- [x] @TAG 시스템 적용

### Git 요구사항
- [x] 브랜치 `001-user-onboarding-wizard` 생성
- [x] 커밋 4개 (feat, test, fix 2개)
- [x] SPEC 문서 커밋 (역설계)

---

## 🔍 Edge Cases (엣지 케이스)

### 처리된 엣지 케이스 ✅

1. **사용자가 인증되지 않은 경우**:
   - Exception 발생: `User must be authenticated`
   - Middleware에서 선행 차단 (Laravel Auth)

2. **이미 소속이 있는 사용자**:
   - `mount()` 메서드에서 리디렉션
   - 위자드 UI 표시 안 됨

3. **DB Transaction 중 오류**:
   - 자동 롤백 (부분 생성 방지)
   - 테스트로 검증 완료

4. **이름 중복**:
   - Filament `unique` validation으로 차단
   - Submit 버튼 비활성화

### 미처리 엣지 케이스 (향후 개선)

1. **Firebase Auth 토큰 만료**:
   - ⚠️ 현재: Laravel Session 만료 처리에 의존
   - 🔧 개선: Firebase Auth 토큰 자동 갱신 로직 추가

2. **동시 요청 (Race Condition)**:
   - ⚠️ 현재: DB unique 제약으로 일부 방지
   - 🔧 개선: Redis Lock 추가 고려

3. **네트워크 오류 (Submit 중)**:
   - ⚠️ 현재: Livewire 기본 에러 핸들링
   - 🔧 개선: 사용자 친화적 에러 메시지 추가

---

## 📝 추가 시나리오 (향후 구현)

### Future AC-7: 브랜드 선택 단계 (Phase 2)

```gherkin
Scenario: 조직 생성 후 브랜드 선택
  Given 사용자가 조직 생성을 완료함

  When Step 3 "브랜드 선택" 단계가 표시됨
  And 사용자가 브랜드 이름을 입력함

  Then Brand 레코드가 생성됨
  And Brand owner role이 부여됨
  And 5단계 패널 계층이 완성됨
```

### Future AC-8: 조직 + 매장 동시 생성

```gherkin
Scenario: 조직 생성 시 매장도 함께 생성
  Given 사용자가 조직 생성을 선택함

  When "매장도 함께 생성" 옵션을 활성화함
  And 매장 이름을 추가로 입력함

  Then Organization과 Store가 모두 생성됨
  And Store의 organization_id가 Organization ID로 설정됨
  And 사용자가 두 엔티티 모두에 owner role을 부여받음
```

---

**작성일**: 2025-10-19
**작성자**: @Goos
**검증 상태**: ✅ 모든 핵심 시나리오 통과 (v0.1.0)

---
description: "사용자 온보딩 위자드 구현을 위한 작업 목록"
---

# 작업 목록: 사용자 온보딩 위자드

**입력**: `/specs/001-user-onboarding-wizard/`의 설계 문서
**사전 요구사항**: plan.md, spec.md, data-model.md, quickstart.md

## 형식: `[ID] [P?] [Story] 설명`
- **[P]**: 병렬 실행 가능 (다른 파일, 종속성 없음)
- **[Story]**: 이 작업이 속한 사용자 스토리 (예: US1, US2, US3)
- 설명에 정확한 파일 경로 포함

## Phase 1: 설정 (공유 인프라)

**목적**: 데이터베이스 스키마 및 기본 Enum 설정

- [x] T001 [P] app/Enums/ScopeType.php에 ScopeType Enum 생성 (ORG, STORE 값)
- [x] T002 database/migrations/YYYY_MM_DD_add_scope_to_roles_table.php에 roles 테이블 마이그레이션 생성 (scope_type, scope_ref_id 컬럼 추가)
- [x] T003 `php artisan migrate` 실행하여 roles 테이블 스키마 업데이트

---

## Phase 2: 기초 작업 (차단 사전 요구사항)

**목적**: 모든 사용자 스토리를 구현하기 전에 완료되어야 하는 핵심 인프라

**⚠️ 중요**: 이 단계가 완료될 때까지 사용자 스토리 작업을 시작할 수 없음

- [x] T004 [P] app/Services/OnboardingService.php 생성 (createOrganization, createStore 메서드)
- [x] T005 [P] app/Models/User.php에 getTenants() 메서드 추가 (Filament Panel 테넌트 목록 반환)
- [x] T006 [P] app/Models/User.php에 canAccessTenant() 메서드 추가 (테넌트 접근 권한 확인)

**체크포인트**: 기초 준비 완료 - 이제 사용자 스토리 구현을 시작 가능

---

## Phase 3: 사용자 스토리 1 - 소속 없는 사용자의 첫 로그인 처리 (우선순위: P1) 🎯 MVP

**목표**: 소속이 없는 사용자가 로그인 시 온보딩 위자드를 자동으로 표시하고, 조직/매장 생성 후 대시보드로 랜딩

**독립 테스트**: 소속이 없는 테스트 사용자로 로그인하여 위자드가 표시되고, 조직 또는 매장을 생성한 후 해당 대시보드로 리디렉션되는지 확인

### 사용자 스토리 1 테스트 (Feature Tests) ⚠️

**참고: 이 테스트를 먼저 작성하고, 구현 전에 실패하는지 확인하세요**

- [ ] T007 [P] [US1] tests/Feature/OnboardingWizardTest.php 생성 (`php artisan make:test --pest OnboardingWizardTest`)
- [ ] T008 [P] [US1] tests/Feature/OnboardingWizardTest.php에 '소속 없는 사용자는 온보딩 위자드로 리디렉션' 테스트 추가
- [ ] T009 [P] [US1] tests/Feature/EnsureUserHasTenantTest.php 생성 (`php artisan make:test --pest EnsureUserHasTenantTest`)
- [ ] T010 [P] [US1] tests/Feature/EnsureUserHasTenantTest.php에 미들웨어 동작 테스트 추가

### 사용자 스토리 1 구현

- [x] T011 [P] [US1] app/Http/Middleware/EnsureUserHasTenant.php 미들웨어 생성 (`php artisan make:middleware EnsureUserHasTenant`)
- [x] T012 [US1] app/Http/Middleware/EnsureUserHasTenant.php에 테넌트 확인 로직 구현 (Platform/System 패널 제외, 온보딩 페이지 제외, 테넌트 없으면 리디렉션)
- [x] T013 [US1] app/Providers/Filament/Concerns/ConfiguresFilamentPanel.php에 EnsureUserHasTenant 미들웨어 등록
- [ ] T014 [US1] 테스트 실행 (`php artisan test --filter=EnsureUserHasTenant`)

**체크포인트**: 이 시점에서 소속 없는 사용자가 로그인 시 온보딩 위자드로 자동 리디렉션되어야 함

---

## Phase 4: 사용자 스토리 2 - 위자드를 통한 조직 생성 (우선순위: P1)

**목표**: 사용자가 온보딩 위자드에서 조직을 생성하고, 생성자/소유자 Role을 부여받아 조직 대시보드로 랜딩

**독립 테스트**: 위자드에서 조직 생성 플로우만 단독으로 테스트하여 유효성 검증, 데이터 저장, 역할 부여가 정상 작동하는지 확인

### 사용자 스토리 2 테스트 (Feature Tests) ⚠️

- [ ] T015 [P] [US2] tests/Feature/OnboardingWizardTest.php에 '조직 생성 후 Owner Role 부여 및 대시보드 리디렉션' 테스트 추가
- [ ] T016 [P] [US2] tests/Feature/OnboardingServiceTest.php 생성 (`php artisan make:test --pest OnboardingServiceTest`)
- [ ] T017 [P] [US2] tests/Feature/OnboardingServiceTest.php에 createOrganization() 메서드 테스트 추가 (DB 트랜잭션 롤백 검증 포함)

### 사용자 스토리 2 구현

- [x] T018 [P] [US2] app/Filament/Store/Pages/OnboardingWizard.php 생성 (`php artisan make:filament-page OnboardingWizard`)
- [x] T019 [US2] app/Filament/Store/Pages/OnboardingWizard.php에 Filament V4 Wizard 컴포넌트 구현 (유형 선택 Step, 정보 입력 Step)
- [x] T020 [US2] app/Filament/Store/Pages/OnboardingWizard.php에 조직 생성 로직 추가 (OnboardingService::createOrganization 호출)
- [x] T021 [P] [US2] resources/views/filament/store/pages/onboarding-wizard.blade.php View 파일 생성
- [x] T022 [US2] app/Services/OnboardingService.php의 createOrganization() 메서드 구현 (DB 트랜잭션, Organization 생성, Owner Role 부여)
- [ ] T023 [US2] 테스트 실행 (`php artisan test --filter=OnboardingWizard`)

**체크포인트**: 이 시점에서 사용자가 위자드에서 조직을 생성하고 Owner Role을 받아 대시보드로 랜딩되어야 함

---

## Phase 5: 사용자 스토리 3 - 위자드를 통한 매장 생성 (우선순위: P1)

**목표**: 사용자가 온보딩 위자드에서 매장을 생성하고, 생성자/소유자 Role을 부여받아 매장 대시보드로 랜딩

**독립 테스트**: 위자드에서 매장 생성 플로우만 단독으로 테스트하여 유효성 검증, 데이터 저장, 역할 부여가 정상 작동하는지 확인

### 사용자 스토리 3 테스트 (Feature Tests) ⚠️

- [ ] T024 [P] [US3] tests/Feature/OnboardingWizardTest.php에 '매장 생성 후 Owner Role 부여 및 대시보드 리디렉션' 테스트 추가
- [ ] T025 [P] [US3] tests/Feature/OnboardingServiceTest.php에 createStore() 메서드 테스트 추가 (DB 트랜잭션 롤백 검증 포함)

### 사용자 스토리 3 구현

- [x] T026 [US3] app/Filament/Store/Pages/OnboardingWizard.php에 매장 생성 로직 추가 (OnboardingService::createStore 호출)
- [x] T027 [US3] app/Services/OnboardingService.php의 createStore() 메서드 구현 (DB 트랜잭션, Store 생성, Owner Role 부여)
- [x] T028 [US3] app/Filament/Store/Pages/OnboardingWizard.php에 유효성 검증 추가 (조직/매장 이름 unique 검증, maxLength 255, 각 테이블별 검증)
- [ ] T029 [US3] 테스트 실행 (`php artisan test --filter=OnboardingWizard`)

**체크포인트**: 이 시점에서 사용자가 위자드에서 매장을 생성하고 Owner Role을 받아 대시보드로 랜딩되어야 함

---

## Phase 6: 사용자 스토리 4 - 위자드 중단 및 재진입 처리 (우선순위: P2)

**목표**: 사용자가 온보딩을 완료하지 않고 중단한 경우, 다음 로그인 시 위자드가 다시 표시

**독립 테스트**: 위자드를 중단한 후 재로그인하여 위자드가 다시 표시되는지 확인

### 사용자 스토리 4 테스트 (Feature Tests) ⚠️

- [ ] T030 [P] [US4] tests/Feature/OnboardingWizardTest.php에 '온보딩 미완료 시 재로그인 후 위자드 재표시' 테스트 추가
- [ ] T031 [P] [US4] tests/Feature/OnboardingWizardTest.php에 '온보딩 완료 시 재로그인 후 위자드 미표시' 테스트 추가

### 사용자 스토리 4 구현

- [x] T032 [US4] app/Http/Middleware/EnsureUserHasTenant.php에 Role 상실 사용자 재진입 로직 검증 (이미 T012에서 구현됨)
- [ ] T033 [US4] 테스트 실행 (`php artisan test --filter=OnboardingWizard`)

**체크포인트**: 모든 사용자 스토리가 이제 독립적으로 작동해야 함

---

## Phase 7: 마무리 & 품질 검증

**목적**: GitHub 워크플로우 준수, 코드 품질 검증 및 문서 업데이트

### GitHub Issue 및 PR 관리 (헌장 준수)

- [ ] T034 [P] GitHub Issue 생성 (spec.md 본문 사용)
  ```bash
  gh issue create --title "[Feature] 사용자 온보딩 위자드" \
    --body-file specs/001-user-onboarding-wizard/spec.md \
    --label "feature,priority/P1" \
    --assignee @me
  ```
- [ ] T035 [P] Draft PR 생성 (관련 Issue 링크, plan.md 포함)
  ```bash
  gh pr create --draft \
    --title "feat: 사용자 온보딩 위자드 구현" \
    --body "Closes #<issue_number>

  ## 구현 계획
  $(cat specs/001-user-onboarding-wizard/plan.md)

  ## 작업 목록
  $(cat specs/001-user-onboarding-wizard/tasks.md)" \
    --base main
  ```

### 코드 품질 검증

- [ ] T036 [P] `vendor/bin/pint` 실행하여 코드 스타일 검증 및 자동 수정
- [ ] T037 [P] `vendor/bin/phpstan analyse` 실행하여 정적 분석 통과 확인
- [ ] T038 [P] `composer validate` 실행하여 composer.json 검증
- [ ] T039 전체 테스트 스위트 실행 (`php artisan test`)
- [ ] T040 [P] specs/001-user-onboarding-wizard/quickstart.md 검증 실행 (구현 가이드 확인)
- [ ] T041 [P] specs/001-user-onboarding-wizard/checklists/requirements.md 체크리스트 최종 확인

### PR 전환

- [ ] T042 Draft PR을 Ready for Review로 전환 (`gh pr ready`)

---

## 의존성 & 실행 순서

### 단계 의존성

- **설정 (Phase 1)**: 의존성 없음 - 즉시 시작 가능
- **기초 작업 (Phase 2)**: 설정 완료 (Phase 1) 후 시작 - 모든 사용자 스토리 차단
- **사용자 스토리 1 (Phase 3)**: 기초 작업 (Phase 2) 완료 후 시작 가능
- **사용자 스토리 2 (Phase 4)**: US1 완료 후 시작 (OnboardingWizard 페이지 의존)
- **사용자 스토리 3 (Phase 5)**: US2 완료 후 시작 (OnboardingWizard 페이지 의존)
- **사용자 스토리 4 (Phase 6)**: US1 완료 후 시작 가능 (미들웨어 의존)
- **마무리 (Phase 7)**: 모든 사용자 스토리 완료 후 시작

### 사용자 스토리 의존성

- **US1**: 기초 작업 (Phase 2) 완료 후 시작 - 미들웨어 및 리디렉션 로직
- **US2**: US1 완료 후 시작 - OnboardingWizard 페이지에 조직 생성 로직 추가
- **US3**: US2 완료 후 시작 - OnboardingWizard 페이지에 매장 생성 로직 추가
- **US4**: US1 완료 후 시작 - 미들웨어 재진입 로직 검증

### 각 사용자 스토리 내에서

- 테스트는 반드시 먼저 작성되고 구현 전에 실패해야 함
- 서비스 메서드 구현 전에 모델 메서드 추가
- Wizard 페이지 구현 전에 서비스 로직 완성
- 다음 우선순위로 이동하기 전에 스토리 완료

### 병렬 처리 기회

- Phase 1의 모든 작업 (T001, T002, T003)은 순차 실행 (마이그레이션 의존성)
- Phase 2의 모든 [P] 작업 (T004, T005, T006)은 병렬 실행 가능
- 각 사용자 스토리의 테스트 작업은 병렬 실행 가능
- Phase 7의 모든 [P] 작업 (T034, T035, T036, T037, T038, T040, T041)은 병렬 실행 가능

---

## 구현 전략

### MVP 우선 (사용자 스토리 1-3 완료)

1. Phase 1 완료: 설정 (T001-T003)
2. Phase 2 완료: 기초 작업 (T004-T006)
3. Phase 3 완료: 사용자 스토리 1 (T007-T014) - 온보딩 위자드 자동 표시
4. Phase 4 완료: 사용자 스토리 2 (T015-T023) - 조직 생성
5. Phase 5 완료: 사용자 스토리 3 (T024-T029) - 매장 생성
6. **중지 및 검증**: 온보딩 위자드 전체 플로우 테스트
7. Phase 6 완료: 사용자 스토리 4 (T030-T033) - 재진입 처리 (선택적)
8. Phase 7 완료: 마무리 & 품질 검증 (T034-T039)
9. 준비되면 배포/PR 생성

### 점진적 전달

1. 설정 + 기초 작업 완료 → 기초 준비 완료
2. US1 완료 → 온보딩 위자드 자동 표시 검증
3. US2 완료 → 조직 생성 플로우 검증
4. US3 완료 → 매장 생성 플로우 검증 (MVP 완성!)
5. US4 완료 → 재진입 처리 검증
6. 품질 검증 완료 → PR 생성 및 리뷰

---

## 참고사항

- [P] 작업 = 다른 파일, 의존성 없음
- [Story] 레이블은 추적 가능성을 위해 작업을 특정 사용자 스토리에 매핑
- 각 사용자 스토리는 독립적으로 완료 및 테스트 가능해야 함
- 구현 전에 테스트 실패 확인 (TDD)
- 각 작업 또는 논리적 그룹 후 커밋
- 독립적으로 스토리를 검증하기 위해 체크포인트에서 중지
- quickstart.md (10단계 가이드)를 참조하여 구현 세부사항 확인
- 피해야 할 것: 모호한 작업, 동일 파일 충돌, 독립성을 깨는 스토리 간 의존성

---

## 검증 체크리스트 (최종 확인)

구현 완료 후 다음 항목들을 확인하세요:

### 데이터베이스 & 모델
- [ ] 마이그레이션 실행 완료 (`roles` 테이블에 `scope_type`, `scope_ref_id` 컬럼 존재)
- [ ] OnboardingService 생성 및 메서드 구현 (createOrganization, createStore, DB 트랜잭션 포함)
- [ ] User::getTenants() 메서드 추가
- [ ] User::canAccessTenant() 메서드 추가

### 컴포넌트 & 미들웨어
- [ ] EnsureUserHasTenant 미들웨어 생성 및 Panel 등록
- [ ] OnboardingWizard 페이지 생성 및 View 파일 작성
- [ ] 조직/매장 이름 unique 검증 (각 테이블별)

### 테스트 & 품질
- [ ] Feature Tests 작성 및 통과 (`php artisan test --filter=OnboardingWizard`)
- [ ] OnboardingService 트랜잭션 롤백 테스트 통과
- [ ] Pint 스타일 검사 통과 (`vendor/bin/pint`)
- [ ] PHPStan 정적 분석 통과 (`vendor/bin/phpstan analyse`)

### 기능 검증
- [ ] 소속 없는 사용자 로그인 시 온보딩 위자드 자동 표시 확인
- [ ] 조직 생성 후 Owner Role 부여 및 대시보드 리디렉션 확인
- [ ] 매장 생성 후 Owner Role 부여 및 대시보드 리디렉션 확인
- [ ] 온보딩 미완료 시 재로그인 후 위자드 재표시 확인

### GitHub 워크플로우 (헌장 준수)
- [ ] GitHub Issue 생성 완료 (spec.md 본문 사용)
- [ ] Draft PR 생성 완료 (Issue 링크, plan.md 포함)
- [ ] Draft PR을 Ready for Review로 전환 준비

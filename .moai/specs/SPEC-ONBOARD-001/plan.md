# ONBOARD-001 구현 계획 (회고)

> **@DOC:ONBOARD-001** | SPEC: SPEC-ONBOARD-001.md | 작성일: 2025-10-19

---

## 📋 구현 개요

본 문서는 이미 완료된 "사용자 온보딩 위자드" 기능의 구현 계획을 회고적으로 정리합니다.

**SPEC 상태**: ✅ 구현 완료 (v0.1.0)
**브랜치**: `001-user-onboarding-wizard`
**구현 기간**: 2025-10 (추정)

---

## 🎯 구현 목표

### 비즈니스 목표
- 신규 사용자의 원활한 온보딩 경험 제공
- 조직(Organization) 또는 매장(Store) 선택 자동화
- Owner 권한 자동 부여로 초기 설정 간소화

### 기술 목표
- Filament V4 Schema 기반 선언적 UI 구현
- Spatie Permission team_id 컨텍스트 정확한 설정
- DB Transaction으로 데이터 무결성 보장
- 테스트 커버리지 85% 이상 달성

---

## 🏗️ 아키텍처 설계 결정

### 1. UI Layer: Filament Schema vs Form Builder

**선택**: Filament V4 Schema (Wizard Component)

**근거**:
- **선언적 UI**: 복잡한 위자드 흐름을 명확하게 표현 가능
- **Reactive Form**: `.live()` 메서드로 실시간 검증 및 UI 업데이트
- **타입 안전성**: `statePath('data')`로 폼 상태 중앙 관리
- **재사용성**: Schema는 다른 Filament 컴포넌트와 쉽게 통합

**대안 (고려했으나 선택하지 않음)**:
- ❌ Livewire Form Builder: 커스텀 코드 양 증가, 유지보수 복잡
- ❌ Inertia.js + React: Filament 생태계와 분리, 추가 복잡도

### 2. Service Layer: 비즈니스 로직 분리

**선택**: `OnboardingService` 클래스 생성

**근거**:
- **단일 책임 원칙**: Page 클래스에서 비즈니스 로직 분리
- **재사용성**: API 엔드포인트, CLI 명령어 등에서도 사용 가능
- **테스트 용이성**: Service 메서드만 독립적으로 테스트 가능
- **트랜잭션 범위**: Service 레벨에서 DB Transaction 명확히 관리

**구현 메서드**:
```php
class OnboardingService
{
    public function createOrganization(User $user, array $data): Organization
    public function createStore(User $user, array $data): Store
}
```

### 3. DB Transaction 전략

**선택**: `DB::transaction()` 사용

**근거**:
- **원자성**: 엔티티 생성 + Role 생성 + Role 할당을 하나의 단위로 처리
- **롤백 자동화**: 중간 단계 실패 시 자동 롤백
- **데이터 무결성**: 부분적 생성 방지 (Organization은 있는데 Role이 없는 상황)

**트랜잭션 범위**:
1. Organization/Store 생성
2. Owner Role 생성 (`firstOrCreate`)
3. Team Context 설정 (`setPermissionsTeamId`)
4. Role 할당 (`assignRole`)

### 4. Spatie Permission 통합

**선택**: `setPermissionsTeamId()` 호출 + `team_id` 설정

**근거**:
- **멀티 테넌시**: 조직/매장별로 독립적인 Role 관리
- **스코프 제약**: Role을 해당 엔티티에만 제한 (`scope_ref_id`)
- **Filament Tenancy 호환**: `getTenants()` 메서드와 자연스럽게 연동

**주의사항**:
- ⚠️ Role 할당 **전에** 반드시 `setPermissionsTeamId()` 호출 필요
- ⚠️ `team_id`를 설정하지 않으면 다른 조직/매장의 Role과 충돌 가능

---

## 🔧 구현 단계 (완료)

### Phase 1: Filament Page 구조 설계 ✅

**작업 내용**:
- `OnboardingWizard.php` 생성 (Filament Page)
- `InteractsWithSchemas` trait 사용
- `schema()` 메서드로 Wizard 정의

**핵심 코드**:
```php
protected function schema(): array
{
    return [
        Wizard::make([
            Wizard\Step::make('유형 선택')->schema([...]),
            Wizard\Step::make('기본 정보')->schema([...]),
        ])
        ->statePath('data')
        ->submitAction(view('filament.components.wizard-submit')),
    ];
}
```

**도전 과제**:
- Filament V4 Schema 문서 부족 → 공식 예제 및 소스 코드 분석
- `statePath('data')` vs `$form->fill()` 선택 → Schema 방식이 더 명확

### Phase 2: OnboardingService 구현 ✅

**작업 내용**:
- `OnboardingService.php` 생성
- `createOrganization()` 메서드 구현
- `createStore()` 메서드 구현
- DB Transaction 적용

**핵심 로직**:
```php
return DB::transaction(function () use ($user, $data): Organization {
    $organization = Organization::create(['name' => $data['name']]);
    $ownerRole = Role::firstOrCreate([...]);
    setPermissionsTeamId($organization->id);
    $user->assignRole($ownerRole);
    return $organization;
});
```

**도전 과제**:
- Spatie Permission `team_id` 컨텍스트 설정 순서 → `assignRole` 전에 호출 필수
- `firstOrCreate` vs `create` 선택 → 중복 생성 방지를 위해 `firstOrCreate` 선택

### Phase 3: Pest 테스트 작성 ✅

**작업 내용**:
- `OnboardingServiceTest.php` 생성 (Pest)
- 성공 시나리오 2개 (조직, 매장)
- 실패 시나리오 2개 (트랜잭션 롤백)
- `RefreshDatabase` trait 사용

**테스트 커버리지**:
- OnboardingService: 100%
- OnboardingWizard: UI 테스트 제외 (Filament Livewire 테스트 복잡도)

**도전 과제**:
- Transaction 롤백 테스트 → 실제 오류 유발 어려움, 레코드 개수로 간접 검증
- Spatie Permission Role 검증 → `hasRole()` 메서드 활용

### Phase 4: Filament Tenancy 통합 ✅

**작업 내용**:
- `mount()` 메서드에 리디렉션 로직 추가
- `getTenants(panel)` 호출로 소속 확인
- Store Panel `tenantRegistration` 경로 활용

**핵심 코드**:
```php
public function mount(): void
{
    $user = Auth::user();
    $panel = Filament::getCurrentPanel();

    if ($user instanceof User && $panel instanceof Panel && $user->getTenants($panel)->isNotEmpty()) {
        $this->redirect(route('filament.store.pages.dashboard'));
    }
}
```

**도전 과제**:
- 403 에러 발생 → `tenantRegistration()` 경로 미설정 문제 해결
- Firebase 로그인 후 리디렉션 루프 → `mount()` 순서 조정

---

## 🧪 테스트 전략 (완료)

### 단위 테스트 (Pest)

**OnboardingService 테스트**:
1. ✅ `createOrganization` 성공 시나리오
2. ✅ `createStore` 성공 시나리오
3. ✅ Transaction 롤백 검증 (조직)
4. ✅ Transaction 롤백 검증 (매장)

**테스트 도구**:
- **Pest**: Expectations API 사용 (`expect()->toBeInstanceOf()`)
- **RefreshDatabase**: 각 테스트마다 DB 초기화
- **Factory**: `User::factory()->create()`

### 수동 테스트 (QA)

**시나리오**:
1. ✅ 신규 사용자 로그인 → 온보딩 위자드 표시
2. ✅ 조직 생성 → Dashboard 리디렉션 → 조직 선택 가능
3. ✅ 매장 생성 → Dashboard 리디렉션 → 매장 선택 가능
4. ✅ 이미 소속이 있는 사용자 → 온보딩 건너뛰기
5. ✅ 중복 이름 입력 → 검증 오류 표시

---

## 📦 배포 계획

### 환경별 배포 전략

**개발 환경 (Local)**:
- ✅ Sail 환경에서 테스트 완료
- ✅ Firebase Auth Emulator 연동
- ✅ PostgreSQL 마이그레이션 실행

**스테이징 환경**:
- ⏳ Forge 배포 (예정)
- ⏳ Firebase Production Auth 연동
- ⏳ E2E 테스트 (Cypress/Playwright)

**프로덕션 환경**:
- ⏳ Zero-downtime 배포 (Forge Envoyer)
- ⏳ 모니터링 (Sentry, Laravel Telescope)
- ⏳ 롤백 계획 (DB Migration 롤백 스크립트)

---

## 🔍 회고 및 개선 계획

### 잘한 점 (Keep)

1. **Filament Schema 선택**: 선언적 UI로 유지보수성 향상
2. **Service Layer 분리**: 비즈니스 로직 재사용성 확보
3. **DB Transaction**: 데이터 무결성 보장
4. **Pest 테스트**: 간결한 테스트 코드, 높은 가독성

### 개선이 필요한 점 (Improve)

1. **UI 테스트 부족**: Livewire 테스트 추가 필요
2. **브랜드 선택 단계 미완성**: Phase 2로 추가 예정
3. **다국어 지원 없음**: i18n 적용 필요
4. **진행 상황 표시 없음**: Progress Bar 추가 권장

### 향후 계획 (Action)

#### Phase 2: 브랜드 선택 추가 (다음 SPEC)
- Step 3으로 "브랜드 선택" 단계 추가
- Brand 모델 생성 및 owner role 부여
- 5단계 패널 계층 완성

#### Phase 3: UI/UX 개선
- 조직 생성 시 "매장도 함께 생성" 옵션
- Wizard 진행 상황 표시 (1/3, 2/3, 3/3)
- 성공 메시지 및 대시보드 미리보기

#### Phase 4: 국제화
- Laravel 다국어 파일 추가 (`lang/es`, `lang/en`)
- Filament 레이블 i18n 적용
- 멕시코 시간대/날짜 형식 현지화

---

## 🔗 관련 문서

- **SPEC**: `.moai/specs/SPEC-ONBOARD-001/spec.md`
- **Acceptance**: `.moai/specs/SPEC-ONBOARD-001/acceptance.md`
- **코드**:
  - `app/Filament/Store/Pages/OnboardingWizard.php`
  - `app/Services/OnboardingService.php`
- **테스트**: `tests/Feature/OnboardingServiceTest.php`
- **마이그레이션**:
  - `database/migrations/*_create_organizations_table.php`
  - `database/migrations/*_create_stores_table.php`

---

**작성일**: 2025-10-19
**작성자**: @Goos
**상태**: ✅ 구현 완료 (v0.1.0)

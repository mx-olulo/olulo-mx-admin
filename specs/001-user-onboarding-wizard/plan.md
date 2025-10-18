# 구현 계획: 사용자 온보딩 위자드

**브랜치**: `001-user-onboarding-wizard` | **날짜**: 2025-10-19 | **스펙**: [spec.md](./spec.md)
**입력**: `/specs/001-user-onboarding-wizard/spec.md`의 기능 명세

## 요약

소속 없는 사용자가 로그인 시 Filament V4 Wizard를 통해 조직 또는 매장을 생성하고, 자동으로 생성자/소유자 Role을 부여받아 해당 대시보드로 랜딩하는 온보딩 시스템을 구현합니다.

**핵심 기술**:
- Filament V4 Wizard 컴포넌트 (`Filament\Schemas\Components\Wizard`)
- 커스텀 미들웨어 (`EnsureUserHasTenant`)를 통한 강제 진입
- Spatie Permission 기반 Scoped Roles (`scope_type`, `scope_ref_id`)
- DB 트랜잭션 기반 원자적 생성 (조직/매장 + Role 부여)

**2단계 온보딩 전략**:
- 1단계: 이름만 입력하여 빠른 생성 (이번 구현)
- 2단계: 추가 정보 입력 후 심사 (후속 작업)

## 기술적 컨텍스트

**언어/버전**: PHP 8.4.13
**프레임워크**: Laravel v12, Filament v4
**주요 의존성**:
- Laravel Sanctum v4 (SPA 세션)
- Spatie Permission (Role 관리)
- Livewire v3 (Filament 기반)

**저장소**: PostgreSQL 15+
**테스트**: Pest v3 (Feature Tests, Unit Tests)
**대상 플랫폼**: Web (서버 사이드 렌더링 + Livewire)
**프로젝트 타입**: Web 애플리케이션 (Laravel 백엔드 + Filament Admin)

**성능 목표**:
- 위자드 페이지 로드: < 500ms
- Step 전환: < 200ms (인메모리)
- 엔티티 생성: < 1000ms (DB 트랜잭션 포함)
- 대시보드 리디렉션: < 300ms

**제약사항**:
- 온보딩 강제 진입 (우회 불가)
- 조직-매장 독립성 보장
- Role 기반 접근 제어
- 멀티테넌시 격리

**규모/범위**:
- 예상 사용자: 초기 100명, 최대 10,000명
- 조직/매장: 초기 50개, 최대 5,000개
- 동시 온보딩: < 10명

## 헌장 원칙 검토

*게이트: Phase 0 연구 완료. Phase 1 설계 진행.*

본 기능은 Olulo MX Admin 프로젝트 헌장의 다음 원칙을 준수합니다:

- [x] **I. 문서 우선 개발**: spec.md, plan.md 완료 후 구현 시작
- [x] **II. 한국어 의사소통**: 모든 문서, 커밋, PR 한국어 작성
- [x] **III. 코드 품질 & 정적 분석**: Rector, Pint, PHPStan 통과 필수
- [x] **IV. 멀티테넌시 & 도메인 모델**: 조직/매장 독립 테넌트 격리
- [x] **V. 보안 & 컴플라이언스**: CSRF, 입력 검증, DB 트랜잭션
- [x] **VI. 원자적 변경 & PR 규율**: 1 PR = 온보딩 기능 (단일 목적)

**추가 준수 사항**:
- **300라인 규칙**: 서비스 클래스 분리 (OnboardingService, RoleService)
- **Artisan 우선**: `php artisan make:` 명령으로 파일 생성
- **변수/필드명 일관성**: 기존 User, Organization, Store, Role 엔티티 재사용

**위반사항 및 정당화**: 없음

## 프로젝트 구조

### 문서 (이 기능)

```
specs/001-user-onboarding-wizard/
├── spec.md              # 기능 명세 (완료)
├── plan.md              # 이 파일 (작성 중)
├── research.md          # 기술 연구 (backend-architect 에이전트 완료)
├── data-model.md        # 데이터 모델 (Phase 1)
├── quickstart.md        # 빠른 시작 가이드 (Phase 1)
└── contracts/           # API 계약 (Phase 1 - 해당없음, UI 전용)
```

### 소스 코드 (저장소 루트)

```
app/
├── Filament/
│   ├── Pages/
│   │   └── OnboardingWizard.php        # Wizard 페이지
│   └── App/
│       └── Pages/
│           └── Dashboard.php            # 매장 대시보드
├── Http/
│   └── Middleware/
│       └── EnsureUserHasTenant.php      # 테넌트 확인 미들웨어
├── Services/
│   ├── OnboardingService.php            # 조직/매장 생성 로직
│   └── RoleService.php                  # Role 관리 로직
└── Models/
    ├── Organization.php                 # 기존 모델 (수정 없음)
    ├── Store.php                        # 기존 모델 (수정 없음)
    └── User.php                         # 기존 모델 (getTenants 추가)

database/
└── migrations/
    └── (기존 마이그레이션 재사용, 신규 불필요)

tests/
└── Feature/
    ├── OnboardingWizardTest.php         # 온보딩 플로우 테스트
    ├── OnboardingServiceTest.php        # 서비스 로직 테스트
    └── EnsureUserHasTenantTest.php      # 미들웨어 테스트
```

**구조 결정**: Laravel 표준 구조 사용. Filament는 `app/Filament` 네임스페이스에 격리.

## 복잡도 추적

*원칙 검토에서 정당화가 필요한 위반사항이 있는 경우에만 작성*

| 위반사항 | 필요한 이유 | 거부된 더 간단한 대안과 그 이유 |
|---------|------------|---------------------------|
| 없음 | - | - |

## Phase 0: 연구 (완료)

**상태**: ✅ 완료 (backend-architect 에이전트)

**주요 결정사항**:

### 1. Filament V4 Wizard 구현

**결정**: `Filament\Schemas\Components\Wizard` 사용

**근거**:
- Filament V4 네이티브 컴포넌트
- Livewire 기반 서버 사이드 상태 관리
- 단계별 검증, UI/UX 내장

**대안 거부**:
- 수동 멀티페이지: 상태 관리 복잡도 증가
- Livewire 커스텀: Filament 재발명
- V3 Wizard: Deprecated 예정

### 2. 인증 미들웨어 통합

**결정**: 커스텀 `EnsureUserHasTenant` Middleware

**근거**:
- Sanctum SPA 세션과 자연스러운 통합
- Filament Panel별 독립 적용
- 온보딩 페이지 제외 로직 단순

**핵심 로직**:
- Panel ID 확인 (Platform/System 제외)
- 온보딩 페이지 라우트 제외
- `User::getTenants()` 빈 배열 → 온보딩 리디렉션

### 3. 데이터 모델 설계

**결정**: 조직-매장 독립 지원 + Scoped Roles

**근거**:
- 기존 스키마 변경 불필요
- `Store.organization_id` nullable로 독립성 보장
- Spatie Permission + 커스텀 `scope_type`, `scope_ref_id`

**핵심 구조**:
- `organizations.name` (필수)
- `stores.name` (필수), `organization_id` (nullable)
- `roles`: `scope_type` (ORG|STORE), `scope_ref_id`
- `model_has_roles`: User ↔ Role 연결

### 4. 멀티테넌시 고려사항

**결정**: Filament Tenancy + Scoped Roles 병행

**근거**:
- Filament 네이티브 Tenancy로 자동 컨텍스트 주입
- Role의 `scope_type`으로 테넌트 타입 구분
- `User::canAccessTenant()`로 세밀한 권한 관리

**조직-매장 독립성**:
- 독립 매장: `organization_id = null`
- 조직 Owner와 매장 Owner 별도 Role

## Phase 1: 설계 및 계약 (진행 중)

### 데이터 모델 (`data-model.md`)

**엔티티 관계**:
```
User (N) ←→ (M) Role
                ↓ (scope_type, scope_ref_id)
         Organization (1) ←→ (N) Store
```

**주요 모델 변경**:

#### User Model
```php
/**
 * 사용자가 접근 가능한 테넌트 목록 반환
 */
public function getTenants(?Filament\Panel $panel = null): Collection
{
    return $this->roles()
        ->where('scope_type', ScopeType::ORGANIZATION->value)
        ->orWhere('scope_type', ScopeType::STORE->value)
        ->get()
        ->map(function ($role) {
            return $role->scope_type === ScopeType::ORGANIZATION->value
                ? Organization::find($role->scope_ref_id)
                : Store::find($role->scope_ref_id);
        })
        ->filter();
}
```

#### OnboardingService
```php
public function createOrganization(User $user, array $data): Organization
{
    return DB::transaction(function () use ($user, $data) {
        $organization = Organization::create(['name' => $data['name']]);

        $ownerRole = Role::firstOrCreate([
            'name' => 'owner',
            'scope_type' => ScopeType::ORGANIZATION->value,
            'scope_ref_id' => $organization->id,
            'guard_name' => 'web',
        ]);

        $user->assignRole($ownerRole);

        return $organization;
    });
}

public function createStore(User $user, array $data): Store
{
    return DB::transaction(function () use ($user, $data) {
        $store = Store::create([
            'name' => $data['name'],
            'organization_id' => null, // 독립 매장
        ]);

        $ownerRole = Role::firstOrCreate([
            'name' => 'owner',
            'scope_type' => ScopeType::STORE->value,
            'scope_ref_id' => $store->id,
            'guard_name' => 'web',
        ]);

        $user->assignRole($ownerRole);

        return $store;
    });
}
```

### Quickstart (`quickstart.md`)

**개발자 빠른 시작**:
1. 미들웨어 생성: `php artisan make:middleware EnsureUserHasTenant`
2. 서비스 생성: `php artisan make:class Services/OnboardingService`
3. Filament 페이지 생성: `php artisan make:filament-page OnboardingWizard`
4. 테스트 생성: `php artisan make:test OnboardingWizardTest`
5. Panel에 미들웨어 등록: `app/Providers/Filament/AppPanelProvider.php`
6. 품질 검증: `composer quality:check`

### 계약 (Contracts)

**해당 없음**: 이 기능은 UI 전용이며 별도 API 엔드포인트 없음. Livewire 컴포넌트 내부 통신만 사용.

## 에이전트 컨텍스트 업데이트

**실행 예정**: `.specify/scripts/bash/update-agent-context.sh claude`

**추가될 기술 컨텍스트**:
- Filament V4 Wizard 컴포넌트
- Spatie Permission Scoped Roles
- Livewire 서버 사이드 상태 관리
- Laravel 멀티테넌시 패턴

## 구현 체크리스트 (Phase 2에서 tasks.md 생성)

### 핵심 기능

- [ ] `OnboardingService.php` 생성 및 메서드 구현
- [ ] `RoleService.php` 생성 (선택적, 복잡도에 따라)
- [ ] `EnsureUserHasTenant.php` 미들웨어 생성
- [ ] `OnboardingWizard.php` Filament 페이지 생성
- [ ] `User::getTenants()` 메서드 추가
- [ ] Panel에 미들웨어 등록
- [ ] Feature Tests 작성
- [ ] Pint + Larastan 실행

### 추가 작업 (후속 PR)

- [ ] Activity Log 이벤트 리스너
- [ ] 추가 정보 수집 Step
- [ ] E2E 테스트 (Dusk)

## 성능 메트릭 목표

| 작업 | 목표 시간 | 쿼리 수 |
|------|-----------|---------|
| 위자드 페이지 로드 | < 500ms | 2개 |
| Step 전환 | < 200ms | 0개 (인메모리) |
| 엔티티 생성 | < 1000ms | 3개 |
| 대시보드 리디렉션 | < 300ms | 1개 |

## 보안 체크리스트

- [x] CSRF 보호 (Filament 자동)
- [x] 입력 검증 (Filament 폼 + 서버)
- [x] DB 트랜잭션 (원자성)
- [x] 권한 확인 (미들웨어)
- [x] SQL Injection 방지 (Eloquent)
- [x] XSS 방지 (Blade/Livewire)

## 다음 단계

1. **Phase 1 완료**: `data-model.md`, `quickstart.md` 생성
2. **Phase 2 진행**: `/speckit.tasks` 실행하여 `tasks.md` 생성
3. **구현 시작**: tasks.md 기준으로 단계별 구현
4. **PR 생성**: GitHub Issue 연결 및 Draft PR
5. **리뷰 & 머지**: 품질 검증 후 머지

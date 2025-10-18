# 사용자 온보딩 위자드 기술 연구 요약

작성일: 2025-10-19
프로젝트: Olulo MX Admin
기술 스택: Laravel 12 + Filament V4 + Sanctum v4 + PostgreSQL

## 핵심 의사 결정 요약

### 1. Filament V4 Wizard 구현

**선택**: `Filament\Schemas\Components\Wizard` 사용

**핵심 근거**:
- Filament V4의 네이티브 컴포넌트로 Schemas 네임스페이스에 통합
- 단계별 폼 검증, 상태 관리, UI/UX가 내장
- Livewire 기반으로 서버 사이드 상태 관리 자동화

**주요 코드 패턴**:

Wizard::make([
    Step::make('유형 선택')
        ->schema([...]),
    Step::make('기본 정보')
        ->schema([...]),
])
->submitAction(view('filament.pages.onboarding-submit-button'))
->skippable(false)

**대안 및 거부 이유**:
- 수동 멀티페이지: 상태 관리 복잡도 증가
- Livewire 커스텀: 재발명, Filament 디자인 불일치
- V3 Wizard: Deprecated 예정

---

### 2. 인증 미들웨어 통합

**선택**: 커스텀 `EnsureUserHasTenant` Middleware + Filament Panel 등록

**핵심 근거**:
- Sanctum SPA 세션과 자연스럽게 통합
- 패널별 독립적 적용 가능 (Organization/Brand/Store만)
- Firebase 인증 완료 후 Laravel 세션 기반 동작
- 명확한 흐름: 인증 → 온보딩 확인 → 패널 접근

**핵심 로직**:

public function handle(Request $request, Closure $next): Response
{
    $user = Auth::user();
    $panel = Filament::getCurrentPanel();

    // Platform/System 패널은 테넌트 불필요
    if (in_array($panel->getId(), ['platform', 'system'])) {
        return $next($request);
    }

    // 이미 온보딩 페이지에 있으면 통과
    if ($request->routeIs('filament.pages.onboarding-wizard')) {
        return $next($request);
    }

    // 테넌트 없으면 온보딩으로 리디렉션
    if ($user->getTenants($panel)->isEmpty()) {
        return redirect()->route('filament.pages.onboarding-wizard');
    }

    return $next($request);
}

**대안 및 거부 이유**:
- Page::mount() 확인: 중복 코드, 누락 가능성
- canAccessPanel() 활용: boolean만 반환, 리디렉션 불가
- Event Listener: 세션마다 확인 필요, 1회성 이벤트

---

### 3. 데이터 모델 설계

**선택**: 조직-매장 독립 지원 + Role 기반 소유권

**핵심 근거**:
- 기존 스키마 유지 (`organizations`, `stores`, `roles` 테이블)
- Store의 유연한 소속 (Organization 직속 또는 독립, nullable FK)
- Spatie Permission + 커스텀 `scope_type`/`scope_ref_id`로 소유권 표현
- 확장성: 추후 Brand 또는 다른 엔티티 타입 쉽게 지원

**핵심 데이터 구조**:

organizations:
  - id, name (필수), description, contact_email, is_active

stores:
  - id, name (필수), brand_id (nullable), organization_id (nullable), is_active

roles (Spatie 확장):
  - id, name, scope_type ('ORG'|'STORE'|'BRAND'), scope_ref_id, guard_name

model_has_roles:
  - role_id, model_type (User::class), model_id (user.id)

**온보딩 생성 로직**:

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

**대안 및 거부 이유**:
- 별도 owner_id 컬럼: Role 중복, 멀티 오너 불가
- Pivot 테이블 분리: Spatie와 이중 구조
- Team 활용: scope_type과 충돌

---

### 4. 멀티테넌시 고려사항

**선택**: Filament Tenancy + Scoped Roles 병행

**핵심 근거**:
- Filament 네이티브 Tenancy로 패널별 자동 컨텍스트 주입
- Role의 `scope_type`으로 테넌트 타입 구분
- `canAccessTenant()`로 세밀한 권한 관리
- 서브도메인 라우팅 향후 확장 준비

**조직-매장 독립성 보장**:

1. **독립 매장**: `store.brand_id = null`, `store.organization_id = null`
   - Owner는 `scope_type=STORE`, `scope_ref_id=store.id` Role 보유

2. **조직 직속 매장**: `store.organization_id = X`, `store.brand_id = null`
   - 매장 Owner와 조직 Owner는 별도 Role (독립적 권한)

**Role 기반 접근 제어**:

public function canAccessPanel(Panel $panel): bool
{
    $scopeType = ScopeType::fromPanelId($panel->getId());

    // 글로벌 패널: 역할 확인
    if (in_array($scopeType, [ScopeType::PLATFORM, ScopeType::SYSTEM])) {
        return $this->hasRole(['platform_admin', 'system_admin']);
    }

    // 테넌트 패널: 멤버십 확인
    return $this->getTenants($panel)->isNotEmpty();
}

public function canAccessTenant(Model $tenant): bool
{
    $scopeType = array_search($tenant::class, ScopeType::getMorphMap(), true);

    return $this->roles()
        ->where('scope_type', $scopeType)
        ->where('scope_ref_id', $tenant->getKey())
        ->exists();
}

**대안 및 거부 이유**:
- Multi Database Tenancy: 초기 과도한 복잡도
- Stancl/Tenancy 패키지: Filament와 충돌

---

## 구현 우선순위

### Phase 1: 핵심 기능 (필수)

1. **OnboardingService** 생성
   - `createOrganization()`, `createStore()` 메서드
   - Owner Role 자동 생성 및 부여
   - DB 트랜잭션 처리

2. **OnboardingWizard** 페이지
   - Wizard 컴포넌트 구성 (유형 선택 → 정보 입력)
   - 폼 검증 및 제출 처리
   - 완료 후 대시보드 리디렉션

3. **EnsureUserHasTenant** 미들웨어
   - 테넌트 확인 로직
   - 온보딩 강제 리디렉션
   - Panel별 적용

4. **Feature Tests**
   - 온보딩 플로우 전체 시나리오
   - Role 부여 검증
   - 리디렉션 확인

### Phase 2: 개선 사항 (권장)

1. **추가 정보 수집**
   - 연락처 정보 (이메일, 전화)
   - 로고 업로드
   - 주소 상세 입력

2. **UX 향상**
   - Step별 진행률 표시
   - 입력 가이드/예시
   - 오류 메시지 개선

3. **감사 로그**
   - Activity Log 기록
   - 생성 이벤트 추적

### Phase 3: 확장 기능 (선택)

1. **초대 시스템**
   - 기존 조직/매장에 구성원 초대
   - 초대 수락 플로우

2. **템플릿 제공**
   - 업종별 매장 템플릿 (레스토랑, 카페 등)
   - 기본 설정 자동 구성

3. **가이드 투어**
   - 대시보드 기능 안내
   - 인터랙티브 튜토리얼

---

## 기술 스택 요약

| 항목 | 기술 | 버전 | 용도 |
|------|------|------|------|
| 백엔드 프레임워크 | Laravel | 12 | 애플리케이션 기반 |
| Admin UI | Filament | V4 | 관리자 패널 + Wizard |
| 인증 | Sanctum | v4 | SPA 세션 관리 |
| 권한 관리 | Spatie Permission | v6 | Role 기반 권한 |
| 데이터베이스 | PostgreSQL | 15+ | 주 데이터 저장소 |
| 상태 관리 | Livewire | v3 | 서버 사이드 UI 상태 |
| 감사 로그 | Spatie Activity Log | v4 | 변경 이력 추적 |

---

## 예상 성능 메트릭

| 작업 | 목표 시간 | 쿼리 수 |
|------|-----------|---------|
| 위자드 페이지 로드 | < 500ms | 2개 (user, roles) |
| Step 전환 | < 200ms | 0개 (Livewire 인메모리) |
| 엔티티 생성 | < 1000ms | 3개 (INSERT org, role, pivot) |
| 대시보드 리디렉션 | < 300ms | 1개 (tenant 조회) |

---

## 보안 체크리스트

- [x] CSRF 보호 (Filament 자동)
- [x] 입력 검증 (폼 레벨 + 서버 레벨)
- [x] DB 트랜잭션 (원자성 보장)
- [x] 권한 확인 (미들웨어 + canAccessTenant)
- [x] SQL Injection 방지 (Eloquent ORM)
- [x] XSS 방지 (Blade 이스케이핑)
- [x] 세션 만료 정책 (config/session.php)
- [ ] 감사 로그 (Activity Log 설정)
- [ ] Rate Limiting (추후 추가)

---

## 의존성 관계도

```
User (인증)
  ↓
EnsureUserHasTenant (미들웨어)
  ↓
OnboardingWizard (Filament Page)
  ↓
OnboardingService (비즈니스 로직)
  ↓
Organization/Store 생성 + Role 부여
  ↓
User::getTenants() 결과 업데이트
  ↓
Dashboard 리디렉션
```

---

## 다음 단계

### 즉시 실행 (이번 PR)

1. `OnboardingService.php` 생성
2. `OnboardingWizard.php` 페이지 생성
3. `EnsureUserHasTenant.php` 미들웨어 생성
4. Panel에 미들웨어 등록
5. Feature Tests 작성
6. Pint + Larastan 실행

### 후속 작업 (다음 PR)

1. 추가 정보 수집 Step 추가
2. 로고 업로드 기능
3. Activity Log 이벤트 리스너
4. E2E 테스트 (Dusk)
5. 사용자 가이드 문서

### 장기 계획

1. 초대 시스템 구현
2. 템플릿 제공 기능
3. 가이드 투어 (Intro.js 등)
4. 서브도메인 라우팅 지원

---

## 참조 문서

### 내부 문서

- **기술 연구 상세**: `/opt/GitHub/olulo-mx-admin/docs/research/user-onboarding-wizard.md`
- **아키텍처 다이어그램**: `/opt/GitHub/olulo-mx-admin/docs/architecture/onboarding-flow.md`
- **구현 가이드**: `/opt/GitHub/olulo-mx-admin/docs/guides/implementing-onboarding.md`
- **프로젝트 1**: `/opt/GitHub/olulo-mx-admin/docs/milestones/project-1.md`
- **인증 설계**: `/opt/GitHub/olulo-mx-admin/docs/auth.md`
- **화이트페이퍼**: `/opt/GitHub/olulo-mx-admin/docs/whitepaper.md`

### 외부 문서

- **Filament V4 Wizards**: https://filamentphp.com/docs/4.x/schemas/wizards
- **Filament Tenancy**: https://filamentphp.com/docs/4.x/panels/tenancy
- **Laravel 12 Middleware**: https://laravel.com/docs/12.x/middleware
- **Spatie Permission**: https://spatie.be/docs/laravel-permission/v6/introduction
- **Livewire 3**: https://livewire.laravel.com/docs/quickstart

---

## 핵심 인사이트

### 설계 철학

1. **기존 구조 최대 활용**: 새로운 테이블 없이 Organization, Store, Role 모델로 구현
2. **Filament 네이티브 우선**: 커스텀 구현보다 Filament 제공 기능 활용
3. **명확한 책임 분리**: Service Layer (비즈니스 로직) + Middleware (접근 제어) + Page (UI)
4. **확장 가능 설계**: 추후 Brand, 초대, 템플릿 쉽게 추가 가능

### 주의 사항

1. **무한 루프 방지**: 미들웨어에서 온보딩 라우트 제외 필수
2. **트랜잭션 필수**: 엔티티 생성 + Role 부여는 원자적 처리
3. **중복 Role 방지**: `firstOrCreate()` 조건에 모든 unique 컬럼 포함
4. **라우트 이름 정확히**: `filament.{panel}.pages.{page}` 패턴 준수

### 성공 기준

- [ ] 소속 없는 사용자 로그인 → 자동 온보딩 리디렉션
- [ ] 조직/매장 생성 완료 → Owner Role 자동 부여
- [ ] 대시보드 접근 성공 (테넌트 컨텍스트 정상)
- [ ] 모든 Feature Tests 통과
- [ ] Pint + Larastan 통과
- [ ] N+1 쿼리 없음 (Telescope 확인)

---

## 결론

Laravel 12 + Filament V4 환경에서 사용자 온보딩 위자드는 **Wizard 컴포넌트**, **커스텀 미들웨어**, **OnboardingService** 3가지 핵심 요소로 구성되며, 기존 데이터 모델과 Filament Tenancy 시스템을 최대한 활용하여 최소한의 코드로 구현 가능합니다.

**추정 구현 시간**: 4-6시간 (테스트 포함)
**복잡도**: 중급
**유지보수성**: 높음 (Filament 네이티브 패턴 준수)
**확장성**: 높음 (Role 기반, MorphTo 관계)

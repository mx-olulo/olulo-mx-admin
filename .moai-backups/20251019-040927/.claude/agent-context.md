# Agent Context: Olulo MX Admin

**작성일**: 2025-10-18
**버전**: 1.2
**목적**: Claude Code 서브 에이전트들이 프로젝트의 기술 스택, 아키텍처 패턴, 주요 결정사항을 이해하고 일관된 코드를 생성하도록 돕는 컨텍스트 문서

---

## 프로젝트 개요

Olulo MX는 멕시코 시장을 위한 멀티테넌트 전자상거래 플랫폼입니다.

### 핵심 특징
- **멀티테넌시**: Organization → Brand → Store 계층 구조
- **도메인 모델 테넌시**: 서브도메인 기반 호스트 분리
- **인증**: Firebase Authentication + Laravel Sanctum SPA 세션
- **관리자**: Filament v4 (매장/브랜드) + Nova v5 (마스터)
- **고객앱**: React 19.1 PWA
- **결제**: operacionesenlinea.com (멕시코)
- **알림**: WhatsApp Business API

---

## 기술 스택

### Backend
- **PHP**: 8.4.13
- **Laravel**: v12
- **Filament**: v4
- **Nova**: v5
- **Livewire**: v3
- **Spatie Permission**: v6 (teams 기능 활성화)
- **Sanctum**: v4
- **Larastan**: v3 (PHPStan Level 10)
- **Pint**: v1
- **Pest**: v3

### Frontend
- **React**: v19
- **Inertia.js**: v2
- **TailwindCSS**: v4
- **daisyUI**: 최신 버전

### Infrastructure
- **Database**: PostgreSQL 15+
- **Cache/Queue**: Redis 7.x (DB 0: sessions/queue, DB 1: cache)
- **Notifications**: WhatsApp Business API + Laravel Notifications

### Testing
- **Pest PHP**: Feature/Unit tests
- **Dusk**: Browser testing (Laravel v8)

---

## 아키텍처 패턴

### 1. 멀티테넌시 아키텍처

#### 도메인 모델 계층
```
Organization (조직)
├── Brand (브랜드)
│   ├── Store (매장) #1
│   ├── Store (매장) #2
│   └── Store (매장) #3
└── Brand (브랜드)
    └── Store (매장) #4
```

#### 서브도메인 격리
- **개발**: `{tenant}.dev.olulo.com.mx`
- **스테이징**: `{tenant}.stg.olulo.com.mx`
- **프로덕션**: `{tenant}.olulo.com.mx`

#### Filament Panel 매핑
- `org` Panel: Organization 스코프
- `brand` Panel: Brand 스코프
- `store` Panel: Store 스코프

### 2. RBAC with Spatie Permission v6

#### team_id 기반 격리
Spatie Permission의 `teams=true` 기능을 활용하여 다중 스코프 RBAC 구현:

```php
// Role 모델 확장 필드
- team_id (auto-increment, Spatie 기본)
- scope_type (VARCHAR: 'ORG', 'BRAND', 'STORE')
- scope_ref_id (BIGINT: Organization/Brand/Store의 ID)

// 역할 명명 규칙
'org-owner'       // ORGANIZATION 스코프, 최고 권한
'brand-manager'   // BRAND 스코프, 관리자
'store-staff'     // STORE 스코프, 직원
```

#### 컨텍스트 기반 권한 검사
```php
setPermissionsTeamId($role->team_id);
$user->hasRole('store-owner');  // 현재 team_id 컨텍스트에서 검사
```

### 3. Firebase Authentication + Sanctum 세션

#### 단일 프로젝트, URL 기반 차별화
- **Admin**: `/auth/login`, `/auth/register`, `/auth/firebase/callback`
- **Customer**: `/customer/auth/login`, `/customer/auth/register`, `/customer/auth/firebase/callback`

#### Request-scoped Session Attribute 패턴
```php
class DetectOnboardingEntryPoint
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. URL 경로 기반 감지
        $entryPoint = $this->detectFromPath($request);

        // 2. 세션에 저장 (온보딩 완료 시까지 유지)
        $request->session()->put('onboarding.entry_point', $entryPoint);

        // 3. Request 속성에도 캐싱 (현재 요청 내 재사용)
        $request->attributes->set('onboarding_entry_point', $entryPoint);

        return $next($request);
    }
}
```

### 4. 이벤트 기반 아키텍처 (Event-Driven)

#### Laravel Events + Queue-based Listeners
```php
// Event
class OnboardingCompleted
{
    public User $user;
    public Role $role;
    public string $entityType;
    public int $entityId;
}

// Listener (ShouldQueue)
class SendWelcomeNotification implements ShouldQueue
{
    public $tries = 3;
    public $backoff = 60;

    public function handle(OnboardingCompleted $event): void
    {
        $event->user->notify(new WelcomeNotification());
    }

    public function failed(OnboardingCompleted $event, \Throwable $exception): void
    {
        Log::error('Welcome notification failed', [...]);
    }
}
```

### 5. Service Layer Pattern

#### OnboardingService 예시
```php
class OnboardingService
{
    public function complete(array $data): array
    {
        DB::beginTransaction();
        try {
            // 1. 엔터티 생성 (Store/Brand/Organization)
            // 2. Role 생성
            // 3. 역할 부여
            // 4. OnboardingCompleted 이벤트 발행
            // 5. 리다이렉트 URL 생성
            DB::commit();
            return ['entity' => $store, 'role' => $role, 'redirect_url' => $url];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
```

### 6. Redis with TTL (임시 저장)

#### OnboardingSessionService
```php
class OnboardingSessionService
{
    private const TTL_SECONDS = 900; // 15분
    private const KEY_PREFIX = 'onboarding:';

    public function create(string $userId, int $storeId, string $mode): string
    {
        $sessionId = Str::uuid()->toString();

        Cache::store('redis')->put(
            self::KEY_PREFIX . $sessionId,
            $data,
            self::TTL_SECONDS
        );

        return $sessionId;
    }
}
```

#### 사용 사례
- 온보딩 임시 세션 (5-15분 수명)
- 완료 후 영구 테이블로 이관
- 자동 만료로 정리 오버헤드 제거

---

## 주요 결정사항

### 온보딩 플로우 (002 & 003 스펙)

#### 1. Filament Wizard 구현 패턴
**선택**: `HasWizard` trait + Resource CreateRecord 패턴

```php
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Wizard\Step;

class CreateOnboarding extends CreateRecord
{
    use CreateRecord\Concerns\HasWizard;

    protected function getSteps(): array
    {
        return [
            Step::make('account_type')
                ->schema([...])
                ->visible(fn (Forms\Get $get): bool => ...),
            Step::make('organization')
                ->schema([...])
                ->afterValidation(function ($livewire) {...}),
        ];
    }
}
```

**근거**: Filament v4 네이티브, 자동 상태 관리, 조건부 스텝 가시성

#### 2. 초대 기반 온보딩 (003 스펙 - 보안 강화)
**선택**: Signed URL + Email Verification + Enum-based State Machine

**핵심 변경사항**:
- ❌ **제거**: 무소속 사용자의 기존 엔티티 직접 참여 신청 (보안 위험)
- ✅ **추가**: 초대장(Invitation) 기반 온보딩 - 명시적 승인 통제

```php
// 초대장 토큰 생성 (Signed URL)
$invitationUrl = URL::temporarySignedRoute(
    'invitations.accept',
    now()->addDays(7),
    ['token' => $invitation->token, 'email' => $invitation->email]
);

// 이메일 정규화 (대소문자, Gmail aliases)
EmailNormalizationService::normalize($email);

// 상태 전이 (Enum-based)
$invitation->accept($user);  // PENDING → ACCEPTED
```

**보안 메커니즘**:
1. **Signed URL**: 변조 방지, 자동 만료 (7일)
2. **이메일 매칭 검증**: 초대받은 이메일 ↔ 로그인 이메일 일치 확인 (정규화 후)
3. **Firebase UID 바인딩**: 초대 수락 시 Firebase UID와 이메일 이중 검증
4. **상태 머신**: 잘못된 상태 전이 원천 차단 (EXPIRED → ACCEPTED 불가)

**근거**: 무작위 참여 방지, OWASP 접근 통제 원칙, Zero Trust 보안 패러다임

#### 3. 참여 요청 워크플로우 (002 스펙 - Deprecated)
**선택**: 이벤트 기반 상태 머신 (Event-Driven State Machine)

**⚠️ 중요**: 003 스펙에서 JoinRequest는 Invitation으로 완전 대체됨

```php
class JoinRequest extends Model
{
    protected $casts = [
        'status' => JoinRequestStatus::class, // Enum: pending, approved, rejected
    ];

    public function approve(User $approver): void
    {
        $this->update([
            'status' => JoinRequestStatus::APPROVED,
            'processed_by' => $approver->id,
            'processed_at' => now(),
        ]);

        event(new JoinRequestApproved($this));
    }
}
```

**근거**: 관심사 분리, 확장성, Queue 기반 비동기 처리

#### 4. Panel 리다이렉트 메커니즘
**선택**: Filament 내장 `Panel::getUrl(Model $tenant)` 메서드

```php
class OnboardingRedirectService
{
    public function getRedirectUrl(Model $tenant, User $user): string
    {
        $scopeType = $this->getScopeTypeForTenant($tenant);
        $panel = Filament::getPanel($scopeType->getPanelId());
        return $panel->getUrl($tenant);
    }
}
```

**근거**: Filament 내장 메커니즘, 명시적 테넌트 전달, UX 최적화

#### 5. 다국어 지원 전략
**선택**: 모듈별 PHP 배열 구조 + Filament 네이티브 번역

```
lang/
├── en/
│   ├── onboarding.php         # 신규: 온보딩 플로우 전용
│   └── validation.php         # 신규: 커스텀 검증 메시지
├── es-MX/
│   └── (동일 구조)
└── ko/
    └── (동일 구조)
```

```php
TextInput::make('name')
    ->label(__('onboarding.step.store_info.name_label'))
    ->placeholder(__('onboarding.step.store_info.name_placeholder'));
```

**근거**: 타입 안전성, Filament 통합, 모듈별 분리 (300라인 규칙 준수)

---

## 코드 컨벤션

### 파일 크기 제한
- **필수**: 한 파일에 300라인 이상 금지
- **해결책**: Trait, Interface, Service 클래스로 분할

### Artisan 명령 우선 사용
```bash
# 모델 생성
php artisan make:model Store -mfsc

# 컨트롤러 생성
php artisan make:controller StoreController --resource

# Filament Resource 생성
php artisan make:filament-resource Store --generate
```

### 변수/필드명 일관성
- 새 이름 생성 전 기존 유사 용도 확인 필수
- `php artisan model:show Store` 명령으로 모델 구조 확인

### 코드 품질 검사 순서
```bash
# 1. Composer 검증
composer validate

# 2. 코드 스타일 검사
vendor/bin/pint --test

# 3. 정적 분석
php -d memory_limit=-1 vendor/bin/phpstan analyse

# 4. 테스트 실행
php artisan test
```

---

## 테스트 전략

### Pest PHP 패턴
```php
it('creates a new store with owner role', function () {
    $user = User::factory()->create();

    $response = actingAs($user)->post('/onboarding/complete', [
        'type' => 'create_store',
        'store_name' => 'Test Store',
    ]);

    $response->assertRedirect();
    expect($user->fresh()->hasRole('store-owner'))->toBeTrue();
});
```

### Filament 테스트
```php
livewire(CreateOnboarding::class)
    ->fillForm([
        'type' => 'create_store',
        'store_name' => 'Test Store',
    ])
    ->call('create')
    ->assertNotified()
    ->assertRedirect();
```

---

## 문서 우선 원칙

### 코드 작성 전 문서 갱신
1. `docs/` 문서 먼저 갱신
2. 코드 작성
3. PR 생성 (문서 + 코드)

### 브랜치 전략
- `feature/*`: 신규 기능
- `chore/*`: 설정, 문서, 의존성
- `fix/*`: 버그 수정

### PR 규칙
- **제목**: `type(scope): 한국어 요약`
- **본문**: 목적/변경점/체크리스트/참고 링크 (모두 한국어)
- **리뷰**: CODEOWNERS 자동 할당

---

## 서브 에이전트 활용 가이드

### 전용 에이전트
- **code-author.md**: 코드 작성
- **code-reviewer.md**: 코드 검토
- **laravel-expert.md**: Laravel 전문
- **filament-expert.md**: Filament 전문
- **nova-expert.md**: Nova 전문
- **react-expert.md**: React 전문
- **database-expert.md**: DB 전문

### 파이프라인
- `.claude/pipelines/default.yaml`: 기본 워크플로우
- `.claude/pipelines/extended.yaml`: 확장 워크플로우

---

## 금지 사항

1. 민감 정보 하드코딩 (.env 파일 사용)
2. 보호 브랜치 직접 푸시 (PR 경유)
3. 300라인 초과 파일 생성
4. Artisan 명령 없이 모델/마이그레이션 직접 생성
5. 테스트/정적 분석 없이 커밋

---

## 추가 참고 문서

### 프로젝트 문서
- `docs/whitepaper.md`: 아키텍처/배경
- `docs/milestones/project-1.md`: 프로젝트 1 상세
- `docs/auth.md`: 인증/세션
- `docs/tenancy/host-middleware.md`: 테넌시 설계
- `docs/admin/filament-setup.md`: Filament 설정

### 외부 문서
- Laravel 12: https://laravel.com/docs/12.x
- Filament 4: https://filamentphp.com/docs
- Nova v5: https://nova.laravel.com/docs/5.0/
- React 19: https://react.dev/
- Spatie Permission: https://spatie.be/docs/laravel-permission/v6/

---

**최종 업데이트**: 2025-10-18
**검토자**: Claude Code
**버전**: 1.2

---

## 변경 이력

### v1.2 (2025-10-18)
- **추가**: 초대 기반 온보딩 시스템 (003 스펙) 패턴 및 보안 메커니즘
  - Signed URL 토큰 생성 전략
  - 이메일 정규화 및 매칭 검증
  - Enum-based 상태 머신
  - Firebase UID 이중 검증
- **명확화**: JoinRequest (002) → Invitation (003) 대체 관계
- **강조**: Zero Trust 보안 원칙 적용

### v1.1 (2025-10-18)
- **추가**: 무소속 사용자 온보딩 플로우 (002 스펙) 주요 결정사항
  - Filament Wizard 패턴
  - OnboardingSessionService (Redis TTL)
  - Panel 리다이렉트 메커니즘
  - 다국어 지원 전략
- **초기 버전**: 기술 스택, RBAC, 멀티테넌시 아키텍처 문서화

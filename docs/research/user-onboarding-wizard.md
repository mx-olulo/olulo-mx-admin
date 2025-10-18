# 사용자 온보딩 위자드 기술 연구 보고서

작성일: 2025-10-19
대상 프로젝트: Olulo MX Admin
기술 스택: Laravel 12 + Filament V4 + Sanctum v4 + PostgreSQL

## 목적

소속 없는 사용자가 로그인 후 조직(Organization) 또는 매장(Store)을 생성하여 온보딩을 완료하고, 생성자/소유자 Role을 자동 부여받는 위자드 시스템의 기술적 구현 방안을 연구합니다.

## 1. Filament V4 Wizard 구현

### 결정: Filament\Schemas\Components\Wizard 사용

Filament V4에서는 `Filament\Schemas\Components\Wizard` 컴포넌트를 사용하여 멀티스텝 폼을 구현합니다.

### 근거

1. **네이티브 통합**: Filament V4는 Wizard를 Schemas 네임스페이스로 재구성하여 Form, Infolist, Table과 일관된 API를 제공
2. **검증 내장**: 각 Step마다 독립적인 유효성 검증 지원
3. **상태 관리**: Livewire 기반으로 서버 사이드 상태 관리가 자동화
4. **UI/UX**: 진행 표시, 아이콘, 설명 등 사용자 친화적 인터페이스 내장

### 구현 예시

app/Filament/Pages/OnboardingWizard.php:

use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;

class OnboardingWizard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-user-plus';
    protected static string $view = 'filament.pages.onboarding-wizard';

    public ?array $data = [];

    public function mount(): void
    {
        // 이미 소속이 있는 사용자는 대시보드로 리디렉션
        if ($this->hasAnyTenant()) {
            redirect()->route('filament.store.pages.dashboard');
        }

        $this->form->fill();
    }

    protected function getFormSchema(): array
    {
        return [
            Wizard::make([
                Step::make('선택')
                    ->description('조직 또는 매장을 선택하세요')
                    ->icon('heroicon-o-building-office')
                    ->schema([
                        Select::make('entity_type')
                            ->label('유형')
                            ->options([
                                'organization' => '조직',
                                'store' => '매장',
                            ])
                            ->required()
                            ->reactive(),
                    ]),

                Step::make('생성')
                    ->description('기본 정보를 입력하세요')
                    ->icon('heroicon-o-pencil-square')
                    ->schema([
                        TextInput::make('name')
                            ->label('이름')
                            ->required()
                            ->maxLength(255),
                    ]),
            ])
            ->submitAction(view('filament.pages.onboarding-submit-button'))
        ];
    }

    protected function hasAnyTenant(): bool
    {
        return auth()->user()->getTenants(filament()->getCurrentPanel())->isNotEmpty();
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        // 엔티티 생성 로직 (섹션 3 참조)
        // ...

        redirect()->route('filament.store.pages.dashboard');
    }
}

### 주요 메서드

- `Wizard::make()`: 위자드 컨테이너 생성
- `Step::make(string $name)`: 개별 단계 정의
- `->schema(array)`: 각 단계의 폼 필드 정의
- `->submitAction()`: 마지막 단계에 제출 버튼 렌더링
- `->icon(string)`: 단계 아이콘 설정
- `->description(string)`: 단계 설명 추가
- `->skippable()`: 단계 건너뛰기 허용 (온보딩에서는 비권장)
- `->startOnStep(int)`: 특정 단계에서 시작

### 고려된 대안

1. **수동 멀티페이지 구현**: 각 단계를 별도 페이지로 구현
   - 거부 이유: 상태 관리 복잡도 증가, 뒤로가기 처리 어려움

2. **Livewire 커스텀 컴포넌트**: Wizard 없이 직접 구현
   - 거부 이유: 재발명(reinventing the wheel), Filament 디자인 시스템과 불일치

3. **Filament V3 Wizard**: Forms 네임스페이스의 Wizard 사용
   - 거부 이유: V4에서 Schemas로 이동, 구 API는 deprecated 예정

## 2. 인증 미들웨어 통합

### 결정: 커스텀 Middleware + Filament Panel 설정

소속 없는 사용자를 온보딩 위자드로 강제 리디렉션하는 미들웨어를 구현하고, Filament Panel에 등록합니다.

### 근거

1. **세션 기반 검증**: Sanctum SPA 세션과 자연스럽게 통합
2. **패널별 분리**: Organization/Brand/Store 패널에만 적용 가능
3. **Firebase 독립성**: Firebase 인증 완료 후 Laravel 세션 기반으로 동작
4. **명확한 흐름**: 인증 → 온보딩 확인 → 패널 접근 순서 보장

### 구현 예시

app/Http/Middleware/EnsureUserHasTenant.php:

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Filament\Facades\Filament;

class EnsureUserHasTenant
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if (! $user) {
            // 인증되지 않은 사용자는 로그인 페이지로
            return redirect()->route('filament.auth.login');
        }

        $panel = Filament::getCurrentPanel();

        // Platform/System 패널은 테넌트 불필요
        if (in_array($panel->getId(), ['platform', 'system'])) {
            return $next($request);
        }

        // 이미 테넌트가 있는지 확인
        $tenants = $user->getTenants($panel);

        if ($tenants->isEmpty()) {
            // 온보딩 위자드로 리디렉션
            return redirect()->route('filament.pages.onboarding-wizard');
        }

        return $next($request);
    }
}

### Panel 등록

app/Providers/Filament/StorePanelProvider.php:

use App\Http\Middleware\EnsureUserHasTenant;

public function panel(Panel $panel): Panel
{
    return $panel
        ->middleware([
            // ... 기존 미들웨어
            EnsureUserHasTenant::class,
        ])
        // ...
}

### Role 확인 로직

User 모델의 기존 `getTenants()` 메서드 활용:

- Spatie Permission의 `model_has_roles` 테이블 조회
- `scope_type`이 현재 패널과 일치하는 Role만 필터링
- `scopeable` MorphTo 관계로 실제 테넌트(Organization/Brand/Store) 로드

### 고려된 대안

1. **Page::mount()에서 확인**: 각 페이지의 mount 메서드에서 개별 확인
   - 거부 이유: 중복 코드, 누락 가능성, 일관성 부족

2. **Filament의 canAccessPanel() 활용**: User 모델의 canAccessPanel에서 리디렉션
   - 거부 이유: 해당 메서드는 boolean만 반환, 리디렉션 불가

3. **Event Listener 사용**: Login 이벤트 리스너에서 확인
   - 거부 이유: 세션 접속마다 확인 필요, 이벤트는 최초 1회만

## 3. 데이터 모델 설계

### 결정: 조직-매장 독립 지원 + Role 기반 소유권

기존 모델 구조를 활용하되, 온보딩 시 자동으로 Owner Role을 부여합니다.

### 근거

1. **기존 스키마 준수**: 이미 구현된 `organizations`, `stores`, `roles` 테이블 활용
2. **유연한 소속**: Store는 Organization 직속 또는 독립 가능 (brand_id, organization_id 모두 nullable)
3. **Role 기반 권한**: Spatie Permission + 커스텀 scope_type/scope_ref_id로 소유권 표현
4. **확장성**: 추후 Brand 추가 또는 다른 엔티티 타입 쉽게 지원

### 데이터 구조 (기존)

organizations 테이블:
- id (PK)
- name (필수)
- description (선택)
- contact_email (선택)
- contact_phone (선택)
- is_active (기본 true)

stores 테이블:
- id (PK)
- brand_id (nullable, FK to brands)
- organization_id (nullable, FK to organizations)
- name (필수)
- description (선택)
- address (선택)
- phone (선택)
- is_active (기본 true)

roles 테이블 (Spatie Permission 확장):
- id (PK)
- name (예: 'owner', 'manager')
- guard_name (기본 'web')
- scope_type (예: 'ORG', 'STORE')
- scope_ref_id (FK to scopeable)
- team_id (Spatie 호환용)

model_has_roles 테이블:
- role_id (FK to roles)
- model_type (User::class)
- model_id (user.id)
- team_id (nullable)

### 온보딩 생성 로직

app/Services/OnboardingService.php:

namespace App\Services;

use App\Models\Organization;
use App\Models\Store;
use App\Models\Role;
use App\Models\User;
use App\Enums\ScopeType;
use Illuminate\Support\Facades\DB;

class OnboardingService
{
    public function createOrganization(User $user, array $data): Organization
    {
        return DB::transaction(function () use ($user, $data) {
            // 1. 조직 생성
            $organization = Organization::create([
                'name' => $data['name'],
                'is_active' => true,
            ]);

            // 2. Owner Role 생성/조회
            $ownerRole = Role::firstOrCreate([
                'name' => 'owner',
                'scope_type' => ScopeType::ORGANIZATION->value,
                'scope_ref_id' => $organization->id,
                'guard_name' => 'web',
            ]);

            // 3. 사용자에게 Role 부여
            $user->assignRole($ownerRole);

            return $organization;
        });
    }

    public function createStore(User $user, array $data): Store
    {
        return DB::transaction(function () use ($user, $data) {
            // 1. 매장 생성 (독립형)
            $store = Store::create([
                'name' => $data['name'],
                'is_active' => true,
                // brand_id, organization_id는 null (독립 매장)
            ]);

            // 2. Owner Role 생성/조회
            $ownerRole = Role::firstOrCreate([
                'name' => 'owner',
                'scope_type' => ScopeType::STORE->value,
                'scope_ref_id' => $store->id,
                'guard_name' => 'web',
            ]);

            // 3. 사용자에게 Role 부여
            $user->assignRole($ownerRole);

            return $store;
        });
    }
}

### 완료 후 리디렉션

// OnboardingWizard::submit()
public function submit(): void
{
    $data = $this->form->getState();
    $user = auth()->user();

    $service = app(OnboardingService::class);

    if ($data['entity_type'] === 'organization') {
        $organization = $service->createOrganization($user, $data);

        // Organization 패널로 리디렉션
        redirect()->route('filament.organization.pages.dashboard', [
            'tenant' => $organization->id,
        ]);
    } else {
        $store = $service->createStore($user, $data);

        // Store 패널로 리디렉션 (기본 패널)
        redirect()->route('filament.store.pages.dashboard', [
            'tenant' => $store->id,
        ]);
    }
}

### 마이그레이션 전략

기존 마이그레이션 유지 (변경 불필요):
- `2025_10_11_053015_create_organizations_table.php`
- `2025_10_11_053017_create_stores_table.php`
- `2025_09_26_152355_create_permission_tables.php` (Spatie)
- `2025_10_11_022059_add_scope_fields_to_roles_table.php`

추가 마이그레이션 없음. 기존 스키마로 충분히 지원 가능.

### 고려된 대안

1. **별도 owner 컬럼 추가**: organizations/stores 테이블에 `owner_id` 컬럼
   - 거부 이유: Role 시스템과 중복, 멀티 오너 지원 어려움

2. **Pivot 테이블 분리**: `organization_user`, `store_user` 테이블
   - 거부 이유: Spatie Permission과 이중 구조, 권한 확인 복잡도 증가

3. **자동 Team 생성**: Spatie의 team_id 활용
   - 거부 이유: scope_type/scope_ref_id 구조와 충돌, 혼란 초래

## 4. 멀티테넌시 고려사항

### 결정: Filament Tenancy + Scoped Roles 병행

Filament의 네이티브 Tenancy 시스템을 기반으로 하되, Role의 scope_type으로 테넌트 타입을 구분합니다.

### 근거

1. **패널별 분리**: Organization/Brand/Store 패널이 독립적으로 운영
2. **자동 스코핑**: Filament Tenancy가 현재 테넌트 컨텍스트를 자동 주입
3. **Role 기반 접근 제어**: canAccessTenant()로 세밀한 권한 관리
4. **서브도메인 라우팅**: 향후 store1.olulo.com.mx 형태 지원 준비

### 조직-매장 독립성 보장

현재 구조에서 이미 보장됨:

1. **독립 매장**: `store.brand_id = null`, `store.organization_id = null`
   - Owner는 `scope_type=STORE`, `scope_ref_id=store.id` Role 보유

2. **조직 직속 매장**: `store.organization_id = X`, `store.brand_id = null`
   - 매장 Owner: `scope_type=STORE`, `scope_ref_id=store.id`
   - 조직 Owner: `scope_type=ORGANIZATION`, `scope_ref_id=X` (별도)

3. **브랜드 소속 매장**: `store.brand_id = Y`, `store.organization_id = null`
   - 유사한 방식으로 독립성 유지

### Role 기반 접근 제어

User::canAccessTenant() 활용:

public function canAccessPanel(Panel $panel): bool
{
    $scopeType = ScopeType::fromPanelId($panel->getId());

    // Platform/System: 글로벌 역할
    if (in_array($scopeType, [ScopeType::PLATFORM, ScopeType::SYSTEM])) {
        return $this->hasRole(['platform_admin', 'system_admin']);
    }

    // Organization/Brand/Store: 테넌트 멤버십
    return $this->getTenants($panel)->isNotEmpty();
}

public function canAccessTenant(Model $tenant): bool
{
    $scopeType = array_search($tenant::class, ScopeType::getMorphMap(), true);

    if ($scopeType === false) {
        return false;
    }

    return $this->roles()
        ->where('scope_type', $scopeType)
        ->where('scope_ref_id', $tenant->getKey())
        ->exists();
}

### 서브도메인 라우팅 (향후)

현재는 `/organization/{tenant}`, `/store/{tenant}` 경로 사용.

향후 확장:
- `store1.olulo.com.mx` → `tenant = store1` (code 기반 조회)
- 미들웨어에서 `request()->getHost()` 파싱
- `stores.code` 컬럼 추가 (마이그레이션)

### 고려된 대안

1. **Single Database Tenancy**: 모든 테넌트가 단일 DB 공유
   - 채택 이유: 현재 구조가 이미 이 방식 (store_id 스코핑)

2. **Multi Database Tenancy**: 테넌트별 독립 DB
   - 거부 이유: 초기 단계에서 과도한 복잡도, 관리 오버헤드

3. **Stancl/Tenancy 패키지**: 서드파티 멀티테넌시 패키지
   - 거부 이유: Filament 네이티브 Tenancy와 충돌, 불필요한 레이어

## 5. 구현 체크리스트

### 5.1. Wizard 페이지 생성
- [ ] `app/Filament/Pages/OnboardingWizard.php` 생성
- [ ] `resources/views/filament/pages/onboarding-wizard.blade.php` 뷰 생성
- [ ] `resources/views/filament/pages/onboarding-submit-button.blade.php` 버튼 뷰 생성
- [ ] Step 정의 (선택 → 생성)
- [ ] 폼 스키마 구성 (entity_type, name)

### 5.2. 서비스 레이어
- [ ] `app/Services/OnboardingService.php` 생성
- [ ] `createOrganization()` 메서드 구현
- [ ] `createStore()` 메서드 구현
- [ ] 트랜잭션 처리 및 롤백 로직

### 5.3. 미들웨어
- [ ] `app/Http/Middleware/EnsureUserHasTenant.php` 생성
- [ ] 테넌트 확인 로직 구현
- [ ] 온보딩 위자드 리디렉션 처리
- [ ] StorePanelProvider, OrganizationPanelProvider에 미들웨어 등록

### 5.4. 라우팅
- [ ] OnboardingWizard 페이지 라우트 등록 (패널 외부)
- [ ] 완료 후 리디렉션 경로 확인

### 5.5. Role 자동 생성
- [ ] ScopeType Enum에 ORGANIZATION, STORE 값 확인
- [ ] Role::firstOrCreate() 로직 테스트
- [ ] User::assignRole() 동작 확인

### 5.6. 테스트
- [ ] Feature Test: 소속 없는 사용자 로그인 시 온보딩 페이지 표시
- [ ] Feature Test: 조직 생성 시 Owner Role 부여
- [ ] Feature Test: 매장 생성 시 Owner Role 부여
- [ ] Feature Test: 완료 후 대시보드 리디렉션
- [ ] Feature Test: 이미 소속이 있는 사용자는 온보딩 건너뜀

### 5.7. 문서화
- [ ] `docs/features/onboarding.md` 작성
- [ ] 사용자 가이드 (관리자 매뉴얼)
- [ ] API 문서 업데이트 (필요시)

## 6. 보안 고려사항

1. **CSRF 보호**: Filament 폼은 자동으로 CSRF 토큰 포함
2. **입력 검증**: `->required()`, `->maxLength()` 등 폼 레벨 검증
3. **트랜잭션**: DB::transaction()으로 원자성 보장
4. **권한 확인**: 온보딩은 인증된 사용자만 가능 (미들웨어)
5. **중복 생성 방지**: `firstOrCreate()` 사용, unique 제약 (필요시)

## 7. 성능 최적화

1. **N+1 방지**: Role::with('scopeable') 사용
2. **캐싱**: User::getTenants() 결과를 세션 기간 동안 캐시 고려
3. **인덱스**: `roles.scope_type`, `roles.scope_ref_id` 복합 인덱스 (이미 존재)
4. **지연 로딩**: 온보딩 페이지에서만 필요한 관계는 eager load 생략

## 8. 추후 확장 계획

1. **멀티스텝 확장**: 추가 정보 입력 (주소, 연락처, 로고 업로드 등)
2. **초대 기능**: 기존 조직/매장에 초대받은 사용자는 온보딩 건너뜀
3. **브랜드 지원**: Organization 생성 후 Brand 추가 옵션
4. **템플릿 선택**: 업종별 매장 템플릿 (레스토랑, 카페 등)
5. **가이드 투어**: 온보딩 완료 후 대시보드 기능 안내

## 참조 문서

- Filament V4 Wizards: https://filamentphp.com/docs/4.x/schemas/wizards
- 프로젝트 1: `/opt/GitHub/olulo-mx-admin/docs/milestones/project-1.md`
- 인증 설계: `/opt/GitHub/olulo-mx-admin/docs/auth.md`
- 화이트페이퍼: `/opt/GitHub/olulo-mx-admin/docs/whitepaper.md`
- Spatie Permission: https://spatie.be/docs/laravel-permission/v6/introduction

## 결론

Laravel 12 + Filament V4 환경에서 사용자 온보딩 위자드는 다음 3가지 핵심 요소로 구성됩니다:

1. **Filament Wizard 컴포넌트**: 멀티스텝 폼 UI와 검증
2. **커스텀 미들웨어**: 소속 없는 사용자 강제 리디렉션
3. **OnboardingService**: 조직/매장 생성 + Owner Role 자동 부여

기존 데이터 모델과 Filament Tenancy 시스템을 최대한 활용하여 최소한의 코드로 구현 가능하며, 향후 확장성도 확보되어 있습니다.

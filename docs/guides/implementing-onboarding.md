# 온보딩 위자드 구현 가이드

작성일: 2025-10-19
난이도: 중급
예상 소요 시간: 4-6시간

## 전제 조건

- Laravel 12 설치 완료
- Filament V4 설치 완료
- Spatie Permission 설정 완료
- Organization, Store, Role 모델 존재
- Firebase 인증 구성 완료

## 단계별 구현

### 1단계: OnboardingService 생성

#### 1.1. 서비스 클래스 생성

php artisan make:class Services/OnboardingService

#### 1.2. 코드 작성

app/Services/OnboardingService.php:

<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Organization;
use App\Models\Store;
use App\Models\Role;
use App\Models\User;
use App\Enums\ScopeType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OnboardingService
{
    /**
     * 조직 생성 및 Owner Role 부여
     */
    public function createOrganization(User $user, array $data): Organization
    {
        return DB::transaction(function () use ($user, $data) {
            // 1. 조직 생성
            $organization = Organization::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'contact_email' => $data['contact_email'] ?? $user->email,
                'is_active' => true,
            ]);

            // 2. Owner Role 생성/조회
            $ownerRole = $this->createOrFindOwnerRole(
                ScopeType::ORGANIZATION,
                $organization->id
            );

            // 3. 사용자에게 Role 부여
            $user->assignRole($ownerRole);

            // 4. Activity Log 기록
            activity('onboarding')
                ->performedOn($organization)
                ->causedBy($user)
                ->log('조직 생성 및 소유자 권한 부여');

            return $organization;
        });
    }

    /**
     * 매장 생성 및 Owner Role 부여
     */
    public function createStore(User $user, array $data): Store
    {
        return DB::transaction(function () use ($user, $data) {
            // 1. 매장 생성 (독립형)
            $store = Store::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'is_active' => true,
                // brand_id, organization_id는 null (독립 매장)
            ]);

            // 2. Owner Role 생성/조회
            $ownerRole = $this->createOrFindOwnerRole(
                ScopeType::STORE,
                $store->id
            );

            // 3. 사용자에게 Role 부여
            $user->assignRole($ownerRole);

            // 4. Activity Log 기록
            activity('onboarding')
                ->performedOn($store)
                ->causedBy($user)
                ->log('매장 생성 및 소유자 권한 부여');

            return $store;
        });
    }

    /**
     * Owner Role 생성 또는 조회
     */
    protected function createOrFindOwnerRole(ScopeType $scopeType, int $scopeRefId): Role
    {
        return Role::firstOrCreate(
            [
                'scope_type' => $scopeType->value,
                'scope_ref_id' => $scopeRefId,
                'name' => 'owner',
                'guard_name' => 'web',
            ],
            [
                'team_id' => null, // 글로벌 역할 아님
            ]
        );
    }
}

### 2단계: OnboardingWizard 페이지 생성

#### 2.1. Artisan 명령

php artisan make:filament-page OnboardingWizard

#### 2.2. 페이지 클래스 작성

app/Filament/Pages/OnboardingWizard.php:

<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Services\OnboardingService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Illuminate\Support\Facades\Auth;

class OnboardingWizard extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-user-plus';
    protected static string $view = 'filament.pages.onboarding-wizard';
    protected static bool $shouldRegisterNavigation = false; // 내비게이션 숨김

    public ?array $data = [];

    public function mount(): void
    {
        // 이미 소속이 있는 사용자는 대시보드로 리디렉션
        if ($this->hasAnyTenant()) {
            $this->redirect(route('filament.store.pages.dashboard'));
        }

        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Step::make('유형 선택')
                        ->description('조직 또는 매장 중 하나를 선택하세요')
                        ->icon('heroicon-o-building-office')
                        ->schema([
                            Select::make('entity_type')
                                ->label('생성할 유형')
                                ->options([
                                    'organization' => '조직',
                                    'store' => '매장',
                                ])
                                ->required()
                                ->helperText('조직은 여러 매장을 관리할 수 있습니다. 매장은 독립적으로 운영됩니다.')
                                ->live(),
                        ]),

                    Step::make('기본 정보')
                        ->description('필수 정보를 입력하세요')
                        ->icon('heroicon-o-pencil-square')
                        ->schema([
                            TextInput::make('name')
                                ->label('이름')
                                ->required()
                                ->maxLength(255)
                                ->helperText('조직 또는 매장의 공식 명칭을 입력하세요'),

                            Textarea::make('description')
                                ->label('설명')
                                ->maxLength(1000)
                                ->rows(3)
                                ->helperText('선택 사항입니다'),
                        ]),

                    Step::make('연락처 정보')
                        ->description('연락처를 입력하세요 (선택)')
                        ->icon('heroicon-o-phone')
                        ->schema(function (callable $get) {
                            $entityType = $get('entity_type');

                            if ($entityType === 'organization') {
                                return [
                                    TextInput::make('contact_email')
                                        ->label('대표 이메일')
                                        ->email()
                                        ->maxLength(255),

                                    TextInput::make('contact_phone')
                                        ->label('대표 전화번호')
                                        ->tel()
                                        ->maxLength(20),
                                ];
                            }

                            return [
                                TextInput::make('phone')
                                    ->label('매장 전화번호')
                                    ->tel()
                                    ->maxLength(20),

                                TextInput::make('address')
                                    ->label('매장 주소')
                                    ->maxLength(500),
                            ];
                        }),
                ])
                    ->submitAction(view('filament.pages.onboarding-submit-button'))
                    ->skippable(false), // 건너뛰기 불가
            ])
            ->statePath('data');
    }

    protected function hasAnyTenant(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        $panel = filament()->getCurrentPanel();

        return $user->getTenants($panel)->isNotEmpty();
    }

    public function submit(): void
    {
        $data = $this->form->getState();
        $user = Auth::user();

        try {
            $service = app(OnboardingService::class);

            if ($data['entity_type'] === 'organization') {
                $organization = $service->createOrganization($user, $data);

                Notification::make()
                    ->success()
                    ->title('조직 생성 완료')
                    ->body("{$organization->name} 조직이 생성되었습니다.")
                    ->send();

                // Organization 패널로 리디렉션
                $this->redirect(route('filament.organization.pages.dashboard', [
                    'tenant' => $organization->id,
                ]));
            } else {
                $store = $service->createStore($user, $data);

                Notification::make()
                    ->success()
                    ->title('매장 생성 완료')
                    ->body("{$store->name} 매장이 생성되었습니다.")
                    ->send();

                // Store 패널로 리디렉션 (기본 패널)
                $this->redirect(route('filament.store.pages.dashboard', [
                    'tenant' => $store->id,
                ]));
            }
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('생성 실패')
                ->body('오류가 발생했습니다: ' . $e->getMessage())
                ->send();

            // 로그 기록
            \Log::error('Onboarding failed', [
                'user_id' => $user->id,
                'data' => $data,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

#### 2.3. Blade 뷰 생성

resources/views/filament/pages/onboarding-wizard.blade.php:

<x-filament-panels::page>
    <div class="max-w-4xl mx-auto">
        <div class="mb-6 text-center">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                환영합니다!
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                시작하려면 조직 또는 매장을 생성하세요.
            </p>
        </div>

        <x-filament-panels::form wire:submit="submit">
            {{ $this->form }}
        </x-filament-panels::form>
    </div>
</x-filament-panels::page>

resources/views/filament/pages/onboarding-submit-button.blade.php:

<x-filament::button
    type="submit"
    size="lg"
>
    생성하기
</x-filament::button>

### 3단계: EnsureUserHasTenant 미들웨어 생성

#### 3.1. Artisan 명령

php artisan make:middleware EnsureUserHasTenant

#### 3.2. 미들웨어 코드

app/Http/Middleware/EnsureUserHasTenant.php:

<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasTenant
{
    /**
     * 사용자가 테넌트를 보유하고 있는지 확인
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user) {
            // 인증되지 않은 사용자는 로그인 페이지로
            return redirect()->route('filament.auth.login');
        }

        $panel = Filament::getCurrentPanel();

        // Platform/System 패널은 테넌트 불필요
        if (in_array($panel->getId(), ['platform', 'system'], true)) {
            return $next($request);
        }

        // 이미 온보딩 페이지에 있는 경우 통과
        if ($request->routeIs('filament.pages.onboarding-wizard')) {
            return $next($request);
        }

        // 테넌트 확인
        $tenants = $user->getTenants($panel);

        if ($tenants->isEmpty()) {
            // 온보딩 위자드로 리디렉션
            return redirect()->route('filament.pages.onboarding-wizard');
        }

        return $next($request);
    }
}

#### 3.3. 미들웨어 등록

bootstrap/app.php에 별칭 추가 (Laravel 12 방식):

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'ensure.tenant' => \App\Http\Middleware\EnsureUserHasTenant::class,
        ]);
    })
    ->create();

### 4단계: Panel에 미들웨어 적용

#### 4.1. StorePanelProvider 수정

app/Providers/Filament/StorePanelProvider.php:

use App\Http\Middleware\EnsureUserHasTenant;

public function panel(Panel $panel): Panel
{
    $scopeType = ScopeType::STORE;

    $panel = $this->applyCommonConfiguration($panel, $scopeType);

    return $panel
        ->default()
        ->tenant(Store::class)
        ->middleware([
            // ... 기존 미들웨어
            EnsureUserHasTenant::class,
        ])
        // ... 나머지 설정
}

#### 4.2. OrganizationPanelProvider 수정

app/Providers/Filament/OrganizationPanelProvider.php:

동일하게 EnsureUserHasTenant::class 추가

### 5단계: 라우트 등록

#### 5.1. OnboardingWizard를 패널 외부 페이지로 등록

app/Providers/Filament/StorePanelProvider.php:

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->pages([
            \App\Filament\Pages\OnboardingWizard::class,
        ])
        ->discoverPages(
            in: app_path('Filament/Store/Pages'),
            for: "App\Filament\Store\Pages",
        );
}

#### 5.2. 라우트 이름 확인

php artisan route:list --name=onboarding

예상 출력:

GET /onboarding ... filament.pages.onboarding-wizard

### 6단계: 테스트 작성

#### 6.1. Feature Test 생성

php artisan make:test --pest Feature/Onboarding/OnboardingWizardTest

#### 6.2. 테스트 코드

tests/Feature/Onboarding/OnboardingWizardTest.php:

<?php

use App\Models\User;
use App\Models\Organization;
use App\Models\Store;
use Livewire\Livewire;
use App\Filament\Pages\OnboardingWizard;

test('소속 없는 사용자는 온보딩 위자드로 리디렉션됨', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/store')
        ->assertRedirect('/onboarding');
});

test('조직 생성 시 Owner Role 부여됨', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(OnboardingWizard::class)
        ->fillForm([
            'entity_type' => 'organization',
            'name' => 'Test Organization',
        ])
        ->call('submit');

    expect(Organization::where('name', 'Test Organization')->exists())->toBeTrue();

    $organization = Organization::where('name', 'Test Organization')->first();

    expect($user->hasRole('owner'))->toBeTrue();
    expect($user->canAccessTenant($organization))->toBeTrue();
});

test('매장 생성 시 Owner Role 부여됨', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(OnboardingWizard::class)
        ->fillForm([
            'entity_type' => 'store',
            'name' => 'Test Store',
        ])
        ->call('submit');

    expect(Store::where('name', 'Test Store')->exists())->toBeTrue();

    $store = Store::where('name', 'Test Store')->first();

    expect($user->hasRole('owner'))->toBeTrue();
    expect($user->canAccessTenant($store))->toBeTrue();
});

test('이미 소속이 있는 사용자는 온보딩 건너뜀', function () {
    $user = User::factory()->create();
    $store = Store::factory()->create();

    // Owner Role 부여
    $role = \App\Models\Role::create([
        'name' => 'owner',
        'scope_type' => 'STORE',
        'scope_ref_id' => $store->id,
        'guard_name' => 'web',
    ]);
    $user->assignRole($role);

    Livewire::actingAs($user)
        ->test(OnboardingWizard::class)
        ->assertRedirect(route('filament.store.pages.dashboard'));
});

#### 6.3. 테스트 실행

php artisan test --filter=OnboardingWizard

### 7단계: 코드 스타일 및 정적 분석

#### 7.1. Pint 실행

vendor/bin/pint app/Services/OnboardingService.php
vendor/bin/pint app/Filament/Pages/OnboardingWizard.php
vendor/bin/pint app/Http/Middleware/EnsureUserHasTenant.php

#### 7.2. Larastan 실행

php -d memory_limit=-1 vendor/bin/phpstan analyse

## 검증 체크리스트

### 기능 테스트

- [ ] 소속 없는 사용자 로그인 → 온보딩 페이지 표시
- [ ] 조직 선택 → 이름 입력 → 생성 → Organization 대시보드
- [ ] 매장 선택 → 이름 입력 → 생성 → Store 대시보드
- [ ] 이미 소속이 있는 사용자 → 온보딩 건너뜀
- [ ] 필수 필드 미입력 시 검증 오류 표시
- [ ] 생성 실패 시 트랜잭션 롤백

### UI/UX 테스트

- [ ] Wizard 단계 표시 정상
- [ ] Step 아이콘 표시
- [ ] 뒤로가기/다음 버튼 동작
- [ ] 제출 버튼 마지막 단계에만 표시
- [ ] 알림(Notification) 정상 표시
- [ ] 다크 모드 지원

### 보안 테스트

- [ ] CSRF 토큰 검증
- [ ] 인증되지 않은 사용자 접근 차단
- [ ] 다른 사용자의 테넌트 접근 불가
- [ ] SQL Injection 방지 (Eloquent 사용)
- [ ] XSS 방지 (Blade 이스케이핑)

### 성능 테스트

- [ ] 페이지 로드 시간 < 500ms
- [ ] 엔티티 생성 시간 < 1000ms
- [ ] N+1 쿼리 없음 (Telescope 확인)
- [ ] DB 인덱스 활용 확인

## 문제 해결

### 문제: 온보딩 페이지가 무한 루프

**원인**: EnsureUserHasTenant가 온보딩 페이지에도 적용됨

**해결**:

미들웨어에서 온보딩 라우트 제외:

if ($request->routeIs('filament.pages.onboarding-wizard')) {
    return $next($request);
}

### 문제: Role이 중복 생성됨

**원인**: firstOrCreate() 조건 불일치

**해결**:

Role::firstOrCreate()의 첫 번째 인자에 모든 unique 컬럼 포함:

Role::firstOrCreate([
    'scope_type' => $scopeType->value,
    'scope_ref_id' => $scopeRefId,
    'name' => 'owner',
    'guard_name' => 'web',
]);

### 문제: 리디렉션 후 404 오류

**원인**: 패널 라우트 이름 불일치

**해결**:

라우트 이름 확인:

php artisan route:list | grep dashboard

올바른 라우트 이름 사용:

redirect()->route('filament.store.pages.dashboard', [
    'tenant' => $store->id,
]);

## 다음 단계

1. **추가 정보 수집**: 로고 업로드, 주소 상세 입력 등
2. **이메일 인증**: 조직/매장 생성 시 인증 메일 발송
3. **초대 기능**: 기존 조직에 구성원 초대
4. **템플릿 선택**: 업종별 매장 템플릿 제공
5. **가이드 투어**: 대시보드 기능 안내

## 참조 문서

- 기술 연구: `/opt/GitHub/olulo-mx-admin/docs/research/user-onboarding-wizard.md`
- 아키텍처: `/opt/GitHub/olulo-mx-admin/docs/architecture/onboarding-flow.md`
- Filament Wizards: https://filamentphp.com/docs/4.x/schemas/wizards
- Laravel Middleware: https://laravel.com/docs/12.x/middleware

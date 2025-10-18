# 빠른 시작: 사용자 온보딩 위자드 구현

**기능**: 사용자 온보딩 위자드
**브랜치**: `001-user-onboarding-wizard`
**예상 소요 시간**: 4-6 시간

## 사전 요구사항

- PHP 8.4.13 설치
- Laravel 12 프로젝트 설정 완료
- Filament v4 설치 완료
- Spatie Permission 설치 완료
- PostgreSQL 데이터베이스 연결 설정

## 구현 단계

### 1. Enum 생성 (선택적)

**명령어**:
```bash
php artisan make:enum ScopeType
```

**파일**: `app/Enums/ScopeType.php`
```php
<?php

namespace App\Enums;

enum ScopeType: string
{
    case ORGANIZATION = 'ORG';
    case STORE = 'STORE';
}
```

### 2. 마이그레이션 생성 (roles 테이블에 scope 컬럼 추가)

**명령어**:
```bash
php artisan make:migration add_scope_to_roles_table
```

**파일**: `database/migrations/YYYY_MM_DD_add_scope_to_roles_table.php`
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->string('scope_type')->nullable()->after('guard_name');
            $table->unsignedBigInteger('scope_ref_id')->nullable()->after('scope_type');

            $table->index(['scope_type', 'scope_ref_id']);
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropIndex(['scope_type', 'scope_ref_id']);
            $table->dropColumn(['scope_type', 'scope_ref_id']);
        });
    }
};
```

**실행**:
```bash
php artisan migrate
```

### 3. OnboardingService 생성

**명령어**:
```bash
php artisan make:class Services/OnboardingService
```

**파일**: `app/Services/OnboardingService.php`
```php
<?php

namespace App\Services;

use App\Enums\ScopeType;
use App\Models\Organization;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class OnboardingService
{
    /**
     * 조직 생성 및 Owner Role 부여
     */
    public function createOrganization(User $user, array $data): Organization
    {
        return DB::transaction(function () use ($user, $data) {
            $organization = Organization::create([
                'name' => $data['name'],
            ]);

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

    /**
     * 매장 생성 및 Owner Role 부여
     */
    public function createStore(User $user, array $data): Store
    {
        return DB::transaction(function () use ($user, $data) {
            $store = Store::create([
                'name' => $data['name'],
                'organization_id' => null, // 독립 매장
                'status' => 'pending',
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
}
```

### 4. User 모델에 getTenants() 메서드 추가

**파일**: `app/Models/User.php`
```php
use App\Enums\ScopeType;
use Illuminate\Support\Collection;

/**
 * 사용자가 접근 가능한 테넌트 목록 반환
 */
public function getTenants(?\Filament\Panel $panel = null): Collection
{
    return $this->roles()
        ->where(function ($query) {
            $query->where('scope_type', ScopeType::ORGANIZATION->value)
                  ->orWhere('scope_type', ScopeType::STORE->value);
        })
        ->get()
        ->map(function ($role) {
            return $role->scope_type === ScopeType::ORGANIZATION->value
                ? Organization::find($role->scope_ref_id)
                : Store::find($role->scope_ref_id);
        })
        ->filter();
}
```

### 5. EnsureUserHasTenant 미들웨어 생성

**명령어**:
```bash
php artisan make:middleware EnsureUserHasTenant
```

**파일**: `app/Http/Middleware/EnsureUserHasTenant.php`
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Filament\Facades\Filament;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasTenant
{
    /**
     * 사용자가 테넌트를 가지고 있는지 확인
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        $panel = Filament::getCurrentPanel();

        // Platform/System 패널은 테넌트 불필요
        if (in_array($panel?->getId(), ['platform', 'system'])) {
            return $next($request);
        }

        // 온보딩 페이지 제외
        if ($request->routeIs('filament.*.pages.onboarding-wizard')) {
            return $next($request);
        }

        // 테넌트 없으면 온보딩으로 리디렉션
        if ($user && $user->getTenants($panel)->isEmpty()) {
            return redirect()->route('filament.app.pages.onboarding-wizard');
        }

        return $next($request);
    }
}
```

### 6. OnboardingWizard 페이지 생성

**명령어**:
```bash
php artisan make:filament-page OnboardingWizard
```

**파일**: `app/Filament/Pages/OnboardingWizard.php`
```php
<?php

namespace App\Filament\Pages;

use App\Enums\ScopeType;
use App\Services\OnboardingService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Schemas\Components\Wizard;
use Illuminate\Support\Facades\Auth;

class OnboardingWizard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-user-plus';

    protected static string $view = 'filament.pages.onboarding-wizard';

    protected static bool $shouldRegisterNavigation = false;

    public ?string $entityType = null;
    public ?string $name = null;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Wizard\Step::make('유형 선택')
                        ->schema([
                            Forms\Components\Radio::make('entityType')
                                ->label('생성할 유형을 선택하세요')
                                ->options([
                                    'organization' => '조직',
                                    'store' => '매장',
                                ])
                                ->required()
                                ->live(),
                        ]),

                    Wizard\Step::make('정보 입력')
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->label(fn () => $this->entityType === 'organization' ? '조직 이름' : '매장 이름')
                                ->required()
                                ->maxLength(255)
                                ->unique(
                                    table: fn () => $this->entityType === 'organization' ? 'organizations' : 'stores',
                                    column: 'name'
                                ),
                        ]),
                ])
                ->submitAction(new \Filament\Actions\Action('submit', '완료'))
                ->onSubmit(function (OnboardingService $service) {
                    $user = Auth::user();

                    $entity = $this->entityType === 'organization'
                        ? $service->createOrganization($user, ['name' => $this->name])
                        : $service->createStore($user, ['name' => $this->name]);

                    $this->redirect(route('filament.app.pages.dashboard'));
                }),
            ]);
    }
}
```

**View 파일**: `resources/views/filament/pages/onboarding-wizard.blade.php`
```blade
<x-filament-panels::page>
    <x-filament-panels::form wire:submit="submit">
        {{ $this->form }}
    </x-filament-panels::form>
</x-filament-panels::page>
```

### 7. Panel에 미들웨어 등록

**파일**: `app/Providers/Filament/AppPanelProvider.php`
```php
use App\Http\Middleware\EnsureUserHasTenant;

public function panel(Panel $panel): Panel
{
    return $panel
        ->id('app')
        // ... 기존 설정
        ->middleware([
            // ... 기존 미들웨어
            EnsureUserHasTenant::class,
        ]);
}
```

### 8. Feature Test 작성

**명령어**:
```bash
php artisan make:test --pest OnboardingWizardTest
```

**파일**: `tests/Feature/OnboardingWizardTest.php`
```php
<?php

use App\Models\User;
use Filament\Livewire\Pages\OnboardingWizard;
use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

it('redirects to onboarding wizard when user has no tenant', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->get(route('filament.app.pages.dashboard'))
        ->assertRedirect(route('filament.app.pages.onboarding-wizard'));
});

it('can create organization via wizard', function () {
    $user = User::factory()->create();

    livewire(OnboardingWizard::class)
        ->set('entityType', 'organization')
        ->set('name', 'Test Organization')
        ->call('submit')
        ->assertRedirect(route('filament.app.pages.dashboard'));

    expect($user->refresh()->getTenants())->toHaveCount(1);
    expect($user->hasRole('owner'))->toBeTrue();
});

it('can create store via wizard', function () {
    $user = User::factory()->create();

    livewire(OnboardingWizard::class)
        ->set('entityType', 'store')
        ->set('name', 'Test Store')
        ->call('submit')
        ->assertRedirect(route('filament.app.pages.dashboard'));

    expect($user->refresh()->getTenants())->toHaveCount(1);
    expect($user->hasRole('owner'))->toBeTrue();
});
```

### 9. 코드 품질 검증

**명령어**:
```bash
# 모든 품질 도구 실행
composer quality:check

# 또는 개별 실행
composer rector:check
composer pint:check
composer phpstan

# 자동 수정
composer quality:fix
```

### 10. 테스트 실행

**명령어**:
```bash
php artisan test --filter=OnboardingWizard
```

## 검증 체크리스트

- [ ] 마이그레이션 실행 완료
- [ ] OnboardingService 생성 및 메서드 구현
- [ ] User::getTenants() 메서드 추가
- [ ] EnsureUserHasTenant 미들웨어 생성 및 등록
- [ ] OnboardingWizard 페이지 생성 및 View 파일 작성
- [ ] Feature Tests 작성 및 통과
- [ ] Pint 스타일 검사 통과
- [ ] PHPStan 정적 분석 통과
- [ ] 소속 없는 사용자 로그인 시 온보딩 위자드 자동 표시 확인
- [ ] 조직 생성 후 대시보드 리디렉션 확인
- [ ] 매장 생성 후 대시보드 리디렉션 확인

## 문제 해결

### 1. Wizard 컴포넌트를 찾을 수 없음

**증상**: `Class 'Filament\Schemas\Components\Wizard' not found`

**해결**:
```bash
composer update filament/filament
```

### 2. getTenants() 메서드가 빈 컬렉션 반환

**원인**: Role에 scope_type, scope_ref_id 컬럼 없음

**해결**: 마이그레이션 실행 확인
```bash
php artisan migrate:status
```

### 3. 온보딩 위자드로 리디렉션되지 않음

**원인**: 미들웨어 등록 누락

**해결**: `AppPanelProvider.php`에서 미들웨어 등록 확인

## 다음 단계

1. `/speckit.tasks` 실행하여 `tasks.md` 생성
2. tasks.md 기준으로 구현 진행
3. GitHub Issue 생성
4. Draft PR 생성
5. 코드 리뷰 후 머지

## 참고 자료

- [Filament V4 Wizard 문서](https://filamentphp.com/docs/4.x/forms/layout/wizard)
- [Spatie Permission 문서](https://spatie.be/docs/laravel-permission/v6/introduction)
- [Pest 테스트 문서](https://pestphp.com/docs/writing-tests)

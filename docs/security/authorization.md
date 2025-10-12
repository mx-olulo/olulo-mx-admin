# Authorization (권한 관리)

## 개요

Olulo MX는 3-layer 권한 체계를 사용하여 멀티테넌시 환경에서 안전하고 세밀한 권한 관리를 구현합니다. 각 레이어는 명확한 책임을 가지며, SOLID 원칙의 Single Responsibility를 준수합니다.

## 3-Layer 권한 체계

```
┌─────────────────────────────────────────────┐
│  Layer 1: Gate::before                      │
│  (글로벌 권한 - PLATFORM/SYSTEM)            │
└─────────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────┐
│  Layer 2: Spatie Permission                 │
│  (세밀한 권한 체크)                         │
└─────────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────┐
│  Layer 3: Filament Tenant + Policy          │
│  (리소스 소유권 체크)                       │
└─────────────────────────────────────────────┘
```

### Layer 1: Gate::before (글로벌 권한)

**목적**: PLATFORM/SYSTEM 스코프 사용자에게 모든 권한 자동 부여

**구현 위치**: `app/Providers/AppServiceProvider.php`

```php
Gate::before(function (User $user, string $ability) {
    // 관리자 패널(Filament/Nova)에서만 글로벌 스코프 체크
    // 고객 앱 요청은 이 체크를 건너뛰어 성능 최적화
    if ($this->isAdminPanel()) {
        // PLATFORM/SYSTEM 스코프 사용자는 모든 권한 자동 허용
        if ($user->hasGlobalScopeRole()) {
            return true;
        }
    }

    return null; // 다음 레이어로 전달
});
```

**특징**:
- 모든 Gate 체크보다 먼저 실행
- `true` 반환 시 즉시 허용, `null` 반환 시 다음 레이어로 전달
- PLATFORM/SYSTEM 스코프의 슈퍼 유저 역할 구현
- **성능 최적화**: 관리자 패널에서만 작동, 고객 앱에서는 DB 쿼리 건너뜀

**적용 대상**:
- Platform 관리자 (전체 시스템 관리) - Filament/Nova 패널에서만
- System 관리자 (시스템 설정 관리) - Filament/Nova 패널에서만

**성능 고려사항**:
- 고객 앱(PWA)에서는 `isAdminPanel()` 체크가 `false`를 반환하여 `hasGlobalScopeRole()` DB 쿼리를 건너뜀
- 관리자 패널에서만 필요한 체크이므로 프론트엔드 성능에 영향 없음

### Layer 2: Spatie Permission (세밀한 권한 체크)

**목적**: 세밀한 권한 관리 (view, create, update, delete 등)

**구현**: Spatie Laravel Permission 패키지

**권한 목록**:

| 권한 | 설명 | 허용 스코프 |
|------|------|------------|
| view-organizations | Organization 조회 | PLATFORM, SYSTEM, ORGANIZATION |
| create-organizations | Organization 생성 | PLATFORM, SYSTEM |
| update-organizations | Organization 수정 | PLATFORM, SYSTEM, ORGANIZATION |
| delete-organizations | Organization 삭제 | PLATFORM, SYSTEM |
| restore-organizations | Organization 복원 | PLATFORM, SYSTEM |
| force-delete-organizations | Organization 영구 삭제 | PLATFORM, SYSTEM |
| view-activities | Activity Log 조회 | PLATFORM, SYSTEM, ORGANIZATION |

**사용 예시**:

```php
// 컨트롤러에서
if (! $user->can('view-organizations')) {
    abort(403);
}

// Policy에서
public function view(User $user, Organization $organization): bool
{
    if (! $user->can('view-organizations')) {
        return false;
    }

    // 소유권 체크는 Layer 3에서 수행
    return $this->canAccessOrganization($user, $organization);
}
```

**권한 할당**:

권한은 `database/seeders/PermissionSeeder.php`에서 Role에 할당됩니다:

```php
// PLATFORM/SYSTEM: 모든 권한
$this->assignPermissionsToScope(ScopeType::PLATFORM, $permissions);
$this->assignPermissionsToScope(ScopeType::SYSTEM, $permissions);

// ORGANIZATION: 제한된 권한
$this->assignPermissionsToScope(ScopeType::ORGANIZATION, [
    'view-organizations',
    'update-organizations',
    'view-activities',
]);
```

### Layer 3: Filament Tenant + Policy (리소스 소유권)

**목적**: 특정 리소스에 대한 소유권 확인

**구현 위치**: `app/Policies/OrganizationPolicy.php`

**핵심 메서드**: `canAccessOrganization()`

```php
protected function canAccessOrganization(User $user, Organization $organization): bool
{
    // 1. PLATFORM/SYSTEM 스코프는 모든 Organization 접근 가능
    //    Gate::before 로직을 Policy에도 적용
    if ($user->hasGlobalScopeRole()) {
        return true;
    }

    // 2. Filament 테넌트(Role) 기반 소유권 체크
    $tenant = Filament::getTenant();

    if (! $tenant instanceof \App\Models\Role) {
        return false; // Filament UI 외부 환경은 거부
    }

    // 3. ORGANIZATION 스코프는 자신의 Organization만 접근
    if ($tenant->scope_type === ScopeType::ORGANIZATION->value) {
        return $tenant->scope_ref_id === $organization->id;
    }

    return false;
}
```

**소유권 체크 로직**:

| 스코프 | 접근 가능 Organization |
|--------|----------------------|
| PLATFORM | 모든 Organization |
| SYSTEM | 모든 Organization |
| ORGANIZATION | 자신의 Organization만 |
| BRAND | Organization 직접 접근 불가 |
| STORE | Organization 직접 접근 불가 |

## 멀티테넌시와 권한

### Filament Tenant (Role)

Filament의 Tenant 시스템을 Spatie Permission의 Role로 구현했습니다.

**Role 구조**:

```php
class Role extends SpatieRole
{
    // team_id: Spatie Permission의 팀 ID (테넌트 격리)
    // scope_type: 스코프 타입 (PLATFORM, SYSTEM, ORGANIZATION 등)
    // scope_ref_id: 스코프 대상 ID (Organization ID, Brand ID 등)
}
```

**Tenant 전환**:

```php
// 사용자가 접근 가능한 테넌트(Role) 목록
public function getTenants(Panel $panel): Collection
{
    $scopeType = ScopeType::fromPanelId($panel->getId());

    return $this->roles()
        ->whereNotNull('team_id')
        ->where('scope_type', $scopeType->value)
        ->get();
}
```

### team_id 컨텍스트

Spatie Permission은 `team_id`를 사용하여 권한을 격리합니다.

**설정 위치**: `app/Http/Middleware/SetSpatieTeamId.php`

```php
public function handle(Request $request, Closure $next): Response
{
    // Filament 테넌트(Role)의 team_id 설정
    $tenant = Filament::getTenant();

    if ($tenant instanceof \App\Models\Role && $tenant->team_id) {
        setPermissionsTeamId($tenant->team_id);
    }

    return $next($request);
}
```

## Filament Resource 권한 통합

Filament Resource는 Policy 메서드를 직접 위임하여 권한을 체크합니다.

**구현**: `app/Filament/Organization/Resources/Organizations/OrganizationResource.php`

```php
public static function canView(Model $record): bool
{
    $user = auth()->user();

    // Policy의 view() 메서드로 위임
    // 권한 + 소유권 체크 통합
    return $user !== null && $user->can('view', $record);
}

public static function canEdit(Model $record): bool
{
    $user = auth()->user();

    // Policy의 update() 메서드로 위임
    return $user !== null && $user->can('update', $record);
}
```

**주의사항**:
- ❌ `$user->can('view-organizations')`: 권한만 체크 (소유권 누락)
- ✅ `$user->can('view', $record)`: Policy 위임 (권한 + 소유권)

## 성능 최적화

### hasGlobalScopeRole() 메서드

PLATFORM/SYSTEM 스코프 확인을 위한 최적화된 메서드입니다.

**구현**: `app/Models/User.php`

```php
public function hasGlobalScopeRole(): bool
{
    // Eloquent relation 캐싱 활용
    // roles가 이미 로드되었으면 쿼리 없이 메모리에서 확인
    return $this->roles()
        ->whereIn('scope_type', [
            ScopeType::PLATFORM->value,
            ScopeType::SYSTEM->value,
        ])
        ->exists();
}
```

**최적화 전략**:
- Eloquent의 relation 캐싱 활용
- 테스트 환경 안정성 확보 (once() 헬퍼 제거)

### Eager Loading

N+1 쿼리 방지를 위해 관련 Role을 사전 로드합니다:

```php
// 사용자와 Role 함께 조회
$user = User::with('roles')->find($id);

// 이후 hasGlobalScopeRole() 호출 시 추가 쿼리 없음
if ($user->hasGlobalScopeRole()) {
    // ...
}
```

## 테스트

### OrganizationPolicyTest

**파일**: `tests/Feature/OrganizationPolicyTest.php`

**커버리지**:
- PLATFORM 스코프의 모든 권한 자동 허용
- SYSTEM 스코프의 모든 권한 자동 허용
- ORGANIZATION 스코프의 제한된 권한
- 소유권 체크 (자신의 Organization만 접근)
- 타 Organization 접근 거부

**실행**:
```bash
php artisan test --filter=OrganizationPolicyTest
```

## 권한 체크 흐름도

### 예시: Organization 조회

```
사용자가 Organization 조회 요청
          ↓
┌─────────────────────────────┐
│ Filament Resource           │
│ canView($record)            │
└─────────────────────────────┘
          ↓
┌─────────────────────────────┐
│ Gate: $user->can('view',    │
│           $record)          │
└─────────────────────────────┘
          ↓
┌─────────────────────────────┐
│ Layer 1: Gate::before       │
│ hasGlobalScopeRole()?       │
└─────────────────────────────┘
      true ↓     ↓ null
       허용      계속
                ↓
┌─────────────────────────────┐
│ Layer 2: Spatie Permission  │
│ can('view-organizations')?  │
└─────────────────────────────┘
      false ↓    ↓ true
       거부      계속
                ↓
┌─────────────────────────────┐
│ Layer 3: Policy             │
│ canAccessOrganization()?    │
└─────────────────────────────┘
      false ↓    ↓ true
       거부      허용
```

## 권한 추가 가이드

### 1. Permission 정의

`database/seeders/PermissionSeeder.php`에 권한 추가:

```php
$permissions = [
    'view-brands',
    'create-brands',
    'update-brands',
    'delete-brands',
];
```

### 2. Policy 생성

```bash
php artisan make:policy BrandPolicy --model=Brand
```

### 3. Policy 구현

```php
class BrandPolicy
{
    public function view(User $user, Brand $brand): bool
    {
        // Layer 2: 권한 체크
        if (! $user->can('view-brands')) {
            return false;
        }

        // Layer 3: 소유권 체크
        return $this->canAccessBrand($user, $brand);
    }

    protected function canAccessBrand(User $user, Brand $brand): bool
    {
        // 1. 글로벌 스코프 자동 허용
        if ($user->hasGlobalScopeRole()) {
            return true;
        }

        // 2. BRAND 스코프는 자신의 Brand만
        $tenant = Filament::getTenant();
        if ($tenant->scope_type === ScopeType::BRAND->value) {
            return $tenant->scope_ref_id === $brand->id;
        }

        return false;
    }
}
```

### 4. Filament Resource 통합

```php
public static function canView(Model $record): bool
{
    $user = auth()->user();
    return $user !== null && $user->can('view', $record);
}
```

### 5. 테스트 작성

```php
test('BRAND 스코프는 자신의 Brand만 조회 가능', function () {
    $brand = Brand::factory()->create();
    $role = Role::create([
        'name' => 'brand-admin',
        'scope_type' => ScopeType::BRAND->value,
        'scope_ref_id' => $brand->id,
        'team_id' => $brand->id,
    ]);

    $user = User::factory()->create();
    $user->assignRole($role);

    expect($user->can('view', $brand))->toBeTrue();
});
```

## 보안 고려사항

### 1. 항상 Policy 위임

❌ **잘못된 예시**:
```php
public static function canView(Model $record): bool
{
    return auth()->user()?->can('view-organizations') ?? false;
    // 소유권 체크 누락 - 보안 취약점
}
```

✅ **올바른 예시**:
```php
public static function canView(Model $record): bool
{
    $user = auth()->user();
    return $user !== null && $user->can('view', $record);
    // Policy 위임 - 권한 + 소유권 통합
}
```

### 2. Gate::before와 Policy 일관성

Policy의 `canAccessOrganization()` 메서드는 `Gate::before` 로직을 복제해야 합니다.

**이유**: API, 콘솔, 테스트 등 Filament 컨텍스트가 없는 환경 대응

```php
protected function canAccessOrganization(User $user, Organization $organization): bool
{
    // Gate::before 로직 복제
    if ($user->hasGlobalScopeRole()) {
        return true;
    }

    // Filament 테넌트 체크
    $tenant = Filament::getTenant();
    // ...
}
```

### 3. 테넌트 격리 검증

각 테넌트는 자신의 리소스만 접근 가능해야 합니다:

```php
test('Organization A는 Organization B를 조회할 수 없음', function () {
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();

    $user = User::factory()->create();
    $user->assignRole($orgA->admin_role);

    expect($user->can('view', $orgA))->toBeTrue();
    expect($user->can('view', $orgB))->toBeFalse(); // ✅ 격리 확인
});
```

## 참조

- [Spatie Laravel Permission 공식 문서](https://spatie.be/docs/laravel-permission/)
- [Filament Tenancy 공식 문서](https://filamentphp.com/docs/panels/tenancy)
- `app/Providers/AppServiceProvider.php`: Gate::before 구현
- `app/Policies/OrganizationPolicy.php`: Policy 예시
- `app/Models/User.php`: hasGlobalScopeRole() 구현
- `docs/features/activity-log.md`: Activity Log 권한 체계

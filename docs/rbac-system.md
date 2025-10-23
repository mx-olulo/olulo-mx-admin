# RBAC 시스템 - TenantUser 기반 멀티테넌트 권한 관리

> **@CODE:RBAC-001 | SPEC: SPEC-RBAC-001.md**
>
> TenantUser 피벗 모델 기반 자체 RBAC 구현 (Spatie Permission 제거)

**최종 업데이트**: 2025-10-23
**상태**: ✅ 구현 완료

---

## 목차

1. [아키텍처 개요](#아키텍처-개요)
2. [데이터 모델](#데이터-모델)
3. [권한 확인 API](#권한-확인-api)
4. [Filament Tenancy 통합](#filament-tenancy-통합)
5. [사용 예시](#사용-예시)
6. [설계 원칙](#설계-원칙)

---

## 아키텍처 개요

### 핵심 설계 원칙

**3-Tier 사용자 타입 시스템**:
```
User Type (user_type Enum)
├── Admin: 멀티테넌트 접근 (tenant_users 피벗 기반)
├── User: 글로벌 패널 접근 (global_role 기반)
└── Customer: Firebase 인증만 (패널 접근 불가)
```

**3개 테넌트 타입 (Polymorphic)**:
```
Tenant Type (ScopeType Enum)
├── Organization (ORG): 최상위 조직
├── Brand (BRD): 중간 브랜드
└── Store (STR): 실제 점포
```

**3가지 역할 (TenantRole Enum)**:
```
Role (role 필드)
├── Owner: 모든 권한 (생성, 수정, 삭제, 조회)
├── Manager: 관리 권한 (생성, 수정, 조회)
└── Viewer: 읽기 전용 (조회만)
```

### Spatie Permission 제거 이유

**제거 전 문제점**:
- `team_id` 기반 접근이 단일 계층만 지원 (Organization/Brand/Store 동시 관리 불가)
- Polymorphic 관계를 지원하지 않음
- 복잡한 설정 (Role 모델 확장, 미들웨어 3개)

**제거 후 이점**:
- ✅ TenantUser 피벗 모델 하나로 Polymorphic M:N 관계 구현
- ✅ 코드 복잡도 감소 (User 모델 LOC 30% 감소)
- ✅ Enum 기반 타입 안전성
- ✅ Fluent API로 가독성 향상

---

## 데이터 모델

### TenantUser 피벗 모델

**테이블 구조**:
```sql
CREATE TABLE tenant_users (
    id BIGINT PRIMARY KEY,
    user_id BIGINT NOT NULL,           -- User FK
    tenant_type VARCHAR(20) NOT NULL,  -- 'ORG'|'BRD'|'STR'
    tenant_id BIGINT NOT NULL,         -- Polymorphic ID
    role VARCHAR(20) NOT NULL,         -- 'owner'|'manager'|'viewer'
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_tenant (tenant_type, tenant_id),
    UNIQUE (user_id, tenant_type, tenant_id)
);
```

**Eloquent 모델**:
```php
// app/Models/TenantUser.php
class TenantUser extends Model
{
    protected $fillable = [
        'user_id',
        'tenant_type',
        'tenant_id',
        'role',
    ];

    // User 관계
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Polymorphic 관계: Organization, Brand, Store
    public function tenant(): MorphTo
    {
        return $this->morphTo('tenant', 'tenant_type', 'tenant_id');
    }
}
```

### User 모델

**핵심 필드**:
```php
class User extends Authenticatable implements FilamentUser, HasTenants
{
    use HasTenantPermissions; // 권한 확인 API

    protected $fillable = [
        'user_type',      // UserType Enum (admin|user|customer)
        'global_role',    // string (platform_admin|system_admin) - User 타입만
        'firebase_uid',   // Firebase 인증
        // ...
    ];

    protected function casts(): array
    {
        return [
            'user_type' => UserType::class,
        ];
    }
}
```

### Enum 정의

#### UserType Enum
```php
// app/Enums/UserType.php
enum UserType: string
{
    case ADMIN = 'admin';      // 멀티테넌트 접근
    case USER = 'user';        // 글로벌 패널 접근
    case CUSTOMER = 'customer'; // Firebase 인증만
}
```

#### TenantRole Enum
```php
// app/Enums/TenantRole.php
enum TenantRole: string
{
    case OWNER = 'owner';
    case MANAGER = 'manager';
    case VIEWER = 'viewer';

    public function canManage(): bool
    {
        return match ($this) {
            self::OWNER, self::MANAGER => true,
            self::VIEWER => false,
        };
    }

    public function canView(): bool
    {
        return true;
    }
}
```

#### ScopeType Enum
```php
// app/Enums/ScopeType.php
enum ScopeType: string
{
    case PLATFORM = 'PLATFORM';
    case SYSTEM = 'SYSTEM';
    case ORGANIZATION = 'ORG';
    case BRAND = 'BRD';
    case STORE = 'STR';

    // Polymorphic 매핑
    public static function getMorphMap(): array
    {
        return [
            'ORG' => \App\Models\Organization::class,
            'BRD' => \App\Models\Brand::class,
            'STR' => \App\Models\Store::class,
        ];
    }
}
```

**AppServiceProvider 등록**:
```php
public function boot(): void
{
    $morphMap = ScopeType::getMorphMap();
    Relation::morphMap($morphMap);
}
```

---

## 권한 확인 API

### HasTenantPermissions Trait

**위치**: `app/Models/Concerns/HasTenantPermissions.php`

#### 1. Fluent API - 메서드 체이닝

```php
/**
 * @CODE:RBAC-001 | SPEC: SPEC-RBAC-001.md
 *
 * 메서드 체이닝을 위한 테넌트 접근자 반환
 */
public function tenant(Model $model): TenantAccessor
{
    return new TenantAccessor($this, $model);
}
```

**사용 예시**:
```php
// 메서드 체이닝
$user->tenant($organization)->canManage();  // bool
$user->tenant($brand)->hasRole(TenantRole::OWNER);  // bool
$user->tenant($store)->canView();  // bool

// 역할 조회
$role = $user->tenant($organization)->role();  // TenantRole|null

// 역할별 확인
$user->tenant($store)->isOwner();    // bool
$user->tenant($store)->isManager();  // bool
$user->tenant($store)->isViewer();   // bool
```

#### 2. 직접 호출 메서드

```php
/**
 * 특정 테넌트에서 특정 역할 보유 여부 확인
 */
public function hasRoleForTenant(Model $model, TenantRole|string $role): bool
{
    $roleString = $role instanceof TenantRole ? $role->value : $role;
    return $this->getRoleForTenant($model) === $roleString;
}

/**
 * 테넌트 관리 권한 확인 (owner 또는 manager)
 */
public function canManageTenant(Model $model): bool
{
    $role = $this->getRoleForTenant($model);
    return in_array($role, ['owner', 'manager'], true);
}

/**
 * 테넌트 조회 권한 확인 (모든 역할)
 */
public function canViewTenant(Model $model): bool
{
    return $this->getRoleForTenant($model) !== null;
}

/**
 * 글로벌 역할 확인 (User 타입만)
 */
public function hasGlobalRole(string $role): bool
{
    if ($this->user_type !== UserType::USER) {
        return false;
    }
    return $this->global_role === $role;
}
```

### TenantAccessor 클래스

**위치**: `app/Models/Concerns/TenantAccessor.php`

메서드 체이닝을 위한 헬퍼 클래스:

```php
class TenantAccessor
{
    public function __construct(
        private readonly mixed $user,
        private readonly Model $model
    ) {}

    public function role(): ?TenantRole
    {
        $roleString = $this->user->getRoleForTenant($this->model);
        return $roleString ? TenantRole::tryFrom($roleString) : null;
    }

    public function hasRole(TenantRole $tenantRole): bool
    {
        return $this->role() === $tenantRole;
    }

    public function canManage(): bool
    {
        $role = $this->role();
        return $role instanceof TenantRole && $role->canManage();
    }

    public function canView(): bool
    {
        return $this->role() instanceof TenantRole;
    }

    public function isOwner(): bool
    {
        return $this->hasRole(TenantRole::OWNER);
    }

    public function isManager(): bool
    {
        return $this->hasRole(TenantRole::MANAGER);
    }

    public function isViewer(): bool
    {
        return $this->hasRole(TenantRole::VIEWER);
    }
}
```

### HasTenantRelations Trait

**위치**: `app/Models/Concerns/HasTenantRelations.php`

TenantUser 관계 관리:

```php
trait HasTenantRelations
{
    /**
     * TenantUser 관계 (1:N)
     */
    public function tenantUsers(): HasMany
    {
        return $this->hasMany(TenantUser::class);
    }

    /**
     * 특정 타입의 테넌트 조회
     */
    public function getTenantsByType(string $tenantType): Collection
    {
        return $this->tenantUsers()
            ->with('tenant')
            ->where('tenant_type', $tenantType)
            ->get()
            ->pluck('tenant');
    }

    /**
     * 특정 테넌트에서의 역할 조회
     */
    public function getRoleForTenant(Model $model): ?string
    {
        $tenantType = array_search(
            get_class($model),
            ScopeType::getMorphMap(),
            true
        );

        if (!$tenantType) {
            return null;
        }

        $tenantUser = $this->tenantUsers()
            ->where('tenant_type', $tenantType)
            ->where('tenant_id', $model->id)
            ->first();

        return $tenantUser?->role;
    }
}
```

---

## Filament Tenancy 통합

### User 모델 - HasTenants 구현

```php
class User extends Authenticatable implements FilamentUser, HasTenants
{
    /**
     * Filament 패널 접근 권한 확인
     *
     * @CODE:RBAC-001 | SPEC: SPEC-RBAC-001.md
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Customer는 모든 패널 차단
        if ($this->user_type === UserType::CUSTOMER) {
            return false;
        }

        $scopeType = ScopeType::fromPanelId($panel->getId());

        // Platform/System 패널: User 타입만 접근 (global_role 기반)
        if ($scopeType === ScopeType::PLATFORM) {
            return $this->user_type === UserType::USER
                && $this->hasGlobalRole('platform_admin');
        }

        if ($scopeType === ScopeType::SYSTEM) {
            return $this->user_type === UserType::USER
                && $this->hasGlobalRole('system_admin');
        }

        // Organization/Brand/Store 패널: Admin 타입만 접근
        if (in_array($scopeType, [
            ScopeType::ORGANIZATION,
            ScopeType::BRAND,
            ScopeType::STORE,
        ], true)) {
            if ($this->user_type !== UserType::ADMIN) {
                return false;
            }

            return $this->canAccessTenantPanel($panel->getId());
        }

        return false;
    }

    /**
     * Filament 테넌트 목록 조회
     */
    public function getTenants(Panel $panel): Collection
    {
        $scopeType = ScopeType::fromPanelId($panel->getId());

        // 글로벌 패널은 테넌트 없음
        if (in_array($scopeType, [ScopeType::PLATFORM, ScopeType::SYSTEM])) {
            return collect();
        }

        // 해당 패널 타입의 테넌트만 반환
        return $this->getTenantsByType($scopeType->value);
    }

    /**
     * 특정 테넌트 접근 권한 확인
     */
    public function canAccessTenant(Model $tenant): bool
    {
        return $this->canViewTenant($tenant);
    }
}
```

### Filament Panel 설정

```php
// app/Providers/Filament/OrganizationPanelProvider.php
public function panel(Panel $panel): Panel
{
    return $panel
        ->id('org')
        ->path('org')
        ->tenant(Organization::class)
        ->tenantRegistration(RegisterOrganization::class) // 온보딩 위자드
        ->login()
        ->colors([
            'primary' => Color::Amber,
        ])
        ->discoverResources(in: app_path('Filament/Org/Resources'), for: 'App\\Filament\\Org\\Resources')
        ->discoverPages(in: app_path('Filament/Org/Pages'), for: 'App\\Filament\\Org\\Pages')
        ->pages([
            Pages\Dashboard::class,
        ])
        ->middleware([
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            AuthenticateSession::class,
            ShareErrorsFromSession::class,
            VerifyCsrfToken::class,
            SubstituteBindings::class,
            DisableBladeIconComponents::class,
            DispatchServingFilamentEvent::class,
        ])
        ->authMiddleware([
            Authenticate::class,
        ]);
}
```

---

## 사용 예시

### 1. TenantUser 생성

```php
use App\Models\TenantUser;
use App\Enums\ScopeType;

// Organization Owner 할당
TenantUser::create([
    'user_id' => $user->id,
    'tenant_type' => ScopeType::ORGANIZATION->value,
    'tenant_id' => $organization->id,
    'role' => 'owner',
]);

// Store Manager 할당
TenantUser::create([
    'user_id' => $user->id,
    'tenant_type' => ScopeType::STORE->value,
    'tenant_id' => $store->id,
    'role' => 'manager',
]);
```

### 2. 권한 확인

```php
// Fluent API (메서드 체이닝)
if ($user->tenant($organization)->canManage()) {
    // Organization 관리 권한 있음
}

if ($user->tenant($store)->isOwner()) {
    // Store Owner
}

// 직접 호출
if ($user->canManageTenant($brand)) {
    // Brand 관리 권한 있음
}

if ($user->canViewTenant($organization)) {
    // Organization 조회 권한 있음
}

// 글로벌 역할 확인
if ($user->hasGlobalRole('platform_admin')) {
    // Platform Admin
}
```

### 3. 테넌트 목록 조회

```php
// Organization 목록
$organizations = $user->getTenantsByType(ScopeType::ORGANIZATION->value);

// Store 목록
$stores = $user->getTenantsByType(ScopeType::STORE->value);

// 모든 TenantUser 조회 (with tenant eager load)
$tenantUsers = $user->tenantUsers()->with('tenant')->get();
```

### 4. Filament Resource에서 사용

```php
use Filament\Facades\Filament;

class ProductResource extends Resource
{
    public static function getEloquentQuery(): Builder
    {
        $tenant = Filament::getTenant(); // Organization|Brand|Store

        return parent::getEloquentQuery()
            ->where('store_id', $tenant->id);
    }

    public static function canCreate(): bool
    {
        $tenant = Filament::getTenant();
        return auth()->user()->tenant($tenant)->canManage();
    }

    public static function canEdit(Model $record): bool
    {
        $tenant = Filament::getTenant();
        return auth()->user()->tenant($tenant)->canManage();
    }

    public static function canDelete(Model $record): bool
    {
        $tenant = Filament::getTenant();
        return auth()->user()->tenant($tenant)->isOwner();
    }
}
```

### 5. Controller에서 사용

```php
use App\Enums\TenantRole;

class OrganizationController extends Controller
{
    public function update(Request $request, Organization $organization)
    {
        // 권한 확인
        if (!auth()->user()->tenant($organization)->canManage()) {
            abort(403, 'Unauthorized');
        }

        // 비즈니스 로직
        $organization->update($request->validated());

        return redirect()->back();
    }

    public function destroy(Organization $organization)
    {
        // Owner만 삭제 가능
        if (!auth()->user()->tenant($organization)->isOwner()) {
            abort(403, 'Only owners can delete organizations');
        }

        $organization->delete();

        return redirect()->route('organizations.index');
    }
}
```

### 6. Policy에서 사용

```php
class OrganizationPolicy
{
    public function update(User $user, Organization $organization): bool
    {
        return $user->tenant($organization)->canManage();
    }

    public function delete(User $user, Organization $organization): bool
    {
        return $user->tenant($organization)->isOwner();
    }

    public function addMember(User $user, Organization $organization): bool
    {
        return $user->tenant($organization)->canManage();
    }
}
```

---

## 설계 원칙

### 1. Enum 중심 타입 안전성

**장점**:
- ✅ 컴파일 타임 타입 체크
- ✅ IDE 자동완성 지원
- ✅ 오타/불일치 방지

**적용**:
- `UserType`: admin|user|customer
- `TenantRole`: owner|manager|viewer
- `ScopeType`: PLATFORM|SYSTEM|ORG|BRD|STR

### 2. Polymorphic 관계

**장점**:
- ✅ 단일 피벗 테이블로 3개 테넌트 타입 관리
- ✅ 확장 용이 (새 테넌트 타입 추가 시 마이그레이션 불필요)
- ✅ 쿼리 효율성 (JOIN 최소화)

**구현**:
```php
// TenantUser::tenant() - morphTo 관계
$tenantUser->tenant; // Organization|Brand|Store (자동 타입 캐스팅)
```

### 3. Trait 기반 복잡도 분리

**User 모델 복잡도 감소**:
- `HasTenantRelations`: TenantUser 관계 관리 (30 LOC)
- `HasTenantPermissions`: 권한 확인 로직 (60 LOC)
- `TenantAccessor`: 메서드 체이닝 헬퍼 (40 LOC)

**리팩토링 효과**:
- User 모델 LOC: 350 → 250 (30% 감소)
- 단일 책임 원칙 준수
- 테스트 용이성 향상

### 4. Fluent API

**장점**:
- ✅ 가독성 향상: `$user->tenant($org)->canManage()`
- ✅ 메서드 체이닝: `$user->tenant($store)->isOwner()`
- ✅ IDE 지원: TenantAccessor 타입 힌트

**구현 패턴**:
```php
// Accessor 패턴 (GoF Design Pattern)
public function tenant(Model $model): TenantAccessor
{
    return new TenantAccessor($this, $model);
}
```

### 5. CODE-FIRST 추적성

**@TAG 시스템**:
```php
/**
 * @CODE:RBAC-001 | SPEC: SPEC-RBAC-001.md | TEST: tests/Unit/Models/TenantUserTest.php
 */
```

**TAG 체인**:
```
@SPEC:RBAC-001 (.moai/specs/SPEC-RBAC-001/spec.md)
    ↓
@TEST:RBAC-001 (tests/Unit/Models/TenantUserTest.php)
    ↓
@CODE:RBAC-001 (app/Models/TenantUser.php, User.php, HasTenantPermissions.php)
    ↓
@DOC:RBAC-001 (docs/rbac-system.md - 본 문서)
```

---

## 관련 문서

- [roles-and-permissions.md](./roles-and-permissions.md): 역할 매핑 테이블
- [auth.md](./auth.md): Firebase + Sanctum 인증 시스템
- [auth/redirect.md](./auth/redirect.md): 지능형 테넌트 리다이렉트
- [models/tables/tenant_users.md](./models/tables/tenant_users.md): TenantUser 테이블 스키마
- [whitepaper.md](./whitepaper.md): 전체 시스템 설계

---

## 파일 구조

```
app/
├── Models/
│   ├── User.php                         # HasTenants 구현
│   ├── TenantUser.php                   # 피벗 모델
│   ├── Organization.php                 # 테넌트 모델
│   ├── Brand.php                        # 테넌트 모델
│   ├── Store.php                        # 테넌트 모델
│   └── Concerns/
│       ├── HasTenantRelations.php       # 관계 관리
│       ├── HasTenantPermissions.php     # 권한 확인
│       └── TenantAccessor.php           # 메서드 체이닝
├── Enums/
│   ├── UserType.php                     # admin|user|customer
│   ├── TenantRole.php                   # owner|manager|viewer
│   └── ScopeType.php                    # PLATFORM|SYSTEM|ORG|BRD|STR
└── Providers/
    ├── AppServiceProvider.php           # Polymorphic morphMap 등록
    └── Filament/
        ├── OrganizationPanelProvider.php
        ├── BrandPanelProvider.php
        └── StorePanelProvider.php

database/migrations/
└── xxxx_create_tenant_users_table.php

tests/
├── Unit/
│   ├── Models/
│   │   └── TenantUserTest.php
│   └── Enums/
│       └── TenantRoleTest.php
└── Feature/
    └── Auth/
        └── TenantAccessTest.php
```

---

**최종 검토**: 2025-10-23
**작성자**: @Alfred
**상태**: ✅ 구현 완료, 문서 동기화 완료

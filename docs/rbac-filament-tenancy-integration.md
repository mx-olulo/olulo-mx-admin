# RBAC + Filament Tenancy 통합

## 최종 아키텍처

Filament v4의 내장 Tenancy 기능과 Spatie Permission을 통합하여 스코프 기반 RBAC를 구현합니다.

---

## 핵심 개념

### 1. Filament Tenancy
- **목적**: 멀티 테넌시 (팀/조직 전환)
- **기능**: 테넌트 스위처, 라우팅, 보안 검증
- **URL**: `/admin/{tenant}/dashboard`

### 2. Spatie Permission
- **목적**: 역할 기반 권한 관리
- **기능**: `hasRole()`, `can()`, `team_id` 컨텍스트

### 3. 통합 방식
- **Role 모델을 Filament Tenant로 직접 사용**
- Filament가 Role 선택 관리
- 미들웨어가 Spatie에 `team_id` 전달

---

## 데이터 구조

### roles 테이블

```sql
CREATE TABLE roles (
    id BIGINT PRIMARY KEY,
    name VARCHAR(255),
    guard_name VARCHAR(255),
    team_id BIGINT NULL,           -- Spatie 표준
    scope_type VARCHAR(20) NULL,   -- 확장: 'ORG', 'BRAND', 'STORE'
    scope_ref_id BIGINT NULL,      -- 확장: 실제 엔터티 PK
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### Role 모델 (Filament Tenant)

```php
// app/Models/Role.php
class Role extends SpatieRole
{
    protected $fillable = [
        'name',
        'guard_name',
        'team_id',
        'scope_type',
        'scope_ref_id',
    ];
    
    // Filament Tenancy용 메서드
    public function getTenantName(): string
    {
        return match ($this->scope_type) {
            'ORG' => "Organization #{$this->scope_ref_id}",
            'BRAND' => "Brand #{$this->scope_ref_id}",
            'STORE' => "Store #{$this->scope_ref_id}",
            default => "Team #{$this->team_id}",
        };
    }
}
```

---

## 구현 상세

### 1. AdminPanelProvider 설정

```php
// app/Providers/Filament/AdminPanelProvider.php
public function panel(Panel $panel): Panel
{
    return $panel
        ->tenant(\App\Models\Role::class)
        ->tenantMiddleware([
            \App\Http\Middleware\SetSpatieTeamId::class,
        ], isPersistent: true);
}
```

**설명**:
- `->tenant(Role::class)`: Role을 Filament Tenant로 사용
- `->tenantMiddleware()`: Spatie 통합 미들웨어 등록
- `isPersistent: true`: 세션 유지

---

### 2. User 모델 (HasTenants 구현)

```php
// app/Models/User.php
class User extends Authenticatable implements FilamentUser, HasTenants
{
    use HasRoles;
    
    /**
     * Filament: 사용자가 접근 가능한 테넌트(Role) 목록
     */
    public function getTenants(Panel $panel): Collection
    {
        return $this->roles
            ->whereNotNull('team_id')
            ->unique('team_id')
            ->values();
    }
    
    /**
     * Filament: 테넌트 접근 권한 검증
     */
    public function canAccessTenant(Model $tenant): bool
    {
        // $tenant는 Role 인스턴스
        return $this->roles->contains('id', $tenant->id);
    }
}
```

**설명**:
- `getTenants()`: Filament 테넌트 스위처에 표시할 목록
- `canAccessTenant()`: 보안 검증 (URL 조작 방지)

---

### 3. SetSpatieTeamId 미들웨어

```php
// app/Http/Middleware/SetSpatieTeamId.php
class SetSpatieTeamId
{
    public function handle(Request $request, Closure $next): Response
    {
        // Filament가 관리하는 현재 테넌트(Role)
        $tenant = Filament::getTenant();
        
        if ($tenant) {
            // Spatie Permission에 team_id 설정
            // $tenant는 Role 인스턴스
            setPermissionsTeamId($tenant->team_id);
        }
        
        return $next($request);
    }
}
```

**설명**:
- Filament가 자동으로 Role(테넌트) 관리
- 미들웨어는 Role의 team_id를 Spatie에 전달

---

## 플로우

```
1. 사용자 로그인
   ↓
2. Filament: 사용 가능한 테넌트 목록 표시
   getTenants() 호출
   ↓
3. 사용자가 테넌트 선택
   ↓
4. Filament: URL 변경
   /admin/{tenant}/dashboard
   ↓
5. Filament: 테넌트 검증
   canAccessTenant() 호출
   ↓
6. SetSpatieTeamId 미들웨어 실행
   setPermissionsTeamId($tenant->team_id)
   ↓
7. 권한 체크
   $user->hasRole('admin')  // team_id 컨텍스트 자동 적용
   $user->can('products.create')
```

---

## 사용 예시

### 1. 역할 생성

```php
Role::create([
    'name' => 'org_admin',
    'team_id' => 100,
    'scope_type' => 'ORG',
    'scope_ref_id' => 1,
]);
```

### 2. 역할 할당

```php
$user->assignRole($role);
```

### 3. 현재 테넌트 조회

```php
// Filament 헬퍼
$tenant = Filament::getTenant();  // Role 인스턴스
echo $tenant->team_id;        // 100
echo $tenant->scope_type;     // 'ORG'
echo $tenant->scope_ref_id;   // 1
echo $tenant->getTenantName(); // "Organization #1"

// 또는 글로벌 헬퍼
$tenant = currentTenant();    // Role 인스턴스
$teamId = currentTeamId();    // 100
```

### 4. 권한 체크

```php
// Spatie 표준 방식 (자동으로 team_id 컨텍스트 적용)
if ($user->hasRole('admin')) {
    // ...
}

if ($user->can('products.create')) {
    // ...
}
```

### 5. Filament Resource에서 사용

```php
class ProductResource extends Resource
{
    public static function getEloquentQuery(): Builder
    {
        $tenant = Filament::getTenant();
        
        return parent::getEloquentQuery()
            ->where('store_id', $tenant->scope_ref_id);
    }
}
```

---

## 장점

### 1. Filament 내장 기능 활용

- ✅ 자동 테넌트 스위처 UI
- ✅ 자동 라우팅 (`/admin/{tenant}/...`)
- ✅ 자동 보안 검증
- ✅ 세션 관리 자동

### 2. 코드 단순화

- ✅ 미들웨어 10줄
- ✅ ScopeContextService 불필요
- ✅ 세션 관리 불필요

### 3. Spatie 표준 준수

- ✅ `team_id` 사용
- ✅ `setPermissionsTeamId()` 사용
- ✅ 기존 Spatie 기능 모두 사용 가능

### 4. 확장성

- ✅ Team 모델에 메타데이터 추가 가능
- ✅ Filament Tenancy 기능 모두 사용 가능
- ✅ 다른 Panel에도 적용 가능

---

## 파일 구조

```
app/
├── Models/
│   ├── Role.php                    # Spatie Role 확장 + Filament Tenant
│   └── User.php                    # HasTenants 구현
├── Http/Middleware/
│   └── SetSpatieTeamId.php         # Spatie 통합 미들웨어
├── Providers/Filament/
│   └── AdminPanelProvider.php      # Tenancy 설정
└── Support/
    └── helpers.php                 # currentTenant(), currentTeamId()

database/migrations/
└── xxxx_add_scope_fields_to_roles_table.php

config/
└── permission.php                  # Role 모델 등록
```

---

## 마이그레이션 가이드

### 기존 코드에서 전환

```bash
# 1. 마이그레이션 실행
php artisan migrate

# 2. 역할 생성 (Seeder)
php artisan db:seed --class=RoleSeeder

# 3. 사용자에게 역할 할당
# (기존 코드 수정 필요)

# 4. Filament에서 테넌트 선택
# (자동 UI 제공)
```

---

## 주의사항

### 1. Role이 Filament Tenant

- Role 모델이 Filament Tenant로 직접 사용됨
- 별도의 Team 모델 불필요
- `getTenantName()` 메서드로 UI 표시 이름 제공

### 2. 테넌트 전환 시

- Filament가 자동으로 URL 변경
- 미들웨어가 자동으로 Spatie 설정
- 수동 세션 관리 불필요

### 3. 권한 체크

- 항상 Spatie 표준 방식 사용
- `hasRole()`, `can()` 등
- `team_id` 컨텍스트 자동 적용

---

## 결론

Filament Tenancy와 Spatie Permission의 완벽한 통합으로:

- ✅ 코드 대폭 단순화
- ✅ Filament 내장 UI 활용
- ✅ 자동 보안 검증
- ✅ 표준 준수

**핵심**: Role을 Filament Tenant로 직접 사용하여 별도 모델 불필요!

**혁신적 단순화**:
- ❌ Team 모델 불필요
- ❌ 가상 모델 불필요
- ✅ Role 하나로 모든 것 해결

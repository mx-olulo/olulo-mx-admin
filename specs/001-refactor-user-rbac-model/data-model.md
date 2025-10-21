# 데이터 모델: 3티어 사용자 권한 시스템

**날짜**: 2025-10-20
**관련 SPEC**: [spec.md](./spec.md)
**관련 계획**: [plan.md](./plan.md)
**관련 연구**: [research.md](./research.md)

## 개요

Spatie Permissions 제거 후 자체 tenant_users 피벗 테이블 기반 3티어 사용자 권한 모델의 데이터 스키마 및 관계를 정의합니다.

---

## 엔티티 목록

1. **User** (수정) - 모든 사용자 (Admin, User, Customer)
2. **TenantUser** (신규) - Admin과 테넌트 간 M:N 피벗
3. **Organization** (수정) - 최상위 테넌트
4. **Brand** (수정) - Organization 하위 테넌트
5. **Store** (수정) - Brand 하위 테넌트

---

## 스키마 정의

### 1. users (기존 테이블 수정)

```sql
CREATE TABLE users (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255),                    -- Nullable (Firebase Customer는 NULL)
    firebase_uid VARCHAR(128) UNIQUE,         -- Customer 전용
    firebase_claims JSON,                     -- Customer 전용
    provider VARCHAR(50),                     -- 'firebase', 'email', etc.
    phone_number VARCHAR(20),
    firebase_phone VARCHAR(20),
    avatar_url VARCHAR(500),
    email_verified_at TIMESTAMP,
    locale VARCHAR(10) DEFAULT 'es-MX',
    last_login_at TIMESTAMP,

    -- 신규 추가 컬럼
    user_type VARCHAR(20) NOT NULL DEFAULT 'admin',  -- 'admin', 'user', 'customer'
    global_role VARCHAR(50),                          -- User 전용: 'platform_admin', 'system_admin', NULL

    -- 기존 2FA 컬럼 유지
    two_factor_secret TEXT,
    two_factor_recovery_codes TEXT,
    two_factor_confirmed_at TIMESTAMP,

    remember_token VARCHAR(100),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    -- 인덱스
    INDEX idx_user_type (user_type),
    INDEX idx_global_role (global_role),
    INDEX idx_firebase_uid (firebase_uid)
);
```

**변경 사항**:
- **추가**: `user_type` 컬럼 (Admin, User, Customer 구분)
- **추가**: `global_role` 컬럼 (User의 Platform/System 역할)
- **유지**: Firebase 관련 컬럼 (Customer 인증)
- **제거 예정**: Spatie Permissions 관련 관계 (HasRoles trait)

**검증 규칙**:
- `user_type`은 enum ('admin', 'user', 'customer') 중 하나
- `global_role`은 User(user_type='user')만 보유 가능
- `firebase_uid`는 Customer(user_type='customer')만 필수
- Admin/User는 `password` 필수, Customer는 Nullable

---

### 2. tenant_users (신규 피벗 테이블)

```sql
CREATE TABLE tenant_users (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    tenant_type VARCHAR(10) NOT NULL,    -- 'ORG', 'BRD', 'STR' (morphMap)
    tenant_id BIGINT NOT NULL,
    role VARCHAR(50) NOT NULL,           -- 'owner', 'manager', 'viewer'
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    -- 외래 키
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,

    -- 복합 유니크 제약 (사용자당 테넌트별 단일 역할)
    UNIQUE KEY unique_user_tenant (user_id, tenant_type, tenant_id),

    -- 인덱스
    INDEX idx_tenant (tenant_type, tenant_id),
    INDEX idx_user_role (user_id, role)
);
```

**설명**:
- **user_id**: Admin 사용자 ID
- **tenant_type**: 테넌트 타입 ('ORG', 'BRD', 'STR') - morphMap 매핑
- **tenant_id**: 실제 테넌트 ID (Organization/Brand/Store의 PK)
- **role**: 테넌트별 역할 ('owner', 'manager', 'viewer')

**검증 규칙**:
- `role`은 TenantRole enum 값 중 하나
- `tenant_type`은 ScopeType enum의 morphMap 키 중 하나
- `tenant_id`는 해당 tenant_type의 실제 레코드 존재 확인 (외래 키 제약 불가 - 폴리모픽)

**폴리모픽 관계 매핑** (App\Enums\ScopeType):
```php
'ORG' => Organization::class,
'BRD' => Brand::class,
'STR' => Store::class,
```

---

### 3. organizations (기존 테이블 수정)

```sql
CREATE TABLE organizations (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    -- 기존 컬럼들 유지...
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

**관계 추가**:
- **tenantUsers()**: `hasMany(TenantUser, 'tenant_id')->where('tenant_type', 'ORG')`
- **admins()**: `tenantUsers()->with('user')`

---

### 4. brands (기존 테이블 수정)

```sql
CREATE TABLE brands (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    organization_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    -- 기존 컬럼들 유지...
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
);
```

**관계 추가**:
- **tenantUsers()**: `hasMany(TenantUser, 'tenant_id')->where('tenant_type', 'BRD')`
- **admins()**: `tenantUsers()->with('user')`

---

### 5. stores (기존 테이블 수정)

```sql
CREATE TABLE stores (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    brand_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    -- 기존 컬럼들 유지...
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE CASCADE
);
```

**관계 추가**:
- **tenantUsers()**: `hasMany(TenantUser, 'tenant_id')->where('tenant_type', 'STR')`
- **admins()**: `tenantUsers()->with('user')`

---

## Eloquent 관계 정의

### User 모델

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;

class User extends Authenticatable
{
    // 사용자가 접근 가능한 모든 테넌트 (M:N 관계)
    public function tenantUsers(): HasMany
    {
        return $this->hasMany(TenantUser::class);
    }

    // 특정 타입의 테넌트만 조회
    public function getTenants(string $tenantType): Collection
    {
        return $this->tenantUsers()
            ->where('tenant_type', $tenantType)
            ->with('tenant')
            ->get()
            ->pluck('tenant');
    }

    // 특정 테넌트의 역할 조회
    public function getRoleForTenant(Model $tenant): ?string
    {
        return $this->tenantUsers()
            ->where('tenant_type', $tenant->getMorphClass())
            ->where('tenant_id', $tenant->id)
            ->value('role');
    }

    // 특정 테넌트에 특정 역할 보유 여부
    public function hasRoleForTenant(Model $tenant, string $role): bool
    {
        return $this->getRoleForTenant($tenant) === $role;
    }

    // 글로벌 역할 여부 (User 타입)
    public function hasGlobalRole(string $role): bool
    {
        return $this->user_type === 'user' && $this->global_role === $role;
    }

    // Firebase 사용자 여부 (Customer 타입)
    public function isFirebaseUser(): bool
    {
        return $this->user_type === 'customer' && !empty($this->firebase_uid);
    }
}
```

---

### TenantUser 모델 (신규)

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class TenantUser extends Model
{
    protected $fillable = ['user_id', 'tenant_type', 'tenant_id', 'role'];

    // 사용자 관계
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // 폴리모픽 테넌트 관계
    public function tenant(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'tenant_type', 'tenant_id');
    }

    // Activity Log 설정
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['user_id', 'tenant_type', 'tenant_id', 'role'])
            ->logOnlyDirty()
            ->useLogName('tenant_user');
    }
}
```

---

### Organization/Brand/Store 모델

```php
namespace App\Models;

class Organization extends Model
{
    // Admin 사용자들 (M:N)
    public function tenantUsers(): HasMany
    {
        return $this->hasMany(TenantUser::class, 'tenant_id')
            ->where('tenant_type', 'ORG');
    }

    public function admins(): Collection
    {
        return $this->tenantUsers()->with('user')->get()->pluck('user');
    }

    // 특정 역할의 Admin만 조회
    public function owners(): Collection
    {
        return $this->tenantUsers()
            ->where('role', 'owner')
            ->with('user')
            ->get()
            ->pluck('user');
    }
}

// Brand, Store도 동일한 패턴
```

---

## 상태 전이 (역할 변경)

### 역할 할당/변경 시나리오

```
초기 상태: User has no tenant_users records
    ↓ (Admin creates tenant_user record)
Owner 할당: tenant_users(user_id=1, tenant_type='ORG', tenant_id=5, role='owner')
    ↓ (Admin changes role)
Manager로 변경: UPDATE tenant_users SET role='manager' WHERE id=...
    ↓ (Admin removes access)
삭제: DELETE FROM tenant_users WHERE id=...
```

### 글로벌 역할 전이

```
초기 상태: users.global_role = NULL
    ↓ (Platform Admin promotion)
Platform Admin 할당: UPDATE users SET global_role='platform_admin' WHERE id=...
    ↓ (Role change)
System Admin으로 변경: UPDATE users SET global_role='system_admin' WHERE id=...
    ↓ (Role removal)
NULL로 복구: UPDATE users SET global_role=NULL WHERE id=...
```

---

## 데이터 무결성 제약

1. **사용자 타입별 제약**:
   - Admin: `user_type='admin'` AND `global_role IS NULL`
   - User: `user_type='user'` AND `global_role IN ('platform_admin', 'system_admin')`
   - Customer: `user_type='customer'` AND `firebase_uid IS NOT NULL`

2. **테넌트 역할 유효성**:
   - `role` 값은 TenantRole enum에 정의된 값만 허용
   - `tenant_type` + `tenant_id` 조합이 실제 테넌트 레코드 존재

3. **CASCADE 정리**:
   - User 삭제 시 tenant_users 자동 삭제 (ON DELETE CASCADE)
   - Organization 삭제 시 tenant_users(tenant_type='ORG', tenant_id=X) 수동 정리 필요

4. **UNIQUE 제약**:
   - `(user_id, tenant_type, tenant_id)` 조합은 유일 (사용자당 테넌트별 단일 역할)

---

## 마이그레이션 순서

1. `create_tenant_users_table` - 새 피벗 테이블 생성
2. `add_user_type_to_users_table` - users 테이블에 user_type, global_role 추가
3. `migrate_spatie_roles_to_tenant_users` - Spatie 데이터 이관
4. `verify_tenant_users_migration` - 데이터 무결성 검증
5. `drop_spatie_permission_tables` - Spatie 테이블 삭제 (검증 후)

---

## 인덱스 전략

### 성능 최적화 인덱스

```sql
-- tenant_users 복합 인덱스
CREATE INDEX idx_tenant_users_lookup ON tenant_users(user_id, tenant_type, tenant_id);
CREATE INDEX idx_tenant_users_role ON tenant_users(role);

-- users 인덱스
CREATE INDEX idx_users_type_role ON users(user_type, global_role);
CREATE INDEX idx_users_firebase ON users(firebase_uid) WHERE firebase_uid IS NOT NULL;
```

**근거**:
- `idx_tenant_users_lookup`: getTenants() 쿼리 최적화
- `idx_tenant_users_role`: 특정 역할 필터링
- `idx_users_type_role`: canAccessPanel() 쿼리 최적화
- `idx_users_firebase`: Customer 인증 조회 최적화

---

## 샘플 데이터

### Admin 사용자 + 멀티테넌트 역할

```php
// Admin 사용자 생성
$admin = User::create([
    'name' => 'John Admin',
    'email' => 'admin@example.com',
    'password' => Hash::make('password'),
    'user_type' => 'admin',
]);

// Organization A - Owner 역할
TenantUser::create([
    'user_id' => $admin->id,
    'tenant_type' => 'ORG',
    'tenant_id' => 1,  // Organization A
    'role' => 'owner',
]);

// Organization B - Viewer 역할
TenantUser::create([
    'user_id' => $admin->id,
    'tenant_type' => 'ORG',
    'tenant_id' => 2,  // Organization B
    'role' => 'viewer',
]);

// Brand C - Manager 역할
TenantUser::create([
    'user_id' => $admin->id,
    'tenant_type' => 'BRD',
    'tenant_id' => 5,  // Brand C
    'role' => 'manager',
]);
```

### User (Platform Admin)

```php
$user = User::create([
    'name' => 'Jane Platform Admin',
    'email' => 'platform@example.com',
    'password' => Hash::make('password'),
    'user_type' => 'user',
    'global_role' => 'platform_admin',
]);

// User는 tenant_users 레코드 없음 (글로벌 역할만)
```

### Customer (Firebase 인증)

```php
$customer = User::create([
    'name' => 'Carlos Customer',
    'email' => 'customer@example.com',
    'user_type' => 'customer',
    'firebase_uid' => 'firebase_uid_123456',
    'provider' => 'firebase',
]);

// Customer는 tenant_users, global_role 모두 없음
```

---

**작성자**: Claude Code
**검토 필요**: 구현 전 DBA 리뷰

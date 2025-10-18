# 데이터 모델: 사용자 온보딩 위자드

**기능**: 사용자 온보딩 위자드
**브랜치**: `001-user-onboarding-wizard`
**날짜**: 2025-10-19

## 엔티티 관계 다이어그램

```
User (N) ←→ (M) Role
                ↓ (scope_type, scope_ref_id)
         Organization (1) ←→ (N) Store
```

**관계 설명**:
- User와 Role: 다대다 (model_has_roles 피벗 테이블)
- Role은 scope_type과 scope_ref_id로 Organization 또는 Store 참조
- Organization과 Store: 일대다 (Store.organization_id nullable)

## 엔티티 정의

### User (기존)

**테이블**: `users`

**속성**:
- `id`: bigint, PK
- `name`: string
- `email`: string, unique
- `email_verified_at`: timestamp, nullable
- `password`: string, nullable (Firebase 사용 시)
- `created_at`: timestamp
- `updated_at`: timestamp

**메서드 추가**:
```php
/**
 * 사용자가 접근 가능한 테넌트 목록 반환
 *
 * @param Filament\Panel|null $panel
 * @return \Illuminate\Support\Collection<Organization|Store>
 */
public function getTenants(?Filament\Panel $panel = null): Collection
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

/**
 * 특정 테넌트에 접근 가능한지 확인
 *
 * @param Organization|Store $tenant
 * @return bool
 */
public function canAccessTenant($tenant): bool
{
    $scopeType = $tenant instanceof Organization
        ? ScopeType::ORGANIZATION->value
        : ScopeType::STORE->value;

    return $this->roles()
        ->where('scope_type', $scopeType)
        ->where('scope_ref_id', $tenant->id)
        ->exists();
}
```

### Organization (기존)

**테이블**: `organizations`

**속성**:
- `id`: bigint, PK
- `name`: string, 필수
- `description`: text, nullable
- `logo_path`: string, nullable
- `created_at`: timestamp
- `updated_at`: timestamp

**관계**:
- `hasMany(Store::class)`: 조직 소속 매장 (nullable)
- `hasManyThrough(User::class, Role::class)`: 조직 멤버 (Role 경유)

**온보딩에서 사용**:
- 생성 시 `name`만 필수
- 나머지 필드는 온보딩 후 추가 입력

### Store (기존)

**테이블**: `stores`

**속성**:
- `id`: bigint, PK
- `name`: string, 필수
- `brand_id`: bigint, nullable, FK (brands)
- `organization_id`: bigint, nullable, FK (organizations)
- `address`: string, nullable
- `phone`: string, nullable
- `business_hours`: json, nullable
- `status`: enum('active', 'inactive', 'pending'), default 'pending'
- `created_at`: timestamp
- `updated_at`: timestamp

**관계**:
- `belongsTo(Organization::class)`: 소속 조직 (nullable)
- `belongsTo(Brand::class)`: 브랜드 (nullable)
- `hasManyThrough(User::class, Role::class)`: 매장 멤버 (Role 경유)

**온보딩에서 사용**:
- 생성 시 `name`만 필수, `organization_id = null` (독립 매장)
- `status = 'pending'` (심사 대기)
- 나머지 필드는 온보딩 후 추가 입력

### Role (Spatie Permission 확장)

**테이블**: `roles`

**속성**:
- `id`: bigint, PK
- `name`: string (예: 'owner', 'manager', 'staff')
- `guard_name`: string, default 'web'
- `scope_type`: string, nullable (커스텀 필드: 'ORG' | 'STORE')
- `scope_ref_id`: bigint, nullable (커스텀 필드: Organization.id | Store.id)
- `created_at`: timestamp
- `updated_at`: timestamp

**온보딩에서 생성되는 Role**:
- Organization Owner: `{name: 'owner', scope_type: 'ORG', scope_ref_id: <organization_id>}`
- Store Owner: `{name: 'owner', scope_type: 'STORE', scope_ref_id: <store_id>}`

**Enum 정의** (필요 시 생성):
```php
enum ScopeType: string
{
    case ORGANIZATION = 'ORG';
    case STORE = 'STORE';
}
```

### model_has_roles (Spatie Permission 피벗 테이블)

**테이블**: `model_has_roles`

**속성**:
- `role_id`: bigint, FK (roles)
- `model_type`: string (예: 'App\Models\User')
- `model_id`: bigint (User.id)

**온보딩에서 사용**:
- User와 생성된 Owner Role 연결

## 마이그레이션 전략

**기존 마이그레이션 재사용**:
- `users`, `organizations`, `stores`, `roles`, `model_has_roles` 테이블은 이미 존재
- `roles` 테이블에 `scope_type`, `scope_ref_id` 컬럼이 없다면 마이그레이션 필요

**신규 마이그레이션** (필요 시):
```php
// database/migrations/YYYY_MM_DD_add_scope_to_roles_table.php
public function up()
{
    Schema::table('roles', function (Blueprint $table) {
        $table->string('scope_type')->nullable()->after('guard_name');
        $table->unsignedBigInteger('scope_ref_id')->nullable()->after('scope_type');

        $table->index(['scope_type', 'scope_ref_id']);
    });
}

public function down()
{
    Schema::table('roles', function (Blueprint $table) {
        $table->dropIndex(['scope_type', 'scope_ref_id']);
        $table->dropColumn(['scope_type', 'scope_ref_id']);
    });
}
```

## 데이터 흐름

### 조직 생성 플로우

1. 사용자 입력: `{name: "My Organization"}`
2. `OnboardingService::createOrganization()`
   - DB 트랜잭션 시작
   - `Organization::create(['name' => 'My Organization'])`
   - `Role::firstOrCreate(['name' => 'owner', 'scope_type' => 'ORG', 'scope_ref_id' => <org_id>])`
   - `User::assignRole(<role>)`
   - 트랜잭션 커밋
3. 결과: Organization 생성, User에 Owner Role 부여

### 매장 생성 플로우

1. 사용자 입력: `{name: "My Store"}`
2. `OnboardingService::createStore()`
   - DB 트랜잭션 시작
   - `Store::create(['name' => 'My Store', 'organization_id' => null, 'status' => 'pending'])`
   - `Role::firstOrCreate(['name' => 'owner', 'scope_type' => 'STORE', 'scope_ref_id' => <store_id>])`
   - `User::assignRole(<role>)`
   - 트랜잭션 커밋
3. 결과: Store 생성 (독립 매장), User에 Owner Role 부여

## 검증 규칙

### Organization

- `name`: required, string, max:255, unique:organizations,name

### Store

- `name`: required, string, max:255, unique:stores,name

### Role (내부 생성, 검증 불필요)

- `name`: 'owner' (고정)
- `scope_type`: ScopeType Enum 값
- `scope_ref_id`: Organization.id 또는 Store.id (exists 검증)

## 인덱스 최적화

### roles 테이블

```sql
CREATE INDEX idx_roles_scope ON roles(scope_type, scope_ref_id);
```

**이유**: `getTenants()` 메서드에서 자주 조회

### model_has_roles 테이블

```sql
CREATE INDEX idx_model_has_roles_composite ON model_has_roles(model_type, model_id, role_id);
```

**이유**: User의 Role 조회 성능 향상 (Spatie Permission 기본 제공)

## 데이터 무결성

### 제약 조건

- `stores.organization_id`: nullable FK (독립 매장 허용)
- `roles.scope_ref_id`: nullable (일반 Role은 null)
- `model_has_roles`: Composite PK (role_id, model_type, model_id)

### 삭제 정책

- Organization 삭제 시: Store의 organization_id를 null로 설정 (ON DELETE SET NULL)
- Store 삭제 시: 관련 Role 삭제 (ON DELETE CASCADE)
- User 삭제 시: model_has_roles 연결 삭제 (ON DELETE CASCADE)

## 예시 데이터

### 조직 생성 후

**organizations**:
| id | name | created_at |
|----|------|------------|
| 1 | Acme Corp | 2025-10-19 10:00:00 |

**roles**:
| id | name | scope_type | scope_ref_id |
|----|------|------------|--------------|
| 1 | owner | ORG | 1 |

**model_has_roles**:
| role_id | model_type | model_id |
|---------|------------|----------|
| 1 | App\Models\User | 101 |

### 매장 생성 후 (독립 매장)

**stores**:
| id | name | organization_id | status |
|----|------|-----------------|--------|
| 1 | Downtown Store | null | pending |

**roles**:
| id | name | scope_type | scope_ref_id |
|----|------|------------|--------------|
| 2 | owner | STORE | 1 |

**model_has_roles**:
| role_id | model_type | model_id |
|---------|------------|----------|
| 2 | App\Models\User | 102 |

## 참고 자료

- [Spatie Permission 문서](https://spatie.be/docs/laravel-permission/v6/introduction)
- [Filament Tenancy 문서](https://filamentphp.com/docs/4.x/panels/tenancy)
- [Laravel Eloquent Relationships](https://laravel.com/docs/12.x/eloquent-relationships)

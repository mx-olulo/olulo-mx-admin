# RBAC Role 확장 설계

## 설계 결정: Role 모델 확장 방식

### 개요

Spatie Permission의 Role 모델을 확장하여 `scope_type`과 `scope_ref_id` 필드를 추가함으로써, 별도의 `scopes` 테이블 없이 스코프 기반 RBAC를 구현합니다.

### 핵심 통찰

1. **Spatie의 캐싱 활용**: Spatie는 Role/Permission 데이터를 자동으로 캐싱하며, 확장 필드(`scope_type`, `scope_ref_id`)도 함께 캐싱됩니다.
2. **team_id는 int 유지**: Spatie 표준을 따라 `team_id`는 정수형으로 유지합니다.
3. **scopes 테이블 불필요**: Role 모델에 스코프 정보를 직접 저장하므로 별도 테이블이 필요 없습니다.

---

## 아키텍처

### 데이터 구조

```
roles 테이블:
┌────┬───────────┬─────────┬────────────┬──────────────┬────────────┐
│ id │ name      │ team_id │ scope_type │ scope_ref_id │ guard_name │
├────┼───────────┼─────────┼────────────┼──────────────┼────────────┤
│ 1  │ super_admin│ NULL   │ NULL       │ NULL         │ web        │ ← 글로벌
│ 2  │ org_admin │ 100     │ ORG        │ 1            │ web        │ ← ORG:1
│ 3  │ org_admin │ 200     │ ORG        │ 2            │ web        │ ← ORG:2
│ 4  │ brand_mgr │ 300     │ BRAND      │ 5            │ web        │ ← BRAND:5
│ 5  │ store_staff│ 400    │ STORE      │ 10           │ web        │ ← STORE:10
└────┴───────────┴─────────┴────────────┴──────────────┴────────────┘
```

### 플로우

```
사용자 로그인
    ↓
스코프 선택 (ORG:1)
    ↓
ScopeContextService::setScope('ORG', 1)
    ↓
세션 저장: scope_type='ORG', scope_id=1
    ↓
getCurrentTeamId() 호출
    ↓
user->roles (Spatie 캐싱 데이터)
    ↓
roles에서 scope_type='ORG', scope_ref_id=1 찾기
    ↓
team_id=100 반환 (DB 쿼리 없음!)
    ↓
setPermissionsTeamId(100)
    ↓
권한 체크: user->hasRole('org_admin') ✅
```

---

## 구현 상세

### 1. 마이그레이션

```php
// database/migrations/xxxx_add_scope_fields_to_roles_table.php
Schema::table('roles', function (Blueprint $table) {
    $table->string('scope_type', 20)->nullable()->after('team_id');
    $table->unsignedBigInteger('scope_ref_id')->nullable()->after('scope_type');
    $table->index(['scope_type', 'scope_ref_id'], 'idx_role_scope');
});
```

### 2. Role 모델 확장

```php
// app/Models/Role.php
class Role extends SpatieRole
{
    public const TYPE_ORG = 'ORG';
    public const TYPE_BRAND = 'BRAND';
    public const TYPE_STORE = 'STORE';

    protected $fillable = [
        'name',
        'guard_name',
        'team_id',
        'scope_type',
        'scope_ref_id',
    ];

    public function scopeable(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'scope_type', 'scope_ref_id');
    }
}
```

### 3. ScopeContextService

```php
public function getCurrentTeamId(): ?int
{
    $user = auth()->user();
    if (! $user) {
        return null;
    }

    $scopeType = $this->getCurrentScopeType();
    $scopeId = $this->getCurrentScopeId();

    if (! $scopeType || ! $scopeId) {
        return null;
    }

    // Spatie가 캐싱한 roles에서 조회 (DB 쿼리 없음!)
    $role = $user->roles
        ->where('scope_type', $scopeType)
        ->where('scope_ref_id', $scopeId)
        ->first();

    return $role?->team_id;
}
```

---

## 장점

### 1. Spatie 캐싱 완전 활용

```php
// 첫 로그인 시 (1회 DB 조회)
$user = auth()->user();
$user->load('roles');  // Spatie가 캐싱

// 이후 모든 조회는 메모리에서!
$role = $user->roles->where('scope_type', 'ORG')->first();  // 캐시
$teamId = $role->team_id;  // 캐시
```

**성능**: DB 쿼리 0회 (캐시 히트 시)

### 2. 테이블 단순화

- ❌ scopes 테이블 불필요
- ✅ roles 테이블에 통합

### 3. Spatie 표준 준수

- ✅ `team_id`는 int (Spatie 표준)
- ✅ Role 모델 확장 (Spatie 공식 지원)
- ✅ 기존 Spatie 기능 모두 사용 가능

### 4. 역추적 가능

```php
// team_id로 스코프 정보 조회
$role = Role::where('team_id', 100)->first();
echo $role->scope_type;    // 'ORG'
echo $role->scope_ref_id;  // 1
```

---

## 대안 비교

### Option A: scopes 테이블 (이전 설계)

```
roles (team_id) → scopes (id, scope_type, scope_ref_id) → Organization/Brand/Store
```

**단점**:
- 추가 테이블 필요
- DB 조회 필요 (team_id → scope_type/scope_ref_id 변환)
- 캐싱 복잡도 증가

### Option B: 문자열 team_id

```
team_id = "ORG:1" (문자열)
```

**단점**:
- Spatie 비표준 (team_id는 기본적으로 bigint)
- 마이그레이션 수정 필요
- 문자열 비교 성능 저하 (미미하지만)

### Option C: Role 확장 (채택) ⭐

```
roles (team_id, scope_type, scope_ref_id)
```

**장점**:
- Spatie 캐싱 활용
- 표준 준수
- 테이블 단순화
- 성능 최적화

---

## 사용 예시

### 역할 생성

```php
// 글로벌 역할
Role::create([
    'name' => 'super_admin',
    'team_id' => null,
    'scope_type' => null,
    'scope_ref_id' => null,
]);

// 스코프 역할
Role::create([
    'name' => 'org_admin',
    'team_id' => 100,
    'scope_type' => 'ORG',
    'scope_ref_id' => 1,
]);
```

### 역할 할당

```php
// 스코프 설정
scopeContext()->setScope('ORG', 1);  // team_id=100 자동 조회

// 역할 할당
$user->assignRole('org_admin');  // Spatie가 team_id=100 사용
```

### 권한 체크

```php
// 스코프 설정
scopeContext()->setScope('ORG', 1);

// 권한 체크
$user->hasRole('org_admin');  // true (team_id=100)

// 다른 스코프로 전환
scopeContext()->setScope('ORG', 2);

$user->hasRole('org_admin');  // false (team_id=200)
```

---

## 마이그레이션 가이드

### 기존 scopes 테이블에서 전환

```php
// 1. roles 테이블에 필드 추가
php artisan migrate

// 2. 기존 데이터 마이그레이션 (필요 시)
DB::table('roles')->update([
    'scope_type' => DB::raw("(SELECT scope_type FROM scopes WHERE scopes.id = roles.team_id)"),
    'scope_ref_id' => DB::raw("(SELECT scope_ref_id FROM scopes WHERE scopes.id = roles.team_id)"),
]);

// 3. scopes 테이블 제거
Schema::dropIfExists('scopes');
```

---

## 결론

Role 모델 확장 방식은 Spatie의 캐싱 메커니즘을 완전히 활용하면서도 표준을 준수하는 최적의 설계입니다.

**핵심 이점**:
- ✅ 성능 최적화 (Spatie 캐싱)
- ✅ 코드 단순화 (scopes 테이블 제거)
- ✅ 표준 준수 (Spatie 공식 확장 방식)
- ✅ 유지보수성 향상

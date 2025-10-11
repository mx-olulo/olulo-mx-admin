# RBAC 최종 아키텍처

## 핵심 설계 원칙

1. **Spatie Permission 표준 준수**: `team_id`, `setPermissionsTeamId()` 사용
2. **Role 모델 확장**: `scope_type`, `scope_ref_id` 필드 추가
3. **미들웨어 기반 상태 관리**: 매 요청마다 자동 설정
4. **최소한의 추상화**: 불필요한 래퍼 제거

---

## 아키텍처 다이어그램

```
┌─────────────────────────────────────────────────────────────────┐
│                         사용자 로그인                              │
└────────────────────────┬────────────────────────────────────────┘
                         ↓
┌─────────────────────────────────────────────────────────────────┐
│                  Filament UI: 스코프 선택                          │
│  ScopeContextService::switchScope($teamId)                      │
│  → session(['current_team_id' => $teamId])                      │
│  → setPermissionsTeamId($teamId)                                │
└────────────────────────┬────────────────────────────────────────┘
                         ↓
┌─────────────────────────────────────────────────────────────────┐
│              SetScopeContext 미들웨어 (매 요청마다)                 │
│  1. session('current_team_id') 읽기                             │
│  2. 권한 검증: user->roles->contains('team_id', $teamId)         │
│  3. setPermissionsTeamId($teamId) 호출                          │
└────────────────────────┬────────────────────────────────────────┘
                         ↓
┌─────────────────────────────────────────────────────────────────┐
│                    Spatie Permission 동작                        │
│  getPermissionsTeamId() → $teamId                               │
│  user->hasRole('admin') → team_id 컨텍스트에서 체크               │
│  user->can('products.create') → team_id 컨텍스트에서 체크         │
└─────────────────────────────────────────────────────────────────┘
```

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
    updated_at TIMESTAMP,
    
    INDEX idx_role_scope (scope_type, scope_ref_id),
    UNIQUE KEY (team_id, name, guard_name)
);
```

### 데이터 예시

```
┌────┬───────────────┬─────────┬────────────┬──────────────┬────────────┐
│ id │ name          │ team_id │ scope_type │ scope_ref_id │ guard_name │
├────┼───────────────┼─────────┼────────────┼──────────────┼────────────┤
│ 1  │ super_admin   │ NULL    │ NULL       │ NULL         │ web        │
│ 2  │ org_admin     │ 100     │ ORG        │ 1            │ web        │
│ 3  │ org_admin     │ 200     │ ORG        │ 2            │ web        │
│ 4  │ brand_manager │ 300     │ BRAND      │ 5            │ web        │
│ 5  │ store_staff   │ 400     │ STORE      │ 10           │ web        │
└────┴───────────────┴─────────┴────────────┴──────────────┴────────────┘
```

---

## 핵심 컴포넌트

### 1. Role 모델 (확장)

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

**역할**:
- Spatie Role 모델 확장
- `scope_type`, `scope_ref_id` 필드 추가
- 다형 관계 지원 (Organization/Brand/Store)

---

### 2. SetScopeContext 미들웨어

```php
// app/Http/Middleware/SetScopeContext.php
class SetScopeContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        if (! $user) {
            return $next($request);
        }

        $teamId = session('current_team_id');
        
        if ($teamId && $user->roles->contains('team_id', $teamId)) {
            setPermissionsTeamId($teamId);
        } else {
            // 첫 번째 역할 사용
            $firstRole = $user->roles->whereNotNull('team_id')->first();
            if ($firstRole) {
                setPermissionsTeamId($firstRole->team_id);
                session(['current_team_id' => $firstRole->team_id]);
            }
        }

        return $next($request);
    }
}
```

**역할**:
- 매 요청마다 실행
- 세션에서 `current_team_id` 읽기
- Spatie에 `setPermissionsTeamId()` 호출
- 권한 검증

**등록 위치**: `AdminPanelProvider::authMiddleware()`

---

### 3. ScopeContextService (UI 헬퍼)

```php
// app/Services/ScopeContextService.php
class ScopeContextService
{
    // 현재 스코프 정보 (역추적)
    public function getCurrentScope(): ?array
    {
        $teamId = getPermissionsTeamId();
        $role = auth()->user()->roles->where('team_id', $teamId)->first();
        
        return [
            'team_id' => $teamId,
            'scope_type' => $role->scope_type,
            'scope_ref_id' => $role->scope_ref_id,
            'role_name' => $role->name,
        ];
    }
    
    // 사용 가능한 스코프 목록 (스위처용)
    public function getAvailableScopes(User $user): Collection
    {
        return $user->roles->whereNotNull('team_id')->map(...);
    }
    
    // 스코프 전환
    public function switchScope(int $teamId): void
    {
        session(['current_team_id' => $teamId]);
        setPermissionsTeamId($teamId);
    }
}
```

**역할**:
- Filament UI 헬퍼
- 스코프 정보 표시
- 스코프 전환 (스위처)

**사용처**: Filament 컴포넌트, 위젯

---

## 상태 관리

### 세션

```php
// 저장되는 데이터
session(['current_team_id' => 100]);

// 읽기
$teamId = session('current_team_id');
```

**특징**:
- 단일 값만 저장 (`team_id`)
- 미들웨어가 읽음
- 사용자가 선택한 스코프 유지

---

### Spatie Permission

```php
// 설정
setPermissionsTeamId(100);

// 조회
$teamId = getPermissionsTeamId();  // 100

// 권한 체크 (자동으로 team_id 컨텍스트 사용)
$user->hasRole('admin');           // team_id=100 컨텍스트
$user->can('products.create');     // team_id=100 컨텍스트
```

**특징**:
- 요청 생명주기 동안 유지
- 미들웨어가 매 요청마다 설정
- Spatie가 자동으로 사용

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

### 3. 스코프 전환 (Filament UI)

```php
$scopeContext->switchScope(100);
```

### 4. 권한 체크

```php
// Spatie 표준 방식 그대로
if ($user->hasRole('admin')) {
    // ...
}

if ($user->can('products.create')) {
    // ...
}
```

### 5. 현재 스코프 정보

```php
$scope = $scopeContext->getCurrentScope();
// ['team_id' => 100, 'scope_type' => 'ORG', 'scope_ref_id' => 1, 'role_name' => 'org_admin']
```

---

## 장점

### 1. Spatie 표준 준수

- ✅ `team_id` 사용 (int)
- ✅ `setPermissionsTeamId()` 사용
- ✅ 기존 Spatie 기능 모두 사용 가능

### 2. 최소한의 추상화

- ✅ 불필요한 래퍼 제거
- ✅ 미들웨어 기반 자동 설정
- ✅ 세션 단일 값 (`current_team_id`)

### 3. Spatie 캐싱 활용

- ✅ Roles 자동 캐싱
- ✅ `scope_type`, `scope_ref_id` 포함
- ✅ DB 쿼리 최소화

### 4. Filament 통합

- ✅ `authMiddleware()`에 등록
- ✅ 모든 Filament 요청에 자동 적용
- ✅ UI 헬퍼 제공

---

## 파일 구조

```
app/
├── Models/
│   └── Role.php                    # Spatie Role 확장
├── Services/
│   └── ScopeContextService.php     # UI 헬퍼
├── Http/Middleware/
│   └── SetScopeContext.php         # 미들웨어
└── Providers/Filament/
    └── AdminPanelProvider.php      # 미들웨어 등록

database/migrations/
└── xxxx_add_scope_fields_to_roles_table.php

config/
└── permission.php                  # Role 모델 등록

docs/
├── rbac-role-extension-design.md  # 설계 문서
├── rbac-scope-usage-examples.md   # 사용 예시
└── rbac-final-architecture.md     # 최종 아키텍처 (이 문서)
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

# 4. Filament에서 스코프 선택
# (UI 구현 필요)
```

---

## 결론

이 아키텍처는 Spatie Permission의 표준을 준수하면서도 스코프 기반 RBAC를 효율적으로 구현합니다.

**핵심 원칙**:
1. Spatie 표준 준수
2. 최소한의 추상화
3. 미들웨어 기반 자동화
4. Filament 통합

**결과**:
- ✅ 코드 단순화
- ✅ 성능 최적화 (Spatie 캐싱)
- ✅ 유지보수성 향상
- ✅ 확장성 확보

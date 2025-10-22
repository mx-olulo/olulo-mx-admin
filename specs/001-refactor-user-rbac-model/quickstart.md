# 빠른 시작 가이드: 3티어 사용자 권한 시스템

**날짜**: 2025-10-20
**대상**: 개발자 및 시스템 관리자
**관련 문서**: [spec.md](./spec.md) | [plan.md](./plan.md) | [data-model.md](./data-model.md)

## 개요

이 가이드는 Spatie Permissions 제거 후 새로운 3티어 사용자 권한 시스템을 빠르게 이해하고 사용하는 방법을 제공합니다.

---

## 1. 핵심 개념 이해 (5분)

### 3가지 사용자 티어

| 티어 | 설명 | 인증 방식 | 접근 패널 |
|------|------|---------|---------|
| **Admin** | 테넌트 관리자 | Sanctum 세션 | Filament (Organization/Brand/Store) |
| **User** | 시스템 관리자 | Sanctum 세션 | Nova (Platform/System) |
| **Customer** | 일반 사용자 | Firebase JWT | API만 (Filament 접근 불가) |

### 권한 모델

- **Admin**: tenant_users 테이블로 M:N 관계, 테넌트별 독립 역할
- **User**: users.global_role 컬럼 (platform_admin, system_admin)
- **Customer**: 권한 시스템 제외 (Firebase Custom Claims 사용)

---

## 2. 로컬 환경 설정 (10분)

### 1단계: 의존성 제거 및 설치

```bash
# Spatie Permissions 제거
composer remove spatie/laravel-permission

# 의존성 업데이트
composer install
```

### 2단계: 마이그레이션 실행

```bash
# 새로운 권한 테이블 생성
php artisan migrate

# 기존 Spatie 데이터 이관 (있는 경우)
php artisan tenant:migrate-from-spatie
```

### 3단계: 시더 실행

```bash
# 기본 역할 및 테스트 사용자 생성
php artisan db:seed --class=TenantRoleSeeder
php artisan db:seed --class=GlobalRoleSeeder
```

---

## 3. 사용자 유형별 사용법

### Admin 사용자 (테넌트 관리자)

#### 역할 할당
```php
use App\Models\User;
use App\Models\TenantUser;
use App\Models\Organization;

$admin = User::where('user_type', 'admin')->first();
$org = Organization::find(1);

// Owner 역할 할당
TenantUser::create([
    'user_id' => $admin->id,
    'tenant_type' => 'ORG',
    'tenant_id' => $org->id,
    'role' => 'owner',
]);
```

#### 권한 체크
```php
// 특정 역할 보유 여부
if ($admin->hasRoleForTenant($org, 'owner')) {
    // Owner 권한 작업
}

// 관리 권한 (owner 또는 manager)
if ($admin->canManageTenant($org)) {
    // 관리 작업
}

// 보기 권한 (owner, manager, viewer)
if ($admin->canViewTenant($org)) {
    // 조회 작업
}
```

#### Filament 리소스 통합
```php
// app/Filament/Organization/Resources/BrandResource.php

public static function canViewAny(): bool
{
    $tenant = Filament::getTenant();  // 현재 Organization
    return auth()->user()->canViewTenant($tenant);
}

public static function canCreate(): bool
{
    $tenant = Filament::getTenant();
    return auth()->user()->canManageTenant($tenant);
}

public static function canEdit(Model $record): bool
{
    $tenant = Filament::getTenant();
    return auth()->user()->canManageTenant($tenant);
}
```

---

### User 사용자 (글로벌 관리자)

#### 역할 할당
```php
$user = User::create([
    'name' => 'Jane Platform Admin',
    'email' => 'platform@example.com',
    'password' => Hash::make('password'),
    'user_type' => 'user',
    'global_role' => 'platform_admin',
]);
```

#### 권한 체크
```php
// 글로벌 역할 체크
if ($user->hasGlobalRole('platform_admin')) {
    // Platform 관리 작업
}

if ($user->hasGlobalRole('system_admin')) {
    // System 관리 작업
}
```

#### Nova 패널 통합
```php
// app/Nova/User.php

public static function authorizedToViewAny(Request $request): bool
{
    return $request->user()->hasGlobalRole('platform_admin');
}
```

---

### Customer 사용자 (Firebase 인증)

#### Firebase 인증 (기존 유지)
```php
// API 컨트롤러에서 Firebase 토큰 검증
use Kreait\Firebase\Auth;

public function __construct(protected Auth $auth) {}

public function index(Request $request)
{
    $idToken = $request->bearerToken();
    $verifiedIdToken = $this->auth->verifyIdToken($idToken);
    $firebaseUid = $verifiedIdToken->claims()->get('sub');

    $customer = User::findByFirebaseUid($firebaseUid);

    // Customer 데이터 반환
}
```

---

## 4. 일반적인 작업 예시

### 1) Admin을 여러 테넌트에 추가

```php
$admin = User::find(1);

// Organization A - Owner
TenantUser::create([
    'user_id' => $admin->id,
    'tenant_type' => 'ORG',
    'tenant_id' => 1,
    'role' => 'owner',
]);

// Brand B - Manager
TenantUser::create([
    'user_id' => $admin->id,
    'tenant_type' => 'BRD',
    'tenant_id' => 5,
    'role' => 'manager',
]);

// Store C - Viewer
TenantUser::create([
    'user_id' => $admin->id,
    'tenant_type' => 'STR',
    'tenant_id' => 10,
    'role' => 'viewer',
]);
```

### 2) Admin의 모든 테넌트 조회

```php
// Organization만 조회
$orgs = $admin->getTenants('ORG');

// 모든 타입의 테넌트
$allTenants = $admin->tenantUsers()
    ->with('tenant')
    ->get()
    ->pluck('tenant');
```

### 3) 특정 테넌트의 모든 Admin 조회

```php
$org = Organization::find(1);

// TenantUser 레코드
$tenantUsers = $org->tenantUsers()->with('user')->get();

// Admin 사용자만
$admins = $org->admins();

// Owner만 조회
$owners = $org->owners();
```

### 4) 역할 변경

```php
$tenantUser = TenantUser::where('user_id', 1)
    ->where('tenant_type', 'ORG')
    ->where('tenant_id', 1)
    ->first();

// Owner → Manager로 변경
$tenantUser->update(['role' => 'manager']);

// Activity Log 자동 기록됨
```

### 5) 역할 제거

```php
$tenantUser->delete();
// ON DELETE CASCADE로 연관 데이터 자동 정리
```

---

## 5. 테스트 실행 (5분)

```bash
# 전체 테스트
php artisan test

# 권한 시스템 테스트만
php artisan test --filter=Tenancy
php artisan test --filter=Auth

# Feature 테스트
php artisan test tests/Feature/Tenancy/TenantUserTest.php
```

---

## 6. 문제 해결

### Q1: "Class 'Spatie\Permission\Models\Role' not found" 오류

**원인**: Spatie 패키지 제거 후 코드에서 여전히 참조

**해결**:
```bash
# 1. 코드에서 Spatie 참조 검색
rg "Spatie\\\\Permission" app/

# 2. User 모델에서 HasRoles trait 제거
# app/Models/User.php
// use Spatie\Permission\Traits\HasRoles;  // 제거
```

### Q2: Admin이 테넌트 목록에서 테넌트를 볼 수 없음

**원인**: tenant_users 레코드 없음

**해결**:
```php
// Tinker에서 확인
php artisan tinker
>>> $user = User::find(1);
>>> $user->tenantUsers()->count();  // 0이면 역할 없음

// 역할 할당
>>> TenantUser::create([
    'user_id' => $user->id,
    'tenant_type' => 'ORG',
    'tenant_id' => 1,
    'role' => 'owner',
]);
```

### Q3: Customer가 Filament에 로그인됨

**원인**: canAccessPanel() 메서드가 Customer를 허용

**해결**:
```php
// app/Models/User.php
public function canAccessPanel(Panel $panel): bool
{
    // Customer는 모든 패널 접근 차단
    if ($this->user_type === 'customer') {
        return false;
    }

    // 기존 로직...
}
```

---

## 7. 다음 단계

### 개발자용
1. [data-model.md](./data-model.md) - 데이터 모델 상세 구조
2. [contracts/](./contracts/) - API 계약 문서
3. [tasks.md](./tasks.md) - 구현 작업 목록 (생성 예정)

### 관리자용
1. Filament Admin 패널에서 역할 관리 UI 사용
2. Activity Log에서 권한 변경 이력 확인
3. Nova Panel에서 글로벌 사용자 관리

---

## 추가 자원

### 공식 문서
- [Filament Tenancy 문서](https://filamentphp.com/docs/4.x/panels/tenancy)
- [Laravel Eloquent Relationships](https://laravel.com/docs/12.x/eloquent-relationships)
- [Firebase Authentication](https://firebase.google.com/docs/auth)

### 프로젝트 문서
- [헌장](../../.specify/memory/constitution.md) - 프로젝트 원칙
- [CLAUDE.md](../../CLAUDE.md) - 개발 가이드
- [docs/auth.md](../../docs/auth.md) - 인증 시스템 상세

---

**작성자**: Claude Code
**최종 업데이트**: 2025-10-20

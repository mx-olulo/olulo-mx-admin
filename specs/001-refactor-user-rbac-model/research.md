# 연구 및 기술 선택: 3티어 사용자 권한 모델 리팩토링

**날짜**: 2025-10-20
**관련 SPEC**: [spec.md](./spec.md)
**관련 계획**: [plan.md](./plan.md)

## 목적

Spatie Laravel Permission 패키지를 제거하고 자체 tenant_users 피벗 테이블 기반 권한 시스템으로 전환하기 위한 기술적 접근 방법 및 모범 사례를 연구합니다.

## 주요 연구 영역

### 1. Laravel 멀티테넌시 권한 패턴

**결정**: Filament Tenancy 기본 구조(`getTenants()`, `canAccessTenant()`) 활용 + 자체 피벗 테이블

**근거**:
- Filament v4는 자체 멀티테넌시 시스템을 제공하며, `FilamentUser`와 `HasTenants` 인터페이스가 필수
- Spatie Permissions의 `team_id` 개념은 Filament Tenancy와 충돌하며, morphMap 기반 폴리모픽 관계를 지원하지 않음
- tenant_users 피벗 테이블로 M:N 관계를 직접 관리하면 쿼리가 단순해지고 성능이 개선됨

**고려된 대안**:
1. **Spatie Permissions 유지 + 커스터마이징**: Rejected - 복잡도 증가, team_id 충돌 해결 불가
2. **Laravel Sanctum Abilities 사용**: Rejected - API 토큰 기반으로 세션 기반 Filament와 맞지 않음
3. **Bouncer 패키지 도입**: Rejected - 새로운 의존성 추가는 경량화 목표에 반함

**참고 자료**:
- [Filament Tenancy 공식 문서](https://filamentphp.com/docs/4.x/panels/tenancy)
- [Laravel Eloquent Polymorphic Relations](https://laravel.com/docs/12.x/eloquent-relationships#polymorphic-relationships)

---

### 2. 테넌트별 역할 관리 스키마

**결정**: `tenant_users` 피벗 테이블 구조
```sql
CREATE TABLE tenant_users (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    tenant_type VARCHAR(10) NOT NULL,  -- 'ORG', 'BRD', 'STR'
    tenant_id BIGINT NOT NULL,
    role VARCHAR(50) NOT NULL,         -- 'owner', 'manager', 'viewer'
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE KEY unique_user_tenant_role (user_id, tenant_type, tenant_id, role)
);
```

**근거**:
- `tenant_type` + `tenant_id`로 폴리모픽 관계 구현 (Laravel morphMap 활용)
- `role` 컬럼은 테넌트별 역할을 저장 (owner, manager, viewer 등)
- UNIQUE 제약으로 중복 역할 할당 방지
- ON DELETE CASCADE로 사용자 삭제 시 자동 정리

**고려된 대안**:
1. **별도 roles 테이블 + 피벗 테이블**: Rejected - 간단한 역할 체계에는 과도한 정규화
2. **JSON 컬럼에 역할 저장**: Rejected - 쿼리 성능 저하, 인덱싱 불가
3. **user_id + tenant_type + tenant_id를 복합 PK로 사용**: Rejected - role 변경 시 레코드 삭제/재생성 필요

**참고 자료**:
- [Laravel Pivot Tables](https://laravel.com/docs/12.x/eloquent-relationships#updating-many-to-many-relationships)
- [PostgreSQL Composite Indexes](https://www.postgresql.org/docs/15/indexes-multicolumn.html)

---

### 3. 글로벌 역할 관리 (Platform/System Admin)

**결정**: `users` 테이블에 `global_role` VARCHAR 컬럼 추가
```sql
ALTER TABLE users ADD COLUMN global_role VARCHAR(50) NULL;
-- 가능한 값: 'platform_admin', 'system_admin', NULL
```

**근거**:
- User 사용자는 테넌트와 무관하며, 단일 글로벌 역할만 보유
- 별도 테이블 생성보다 users 테이블 컬럼으로 충분히 단순함
- NULL 허용으로 Admin/Customer는 글로벌 역할 없음

**고려된 대안**:
1. **global_roles 별도 테이블**: Rejected - 1:1 관계에는 과도한 정규화
2. **tenant_users에 통합 (tenant_type='GLOBAL')**: Rejected - 테넌트 개념과 혼동
3. **boolean 플래그 (is_platform_admin, is_system_admin)**: Rejected - 확장성 부족

**참고 자료**:
- [Laravel Enum Casts](https://laravel.com/docs/12.x/eloquent-mutators#enum-casting)

---

### 4. Spatie Permissions 마이그레이션 전략

**결정**: 3단계 마이그레이션 스크립트
1. **데이터 추출**: 기존 `model_has_roles` → `tenant_users` 데이터 이관
2. **검증**: 이관 데이터 무결성 확인 (사용자별 역할 수 일치)
3. **정리**: Spatie 테이블 삭제 (`roles`, `permissions`, `model_has_roles`, `role_has_permissions`)

**근거**:
- 프로덕션 환경에서 무중단 마이그레이션 가능
- 데이터 손실 방지를 위한 검증 단계 필수
- 롤백 계획: 백업 테이블 유지 (migration 완료 후 1주일 후 삭제)

**마이그레이션 스크립트 로직**:
```php
// Phase 1: 데이터 이관
DB::table('model_has_roles')
    ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
    ->where('roles.scope_type', '!=', null)  // 테넌트 역할만
    ->get()
    ->each(function ($record) {
        DB::table('tenant_users')->insert([
            'user_id' => $record->model_id,
            'tenant_type' => $record->scope_type,
            'tenant_id' => $record->scope_ref_id,
            'role' => $record->name,
        ]);
    });

// Phase 2: 검증
$oldCount = DB::table('model_has_roles')->count();
$newCount = DB::table('tenant_users')->count();
if ($oldCount !== $newCount) {
    throw new \Exception("데이터 이관 불일치: old=$oldCount, new=$newCount");
}

// Phase 3: 정리 (검증 완료 후)
// Schema::dropIfExists('roles');
// Schema::dropIfExists('permissions');
// ...
```

**고려된 대안**:
1. **Spatie 테이블 즉시 삭제**: Rejected - 롤백 불가, 위험성 높음
2. **dual write (Spatie + tenant_users 동시 업데이트)**: Rejected - 복잡도 증가, 일관성 보장 어려움

**참고 자료**:
- [Laravel Migrations](https://laravel.com/docs/12.x/migrations)
- [Zero-Downtime Migrations](https://mattstauffer.com/blog/laravel-migrations-without-downtime/)

---

### 5. Policy 제거 및 User 모델 권한 체크 메서드

**결정**: User 모델에 권한 체크 메서드 직접 구현
```php
// app/Models/User.php

public function hasRoleForTenant(Model $tenant, string $role): bool
{
    return DB::table('tenant_users')
        ->where('user_id', $this->id)
        ->where('tenant_type', $tenant->getMorphClass())
        ->where('tenant_id', $tenant->id)
        ->where('role', $role)
        ->exists();
}

public function canManageTenant(Model $tenant): bool
{
    return $this->hasRoleForTenant($tenant, 'owner')
        || $this->hasRoleForTenant($tenant, 'manager');
}

public function canViewTenant(Model $tenant): bool
{
    return $this->canManageTenant($tenant)
        || $this->hasRoleForTenant($tenant, 'viewer');
}
```

**근거**:
- Policy 클래스는 복잡한 권한 체계에 필요하지만, 역할 기반 단순 체크에는 과도함
- User 모델에 메서드로 구현하면 Filament 리소스에서 직접 호출 가능
- 쿼리 최적화 용이 (Eager Loading, 캐싱 등)

**Filament 리소스 통합**:
```php
// app/Filament/Organization/Resources/BrandResource.php

public static function canViewAny(): bool
{
    return auth()->user()->canViewTenant(Filament::getTenant());
}

public static function canCreate(): bool
{
    return auth()->user()->canManageTenant(Filament::getTenant());
}
```

**고려된 대안**:
1. **Policy 클래스 유지**: Rejected - 경량화 목표에 반함, 불필요한 추상화
2. **Middleware로 권한 체크**: Rejected - Filament 리소스별 체크가 더 세밀함
3. **Gate 파사드 사용**: Rejected - User 모델 메서드가 더 명시적이고 테스트 용이

**참고 자료**:
- [Filament Authorization](https://filamentphp.com/docs/4.x/panels/resources#authorization)
- [Laravel Eloquent: Query Performance](https://laravel.com/docs/12.x/eloquent#optimizing-eloquent-performance)

---

### 6. Firebase 인증 분리 및 Customer 권한 제외

**결정**: Firebase 인증은 Customer 전용, guard 분리로 Filament와 독립 운영

**구현 전략**:
- **Guard 설정** (config/auth.php):
  ```php
  'guards' => [
      'web' => ['driver' => 'session'],        // Filament (Admin/User)
      'firebase' => ['driver' => 'firebase'],  // Customer API
  ],
  ```
- **Middleware 분리**:
  - Filament: `auth:web` (세션 기반)
  - Customer API: `auth:firebase` (JWT 토큰 기반)
- **User 모델 구분**:
  - `firebase_uid` 존재 → Customer (Filament 접근 차단)
  - `firebase_uid` NULL → Admin/User (Filament 접근 허용)

**근거**:
- Firebase는 stateless 인증 (JWT), Filament는 stateful 세션 (Sanctum)
- guard 분리로 인증 로직 충돌 방지
- Customer는 권한 시스템 제외 (Firebase Custom Claims로 관리)

**고려된 대안**:
1. **Customer도 tenant_users에 포함**: Rejected - Customer는 테넌트 개념 없음
2. **단일 guard 사용**: Rejected - Firebase와 세션 인증 혼재 시 예측 불가능한 동작

**참고 자료**:
- [Laravel Multi-Authentication](https://laravel.com/docs/12.x/authentication#authentication-quickstart)
- [Kreait Firebase PHP](https://firebase-php.readthedocs.io/en/stable/)

---

## 기술 스택 최종 선택

| 영역 | 선택된 기술 | 근거 |
|------|-----------|------|
| 권한 스키마 | tenant_users 피벗 테이블 | Filament Tenancy 호환, 쿼리 단순화 |
| 글로벌 역할 | users.global_role 컬럼 | 1:1 관계에 적합한 단순 구조 |
| 권한 체크 | User 모델 메서드 | Policy 제거, 경량화 달성 |
| Firebase 분리 | guard 분리 (web vs firebase) | stateless/stateful 인증 충돌 방지 |
| 마이그레이션 | 3단계 (이관 → 검증 → 정리) | 무중단 마이그레이션, 롤백 가능 |

---

## 성능 최적화 고려사항

1. **Eager Loading**: getTenants() 호출 시 scopeable 관계 미리 로드
   ```php
   TenantUser::with('tenant')->where('user_id', $userId)->get();
   ```

2. **인덱스 전략**:
   - `tenant_users(user_id, tenant_type, tenant_id)` 복합 인덱스
   - `users(global_role)` 인덱스 (Platform/System 필터)

3. **캐싱**:
   - 사용자별 테넌트 목록 캐싱 (Redis, TTL 5분)
   - 권한 체크 결과 요청 범위 캐싱 (메모리)

---

## 보안 고려사항

1. **역할 검증**: Enum 클래스로 허용된 역할만 저장
2. **CASCADE 설정**: 사용자 삭제 시 tenant_users 자동 정리
3. **감사 로그**: Spatie Activity Log로 역할 변경 기록
4. **Firebase Claims 검증**: Customer API는 JWT 검증 미들웨어 필수

---

## 테스트 전략

1. **Unit Tests**: User 모델 권한 체크 메서드
2. **Feature Tests**: Admin 멀티테넌트 접근, User 글로벌 접근
3. **Integration Tests**: Filament 패널 접근 제어
4. **E2E Tests**: Laravel Dusk로 브라우저 테스트

---

## 위험 완화 계획

| 위험 | 영향도 | 완화 전략 |
|------|-------|---------|
| 데이터 마이그레이션 실패 | 높음 | 백업 테이블 유지, 검증 단계 필수 |
| Filament 접근 제어 오류 | 높음 | 기존 테스트 케이스 전면 수정 및 검증 |
| Customer 인증 중단 | 치명적 | Firebase guard 분리로 영향 최소화 |
| 성능 저하 | 중간 | 인덱스 추가, Eager Loading, 캐싱 |

---

**연구 완료일**: 2025-10-20
**승인자**: [구현 시 검토 필요]

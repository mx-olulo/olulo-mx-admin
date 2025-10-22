---
description: "3티어 사용자 권한 모델 리팩토링 구현 작업 목록"
---

# 작업 목록: 3티어 사용자 권한 모델 리팩토링

**입력**: `/specs/001-refactor-user-rbac-model/`의 설계 문서
**사전 요구사항**: plan.md, spec.md, research.md, data-model.md, contracts/tenant-user-management.md, quickstart.md

**구성**: 작업은 각 스토리의 독립적인 구현 및 테스트를 가능하게 하기 위해 사용자 스토리별로 그룹화됩니다.

## 형식: `[ID] [P?] [Story] 설명`
- **[P]**: 병렬 실행 가능 (다른 파일, 종속성 없음)
- **[Story]**: 이 작업이 속한 사용자 스토리 (US1, US2, US3, US4)
- 설명에 정확한 파일 경로 포함

## 경로 규칙
- Laravel Monolithic 구조: 저장소 루트의 `app/`, `database/`, `tests/`
- Filament Admin 패널: `app/Filament/Organization/`, `app/Filament/Brand/`, `app/Filament/Store/`
- Laravel Nova 패널: `app/Nova/Platform/`, `app/Nova/System/`

---

## Phase 1: 설정 (공유 인프라)

**목적**: 프로젝트 초기화 및 기본 구조

- [ ] T001 `.moai/specs/001-refactor-user-rbac-model/` 디렉토리 구조 검증
- [ ] T002 [P] composer.json에서 `spatie/laravel-permission` 제거 준비 (백업 생성)
- [ ] T003 [P] Laravel Pint, PHPStan, Rector 설정 파일 검토

---

## Phase 2: 기초 작업 (차단 사전 요구사항)

**목적**: 모든 사용자 스토리를 구현하기 전에 완료되어야 하는 핵심 인프라

**⚠️ 중요**: 이 단계가 완료될 때까지 사용자 스토리 작업을 시작할 수 없음

### 데이터베이스 스키마 및 마이그레이션

- [ ] T004 `database/migrations/YYYY_MM_DD_create_tenant_users_table.php` 생성
  - 컬럼: `id`, `user_id` (FK), `tenant_type` (ORG/BRD/STR), `tenant_id`, `role` (owner/manager/viewer), `created_at`, `updated_at`
  - 인덱스: `idx_tenant (tenant_type, tenant_id)`, `idx_user_role (user_id, role)`
  - UNIQUE 제약: `unique_user_tenant (user_id, tenant_type, tenant_id)`
  - FK: `user_id REFERENCES users(id) ON DELETE CASCADE`

- [ ] T005 `database/migrations/YYYY_MM_DD_add_user_type_to_users_table.php` 생성
  - 컬럼 추가: `user_type` VARCHAR(20) NOT NULL DEFAULT 'admin' (admin/user/customer)
  - 컬럼 추가: `global_role` VARCHAR(50) NULL (platform_admin/system_admin/NULL)
  - 인덱스: `idx_user_type (user_type)`, `idx_global_role (global_role)`

- [ ] T006 `database/migrations/YYYY_MM_DD_migrate_roles_to_tenant_users.php` 생성 (데이터 마이그레이션)
  - Phase 1: Spatie `model_has_roles` → `tenant_users` 데이터 이관 로직
  - Phase 2: 데이터 무결성 검증 (레코드 수 일치 확인)
  - Phase 3: Spatie 테이블 삭제는 주석 처리 (검증 후 수동 실행)

### 모델 및 Enum 생성

- [ ] T007 [P] `app/Enums/UserType.php` 생성
  - Enum 값: `Admin`, `User`, `Customer`
  - 메서드: `isAdmin()`, `isUser()`, `isCustomer()`

- [ ] T008 [P] `app/Enums/TenantRole.php` 생성
  - Enum 값: `Owner`, `Manager`, `Viewer`
  - 메서드: `canManage()`, `canView()`

- [ ] T009 [P] `app/Models/TenantUser.php` 생성
  - Eloquent 모델 (피벗 테이블)
  - 관계: `belongsTo(User)`, `morphTo('tenant')`
  - Activity Log 설정: `logOnly(['user_id', 'tenant_type', 'tenant_id', 'role'])`
  - Fillable: `['user_id', 'tenant_type', 'tenant_id', 'role']`

- [ ] T010 `app/Models/User.php` 수정
  - `HasRoles` trait 제거 (Spatie)
  - 관계 추가: `hasMany(TenantUser)`
  - 메서드 추가: `getTenants(string $tenantType): Collection`
  - 메서드 추가: `getRoleForTenant(Model $tenant): ?string`
  - 메서드 추가: `hasRoleForTenant(Model $tenant, string $role): bool`
  - 메서드 추가: `canManageTenant(Model $tenant): bool` (owner || manager)
  - 메서드 추가: `canViewTenant(Model $tenant): bool` (owner || manager || viewer)
  - 메서드 추가: `hasGlobalRole(string $role): bool`
  - 메서드 추가: `isFirebaseUser(): bool`
  - Cast 추가: `'user_type' => UserType::class`

- [ ] T011 [P] `app/Models/Organization.php` 수정
  - 관계 추가: `hasMany(TenantUser, 'tenant_id')->where('tenant_type', 'ORG')`
  - 메서드 추가: `admins(): Collection` (tenantUsers with user)
  - 메서드 추가: `owners(): Collection` (role='owner')

- [ ] T012 [P] `app/Models/Brand.php` 수정
  - 관계 추가: `hasMany(TenantUser, 'tenant_id')->where('tenant_type', 'BRD')`
  - 메서드 추가: `admins(): Collection`
  - 메서드 추가: `owners(): Collection`

- [ ] T013 [P] `app/Models/Store.php` 수정
  - 관계 추가: `hasMany(TenantUser, 'tenant_id')->where('tenant_type', 'STR')`
  - 메서드 추가: `admins(): Collection`
  - 메서드 추가: `owners(): Collection`

### Seeder 생성

- [ ] T014 [P] `database/seeders/TenantRoleSeeder.php` 생성
  - Owner, Manager, Viewer 역할 시드 (테스트용 TenantUser 레코드)

- [ ] T015 [P] `database/seeders/GlobalRoleSeeder.php` 생성
  - Platform Admin, System Admin 역할 시드 (테스트용 User 레코드)

### Laravel MorphMap 설정

- [ ] T016 `app/Providers/AppServiceProvider.php` 수정
  - `Relation::morphMap()` 추가: `['ORG' => Organization::class, 'BRD' => Brand::class, 'STR' => Store::class]`

**체크포인트**: 기초 준비 완료 - 마이그레이션 실행 (`php artisan migrate`) 및 시더 실행 성공 확인

---

## Phase 3: 사용자 스토리 1 - Admin 멀티테넌트 접근 (우선순위: P1) 🎯 MVP

**목표**: Admin 사용자가 여러 Organization/Brand/Store에 동시 접근하고 각 테넌트별로 다른 역할 수행

**독립 테스트**: Admin 사용자를 생성하고 여러 테넌트에 역할 할당 후, Filament 패널에서 테넌트 선택 및 권한별 CRUD 작업 확인

### 사용자 스토리 1 테스트 (TDD 필수)

**참고: 이 테스트를 먼저 작성하고, 구현 전에 실패하는지 확인하세요**

- [ ] T017 [P] [US1] `tests/Feature/Tenancy/TenantUserTest.php` 생성
  - 테스트: Admin이 여러 Organization에 서로 다른 역할로 할당됨
  - 테스트: `getTenants('ORG')` 메서드가 Admin의 모든 Organization 반환
  - 테스트: `hasRoleForTenant($org, 'owner')` 메서드 검증
  - 테스트: `canManageTenant($org)` 메서드 검증 (owner, manager만 true)
  - 테스트: `canViewTenant($org)` 메서드 검증 (모든 역할 true)

- [ ] T018 [P] [US1] `tests/Feature/Tenancy/MultiTenantRoleTest.php` 생성
  - 테스트: Admin이 Brand, Store에도 각각 역할 보유 시 접근 가능
  - 테스트: 테넌트별 역할이 독립적으로 작동 (Organization A owner, B viewer)
  - 테스트: tenant_users UNIQUE 제약 검증 (중복 역할 할당 실패)

### 사용자 스토리 1 구현

- [ ] T019 [US1] `app/Filament/Organization/Resources/BrandResource.php` 수정
  - `canViewAny()`: `auth()->user()->canViewTenant(Filament::getTenant())` 사용
  - `canCreate()`: `auth()->user()->canManageTenant(Filament::getTenant())` 사용
  - `canEdit()`: `auth()->user()->canManageTenant(Filament::getTenant())` 사용
  - `canDelete()`: `auth()->user()->hasRoleForTenant(Filament::getTenant(), 'owner')` 사용

- [ ] T020 [US1] `app/Filament/Brand/Resources/StoreResource.php` 수정
  - 동일한 권한 체크 로직 적용 (canViewAny, canCreate, canEdit, canDelete)

- [ ] T021 [US1] `app/Filament/Store/Resources` 내 모든 리소스 수정
  - 동일한 권한 체크 로직 일괄 적용

- [ ] T022 [US1] Filament `getTenants()` 메서드 구현
  - User 모델에 `getTenants()` 메서드가 Filament Tenancy와 통합되도록 검증
  - Organization, Brand, Store 패널 각각에서 테넌트 선택 화면 동작 확인

- [ ] T023 [US1] Filament `canAccessTenant()` 메서드 구현
  - User 모델에 `canAccessTenant(Model $tenant): bool` 메서드 추가
  - `hasRoleForTenant($tenant, ...)` 로직 활용

**체크포인트**: 이 시점에서 Admin이 Filament 패널에서 여러 테넌트를 선택하고 역할별 권한으로 작업 수행 가능해야 함

---

## Phase 4: 사용자 스토리 2 - User 글로벌 접근 제한 (우선순위: P1)

**목표**: User 사용자는 Platform/System 패널만 접근 가능하며, Organization/Brand/Store 패널 접근 차단

**독립 테스트**: platform_admin 역할 User 생성 후 Platform 패널 접근 가능, Organization 패널 접근 불가 확인

### 사용자 스토리 2 테스트 (TDD 필수)

- [ ] T024 [P] [US2] `tests/Feature/Auth/UserGlobalAccessTest.php` 생성
  - 테스트: User(platform_admin)가 Platform 패널 접근 가능
  - 테스트: User(system_admin)가 System 패널 접근 가능
  - 테스트: User가 Organization/Brand/Store 패널 접근 시 403 반환
  - 테스트: `hasGlobalRole('platform_admin')` 메서드 검증

### 사용자 스토리 2 구현

- [ ] T025 [US2] `app/Models/User.php` 수정
  - `canAccessPanel(Panel $panel): bool` 메서드 수정
  - Platform 패널: `user_type === 'user' && global_role === 'platform_admin'`
  - System 패널: `user_type === 'user' && global_role === 'system_admin'`
  - Organization/Brand/Store 패널: `user_type === 'admin'`
  - Customer는 모든 패널 차단: `user_type === 'customer' → false`

- [ ] T026 [US2] `app/Nova/Platform/` 리소스 수정
  - `authorizedToViewAny()`: `$request->user()->hasGlobalRole('platform_admin')` 사용

- [ ] T027 [US2] `app/Nova/System/` 리소스 수정
  - `authorizedToViewAny()`: `$request->user()->hasGlobalRole('system_admin')` 사용

**체크포인트**: 이 시점에서 User가 Platform/System 패널에만 접근 가능하고 테넌트 패널 접근이 차단되어야 함

---

## Phase 5: 사용자 스토리 3 - Customer Firebase 인증 유지 (우선순위: P2)

**목표**: Customer의 Firebase 인증 흐름을 유지하되, Admin/User 권한 모델과 독립적으로 작동

**독립 테스트**: Firebase UID Customer 생성 후 API 요청 성공, Filament 패널 접근 실패 확인

### 사용자 스토리 3 테스트 (TDD 필수)

- [ ] T028 [P] [US3] `tests/Feature/Auth/CustomerFirebaseAuthTest.php` 수정
  - 테스트: Customer가 Firebase JWT로 API 요청 성공
  - 테스트: Customer가 Filament Admin 패널 접근 시 401/403 반환
  - 테스트: `isFirebaseUser()` 메서드 검증 (user_type='customer' && firebase_uid 존재)

### 사용자 스토리 3 구현

- [ ] T029 [US3] `app/Models/User.php` 수정
  - `canAccessPanel()` 메서드에 Customer 차단 로직 추가 (위 T025에서 구현됨)
  - `isFirebaseUser()` 메서드 검증 (이미 기초 작업 T010에서 구현됨)

- [ ] T030 [US3] Firebase 인증 미들웨어 검증
  - `app/Http/Middleware/FirebaseAuth.php` 존재 확인
  - Customer API 라우트에 `auth:firebase` guard 적용 확인

- [ ] T031 [US3] Customer API 라우트 검증
  - `routes/api.php`에서 Customer 전용 라우트가 `auth:firebase` guard 사용 확인
  - Admin/User API는 `auth:sanctum` guard 사용 확인

**체크포인트**: 이 시점에서 Customer가 Firebase 인증으로 API 접근 가능하나 Filament 패널은 차단되어야 함

---

## Phase 6: 사용자 스토리 4 - Spatie Permissions 제거 및 경량화 (우선순위: P1)

**목표**: Spatie Permissions 패키지를 완전히 제거하고 자체 권한 모델로 전환

**독립 테스트**: Spatie 의존성 제거 후 composer update 성공, 모든 권한 체크가 새로운 메서드로 작동 확인

### 사용자 스토리 4 테스트 (TDD 필수)

- [ ] T032 [P] [US4] `tests/Unit/Models/UserTest.php` 수정
  - Spatie 관련 테스트 제거 (`hasPermissionTo`, `assignRole` 등)
  - 새 메서드 테스트 추가: `getTenants()`, `hasRoleForTenant()`, `canManageTenant()`

### 사용자 스토리 4 구현

- [ ] T033 [US4] Policy 클래스 제거
  - `app/Policies/OrganizationPolicy.php` 삭제
  - `app/Policies/BrandPolicy.php` 삭제
  - `app/Policies/StorePolicy.php` 삭제
  - `app/Providers/AuthServiceProvider.php`에서 Policy 등록 제거

- [ ] T034 [US4] Spatie Permissions 코드 검색 및 제거
  - `rg "hasPermissionTo|assignRole|syncRoles|hasRole" app/` 실행
  - 모든 Spatie 메서드 호출을 새로운 User 모델 메서드로 대체
  - `use Spatie\Permission\Traits\HasRoles` 제거 (이미 T010에서 구현됨)

- [ ] T035 [US4] composer.json 수정
  - `"spatie/laravel-permission": "^6.10"` 라인 제거
  - `composer update` 실행 및 의존성 크기 측정 (15% 감소 목표)

- [ ] T036 [US4] Spatie 마이그레이션 파일 제거
  - `database/migrations/2025_09_26_152355_create_permission_tables.php` 삭제
  - 또는 git history 보존 위해 주석 처리

- [ ] T037 [US4] Activity Log 검증
  - TenantUser 모델에서 Activity Log가 역할 변경 시 자동 기록되는지 확인
  - 테스트: TenantUser 생성/수정/삭제 시 activity_log 테이블 레코드 생성

**체크포인트**: 이 시점에서 Spatie Permissions 완전히 제거되고 모든 권한 체크가 자체 메서드로 작동해야 함

---

## Phase 7: 마무리 & 횡단 관심사

**목적**: 여러 사용자 스토리에 영향을 주는 개선사항 및 문서화

- [ ] T038 [P] `docs/auth.md` 업데이트
  - 3티어 사용자 모델 설명 추가
  - Spatie 제거 후 새로운 권한 시스템 문서화

- [ ] T039 [P] `README.md` 업데이트
  - 새로운 권한 모델 간단 설명 추가
  - quickstart.md 링크 추가

- [ ] T040 코드 정리 및 리팩토링
  - Laravel Pint 실행: `./vendor/bin/pint`
  - PHPStan 실행: `./vendor/bin/phpstan analyse --level=5 app/`
  - Rector 실행: `./vendor/bin/rector process app/ --dry-run`

- [ ] T041 성능 최적화
  - `getTenants()` 메서드에 Eager Loading 추가: `with('tenant')`
  - 인덱스 검증: `tenant_users` 테이블 인덱스 실행 계획 확인

- [ ] T042 [P] `tests/Feature/Tenancy/PerformanceTest.php` 생성
  - 테스트: Admin 테넌트 전환 응답 시간 <500ms
  - 테스트: 권한 체크 쿼리 수 ≤2개

- [ ] T043 보안 검증
  - Customer가 Admin 패널 URL 직접 입력 시 차단 확인
  - User가 Organization 패널 접근 시 차단 확인
  - CSRF 토큰 검증 확인

- [ ] T044 quickstart.md 검증 실행
  - `specs/001-refactor-user-rbac-model/quickstart.md` 단계별 실행
  - 로컬 환경 설정 (10분) 완료 확인
  - Admin/User/Customer 사용 예시 모두 동작 확인

- [ ] T045 마이그레이션 검증
  - `database/migrations/YYYY_MM_DD_migrate_roles_to_tenant_users.php` 실행
  - Phase 2 데이터 무결성 검증 통과 확인
  - Phase 3 Spatie 테이블 삭제 주석 해제 및 실행

---

## 의존성 & 실행 순서

### 단계 의존성

- **설정 (Phase 1)**: 의존성 없음 - 즉시 시작 가능
- **기초 작업 (Phase 2)**: 설정 완료에 의존 - 모든 사용자 스토리 차단
- **사용자 스토리 (Phase 3-6)**: 모두 기초 작업 단계 완료에 의존
  - US1 (Admin 멀티테넌트): 기초 작업 후 즉시 시작 가능 - **MVP 최우선**
  - US2 (User 글로벌): 기초 작업 후 즉시 시작 가능 - US1과 병렬 가능
  - US3 (Customer Firebase): 기초 작업 후 즉시 시작 가능 - US1/US2와 병렬 가능
  - US4 (Spatie 제거): US1, US2, US3 완료 후 실행 권장 (안전성)
- **마무리 (Phase 7)**: 모든 사용자 스토리 완료에 의존

### 사용자 스토리 의존성

- **사용자 스토리 1 (P1 - MVP)**: 기초 작업 (Phase 2) 완료 후 시작 가능 - 다른 스토리에 대한 의존성 없음
- **사용자 스토리 2 (P1)**: 기초 작업 (Phase 2) 완료 후 시작 가능 - US1과 병렬 실행 가능
- **사용자 스토리 3 (P2)**: 기초 작업 (Phase 2) 완료 후 시작 가능 - US1/US2와 병렬 실행 가능
- **사용자 스토리 4 (P1)**: US1, US2, US3 완료 후 실행 권장 (Spatie 제거는 모든 코드 검증 후 안전)

### 각 사용자 스토리 내에서

- 테스트는 반드시 먼저 작성되고 구현 전에 실패해야 함 (TDD)
- 모델 및 Enum은 서비스보다 먼저
- Filament/Nova 리소스 수정은 User 모델 메서드 구현 후
- Policy 제거는 새 권한 체크 메서드 구현 및 테스트 통과 후

### 병렬 처리 기회

- [P]로 표시된 모든 설정 작업은 병렬로 실행 가능 (T002, T003)
- [P]로 표시된 모든 기초 작업은 병렬로 실행 가능 (T007, T008, T009, T011, T012, T013, T014, T015)
- 기초 작업 단계 완료 후, US1, US2, US3는 병렬로 시작 가능 (팀 역량이 허용하는 경우)
- 각 스토리의 테스트 파일은 병렬로 작성 가능 (T017, T018, T024, T028, T032)
- 마무리 단계의 문서 작업은 병렬 가능 (T038, T039, T042)

---

## 병렬 처리 예시

### 기초 작업 (Phase 2)에서 병렬 실행 가능

```bash
# 동시 작업 가능:
T007: app/Enums/UserType.php 생성
T008: app/Enums/TenantRole.php 생성
T009: app/Models/TenantUser.php 생성
T011: app/Models/Organization.php 수정
T012: app/Models/Brand.php 수정
T013: app/Models/Store.php 수정
T014: database/seeders/TenantRoleSeeder.php 생성
T015: database/seeders/GlobalRoleSeeder.php 생성
```

### 사용자 스토리 테스트 병렬 실행

```bash
# US1의 모든 테스트를 함께 시작:
T017: tests/Feature/Tenancy/TenantUserTest.php
T018: tests/Feature/Tenancy/MultiTenantRoleTest.php
```

---

## 구현 전략

### MVP 우선 (사용자 스토리 1만)

1. Phase 1 완료: 설정
2. Phase 2 완료: 기초 작업 (중요 - 모든 스토리 차단)
3. Phase 3 완료: 사용자 스토리 1 (Admin 멀티테넌트 접근)
4. **중지 및 검증**: 사용자 스토리 1을 독립적으로 테스트
5. 준비되면 배포/데모

### 점진적 전달

1. 설정 + 기초 작업 완료 → 기초 준비 완료
2. 사용자 스토리 1 추가 → 독립적으로 테스트 → 배포/데모 (MVP!)
3. 사용자 스토리 2 추가 → 독립적으로 테스트 → 배포/데모
4. 사용자 스토리 3 추가 → 독립적으로 테스트 → 배포/데모
5. 사용자 스토리 4 완료 → Spatie 완전 제거 → 배포
6. 각 스토리는 이전 스토리를 깨지 않고 가치 추가

### 병렬 팀 전략

여러 개발자가 있는 경우:

1. 팀이 설정 + 기초 작업을 함께 완료
2. 기초 작업 완료 후:
   - 개발자 A: 사용자 스토리 1 (Admin 멀티테넌트)
   - 개발자 B: 사용자 스토리 2 (User 글로벌 접근)
   - 개발자 C: 사용자 스토리 3 (Customer Firebase)
3. 모든 스토리 완료 후 개발자 D: 사용자 스토리 4 (Spatie 제거)
4. 스토리가 독립적으로 완료되고 통합됨

---

## 참고사항

- [P] 작업 = 다른 파일, 의존성 없음
- [Story] 레이블은 추적 가능성을 위해 작업을 특정 사용자 스토리에 매핑
- 각 사용자 스토리는 독립적으로 완료 및 테스트 가능해야 함
- **TDD 필수**: 구현 전에 테스트 실패 확인
- 각 작업 또는 논리적 그룹 후 커밋
- 독립적으로 스토리를 검증하기 위해 체크포인트에서 중지
- 피해야 할 것:
  - 모호한 작업
  - 동일 파일 충돌 (병렬 작업 시)
  - 독립성을 깨는 스토리 간 의존성
  - Spatie 제거 전 충분한 테스트 없이 진행
  - Customer Firebase 인증 중단 위험

---

## 성공 기준 검증 체크리스트

구현 완료 후 아래 성공 기준을 모두 통과해야 합니다 (spec.md 기준):

- [ ] **SC-001**: Admin 사용자가 5개 이상의 서로 다른 테넌트에 접근하고, 각 테넌트에서 역할에 맞는 권한으로 작업 수행 가능 (Feature 테스트 통과)
- [ ] **SC-002**: User 사용자가 Platform 또는 System 패널 접근 가능하나, Organization/Brand/Store 패널 접근 시 100% 차단 (Feature 테스트 통과)
- [ ] **SC-003**: Customer 사용자가 Firebase 인증 API로 로그인 및 API 요청 시 95% 이상 성공률 유지 (기존 테스트 통과)
- [ ] **SC-004**: Spatie Permissions 의존성 제거 후 composer 의존성 크기 최소 15% 감소, 권한 체크 쿼리 평균 2개 이하
- [ ] **SC-005**: 모든 Policy 클래스 제거 후 코드베이스 LOC 최소 300줄 감소
- [ ] **SC-006**: 기존 권한 관련 테스트 케이스가 새로운 권한 모델로 마이그레이션되어 100% 통과
- [ ] **SC-007**: 테넌트 권한 검증 성능이 기존 Spatie 기반 대비 50% 이상 개선 (DB 쿼리 수 감소)
- [ ] **SC-008**: Admin이 테넌트를 변경할 때 평균 응답 시간 500ms 이하 유지

---

**작성일**: 2025-10-20
**최종 업데이트**: 2025-10-20

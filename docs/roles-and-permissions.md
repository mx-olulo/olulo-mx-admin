# Roles and Permissions - 역할 매핑 테이블

> 본 문서는 역할 체계의 개요를 제공합니다. 상세한 구현은 [rbac-system.md](./rbac-system.md)를 참조하세요.

**최종 업데이트**: 2025-10-23
**상태**: ✅ TenantUser 기반 RBAC 구현 완료

---

## 역할 체계 개요

olulo-mx-admin은 **3-Tier 사용자 타입 시스템**과 **3가지 테넌트 역할**을 조합하여 멀티테넌트 권한을 관리합니다.

### 사용자 타입 (UserType Enum)

| 타입 | 설명 | 접근 범위 | 역할 기반 |
|------|------|----------|----------|
| **Admin** | 테넌트 관리자 | Organization/Brand/Store 패널 | `tenant_users` 피벗 테이블 |
| **User** | 글로벌 운영자 | Platform/System 패널 | `global_role` 필드 |
| **Customer** | 고객앱 사용자 | Firebase 인증만, 패널 접근 불가 | 없음 |

### 테넌트 역할 (TenantRole Enum)

| 역할 | 권한 | 설명 |
|------|------|------|
| **Owner** | 모든 권한 (생성, 수정, 삭제, 조회) | 테넌트 소유자 |
| **Manager** | 관리 권한 (생성, 수정, 조회) | 테넌트 관리자 |
| **Viewer** | 읽기 전용 (조회만) | 읽기 전용 사용자 |

### 글로벌 역할 (global_role 필드)

| 역할 | 접근 패널 | 사용자 타입 |
|------|----------|----------|
| **platform_admin** | Platform 패널 | User |
| **system_admin** | System 패널 | User |

---

## 역할 매핑

### Admin 타입 - 테넌트 역할 조합

Admin 타입 사용자는 `tenant_users` 피벗 테이블을 통해 여러 테넌트에 다양한 역할로 참여할 수 있습니다.

**예시**:
```php
// 사용자 A
- Organization #1: Owner
- Organization #2: Manager
- Store #5: Viewer
- Brand #3: Manager
```

### User 타입 - 글로벌 역할

User 타입 사용자는 `global_role` 필드로 Platform 또는 System 패널에 접근합니다.

**예시**:
```php
// 사용자 B (User 타입)
- global_role: 'platform_admin'
- 접근 가능: Platform 패널만
```

### Customer 타입 - 패널 접근 불가

Customer 타입 사용자는 Firebase 인증만 사용하며, Filament 패널에 접근할 수 없습니다.

---

## 레거시 역할 분류 (참고용)

**기존 6개 역할** → **현재 3-Tier 시스템으로 대체**

| 레거시 역할 | 현재 시스템 매핑 |
|----------|---------------|
| `admin` | ❌ 제거 → User 타입 + global_role: 'platform_admin' |
| `org_admin` | ✅ Admin 타입 + Organization Owner/Manager |
| `store_owner` | ✅ Admin 타입 + Store Owner |
| `store_manager` | ✅ Admin 타입 + Store Manager |
| `staff` | ✅ Admin 타입 + Store Viewer |
| `customer` | ✅ Customer 타입 (Firebase 인증만) |

---

## 접근 제어 원칙

### Filament 패널 접근

| 패널 | 사용자 타입 | 확인 방법 |
|------|----------|----------|
| Platform | User | `global_role === 'platform_admin'` |
| System | User | `global_role === 'system_admin'` |
| Organization | Admin | `tenant_users` 테이블에 Organization 멤버십 존재 |
| Brand | Admin | `tenant_users` 테이블에 Brand 멤버십 존재 |
| Store | Admin | `tenant_users` 테이블에 Store 멤버십 존재 |

### API/화면 접근

- **고객 API**: `auth:sanctum` + Customer 타입 확인
- **관리자 API**: `auth:sanctum` + Admin/User 타입 + 테넌트 역할 확인

---

## 권한 확인 메서드

### Fluent API (메서드 체이닝)

```php
// 테넌트 관리 권한 확인
$user->tenant($organization)->canManage();  // bool

// 특정 역할 확인
$user->tenant($store)->isOwner();     // bool
$user->tenant($brand)->isManager();   // bool
$user->tenant($store)->isViewer();    // bool

// 조회 권한 확인
$user->tenant($organization)->canView();  // bool
```

### 직접 호출 메서드

```php
// 역할 확인
$user->hasRoleForTenant($organization, TenantRole::OWNER);

// 권한 확인
$user->canManageTenant($store);  // owner 또는 manager
$user->canViewTenant($brand);    // 모든 역할

// 글로벌 역할 확인
$user->hasGlobalRole('platform_admin');
```

**상세 API 문서**: [rbac-system.md#권한-확인-api](./rbac-system.md#권한-확인-api)

---

## 시스템 연동

### User 모델

- ✅ `HasTenantPermissions` Trait 적용
- ✅ `HasTenants` 인터페이스 구현 (Filament Tenancy)
- ✅ `canAccessPanel()` 메서드 (패널 접근 권한)
- ✅ `getTenants()` 메서드 (테넌트 목록)
- ✅ `canAccessTenant()` 메서드 (테넌트 접근 권한)

### TenantUser 피벗 모델

- ✅ Polymorphic 관계 (Organization/Brand/Store)
- ✅ Spatie Activity Log 통합
- ✅ `user_id`, `tenant_type`, `tenant_id`, `role` 필드

### Enum 정의

- ✅ `UserType`: admin|user|customer
- ✅ `TenantRole`: owner|manager|viewer
- ✅ `ScopeType`: PLATFORM|SYSTEM|ORG|BRD|STR

---

## 관련 문서

- **[rbac-system.md](./rbac-system.md)**: TenantUser 기반 RBAC 상세 구현
- **[auth.md](./auth.md)**: Firebase + Sanctum 인증 시스템
- **[auth/redirect.md](./auth/redirect.md)**: 지능형 테넌트 리다이렉트
- **[models/tables/tenant_users.md](./models/tables/tenant_users.md)**: TenantUser 테이블 스키마

---

## 마이그레이션 노트

### Spatie Permission 제거 (2025-10-22)

**제거 이유**:
- `team_id` 기반 접근이 단일 계층만 지원
- Polymorphic 관계 미지원
- 복잡한 설정 (Role 모델 확장, 미들웨어 3개)

**대체 방안**:
- ✅ TenantUser 피벗 모델로 Polymorphic M:N 관계 구현
- ✅ Enum 기반 타입 안전성
- ✅ Fluent API로 가독성 향상
- ✅ 코드 복잡도 30% 감소

**영향받는 코드**:
- ❌ `Spatie\Permission\Traits\HasRoles` 제거
- ❌ `SetSpatieTeamId` 미들웨어 제거
- ✅ `HasTenantPermissions` Trait 추가
- ✅ `TenantAccessor` 클래스 추가

---

**최종 검토**: 2025-10-23
**작성자**: @Alfred

---
id: BRAND-STORE-MGMT-001
version: 0.0.1
status: draft
created: 2025-10-19
updated: 2025-10-19
author: @Goos
priority: high
category: feature
labels:
  - multi-tenant
  - filament
  - brand
  - store
  - hierarchy
depends_on:
  - I18N-001
  - TENANCY-AUTHZ-001
scope:
  packages:
    - app/Filament/Organization/Resources
    - app/Filament/Brand/Resources
    - app/Models
  files:
    - app/Models/Brand.php
    - app/Models/Store.php
    - app/Policies/BrandPolicy.php
    - app/Policies/StorePolicy.php
---

# @SPEC:BRAND-STORE-MGMT-001: Filament 기반 브랜드/매장 관리 체계

## HISTORY

### v0.0.1 (2025-10-19)
- **INITIAL**: Filament 기반 브랜드/매장 관리 체계 명세 작성
- **AUTHOR**: @Goos
- **SECTIONS**: 전체 EARS 요구사항, 데이터베이스 설계, Filament 리소스 구조
- **REASON**: Organization/Brand 패널에서 하위 엔티티(Brand/Store) 생성 및 관리 기능 구현

---

## 개요

### 목적

Organization과 Brand가 각각 하위 엔티티(Brand, Store)를 **자체 패널 내에서** 생성하고 관리할 수 있는 Filament 리소스를 구축합니다.

### 핵심 요구사항

1. **OrganizationPanel**: Brand 생성/관리
2. **BrandPanel**: Store 생성/관리
3. **relationship_type Enum**: 직영(직접 운영) vs 입점(계약 관계) 구분
4. **Soft Delete 지원**: 삭제된 엔티티 복구 가능
5. **권한 기반 접근 제어**: Policy 기반 권한 관리

---

## @SPEC:BRAND-STORE-MGMT-001 EARS 요구사항

### Ubiquitous Requirements (기본 기능)

1. **OrganizationPanel은 Brand 관리 리소스를 제공해야 한다**
   - BrandResource: Brand 생성, 조회, 수정, 삭제
   - Form 스키마: name, description, logo, is_active, relationship_type
   - Table 스키마: 브랜드명, 관계 유형, 활성 상태, 소속 매장 수

2. **BrandPanel은 Store 관리 리소스를 제공해야 한다**
   - StoreResource: Store 생성, 조회, 수정, 삭제
   - Form 스키마: name, description, address, phone, is_active, relationship_type
   - Table 스키마: 매장명, 관계 유형, 활성 상태, 주소

3. **relationship_type Enum은 직영/입점 구분을 제공해야 한다**
   - `owned`: 직영 (직접 운영)
   - `franchised`: 입점 (계약 관계)

4. **Soft Delete는 모든 엔티티에 적용되어야 한다**
   - Brand: SoftDeletes 트레이트 사용
   - Store: SoftDeletes 트레이트 사용
   - 삭제된 엔티티는 복구 가능

### Event-driven Requirements (이벤트 기반)

5. **WHEN Organization 관리자가 Brand를 생성하면, 시스템은 OrganizationPanel에서 BrandResource Form을 제공해야 한다**
   - Create 페이지: BrandResource\Pages\CreateBrand
   - 필수 필드: name, relationship_type
   - 선택 필드: description, logo

6. **WHEN Brand 관리자가 Store를 생성하면, 시스템은 BrandPanel에서 StoreResource Form을 제공해야 한다**
   - Create 페이지: StoreResource\Pages\CreateStore
   - 필수 필드: name, address, relationship_type
   - 선택 필드: description, phone, logo

7. **WHEN 사용자가 Brand/Store를 삭제하면, 시스템은 Soft Delete를 수행하고 deleted_at 타임스탬프를 기록해야 한다**
   - Filament Table Action: `Tables\Actions\DeleteAction`
   - 복구 기능: `Tables\Actions\RestoreAction`

8. **WHEN relationship_type이 변경되면, 시스템은 변경 이력을 Activity Log에 기록해야 한다**
   - Spatie Activity Log 사용
   - 변경 사항: old_value → new_value

### State-driven Requirements (상태 기반)

9. **WHILE 사용자가 Organization 관리자일 때, 시스템은 OrganizationPanel에서 Brand CRUD 권한을 부여해야 한다**
   - Policy: BrandPolicy
   - 메서드: `viewAny()`, `create()`, `update()`, `delete()`, `restore()`, `forceDelete()`

10. **WHILE 사용자가 Brand 관리자일 때, 시스템은 BrandPanel에서 Store CRUD 권한을 부여해야 한다**
    - Policy: StorePolicy
    - 메서드: `viewAny()`, `create()`, `update()`, `delete()`, `restore()`, `forceDelete()`

11. **WHILE Brand가 삭제된 상태일 때, 시스템은 해당 Brand의 모든 Store를 비활성화해야 한다**
    - Soft Delete 캐스케이드
    - Store의 is_active = false 설정

### Optional Features (선택적 기능)

12. **WHERE Brand가 여러 Store를 보유하면, 시스템은 BrandResource Infolist에 관계 테이블을 표시할 수 있다**
    - RelationManager: StoresRelationManager
    - 통계: 전체 매장 수, 활성 매장 수, 비활성 매장 수

13. **WHERE Organization이 여러 Brand를 보유하면, 시스템은 OrganizationResource Infolist에 관계 테이블을 표시할 수 있다**
    - RelationManager: BrandsRelationManager
    - 통계: 전체 브랜드 수, 활성 브랜드 수, 비활성 브랜드 수

### Constraints (제약사항)

14. **IF Brand가 입점(franchised) 관계이면, 시스템은 Brand 삭제를 차단해야 한다**
    - BrandPolicy: `delete()` 메서드에서 relationship_type 검증
    - 에러 메시지: "입점 브랜드는 삭제할 수 없습니다. 계약 해지 프로세스를 따르세요."

15. **IF Store가 입점(franchised) 관계이면, 시스템은 Store 삭제를 차단해야 한다**
    - StorePolicy: `delete()` 메서드에서 relationship_type 검증
    - 에러 메시지: "입점 매장은 삭제할 수 없습니다. 계약 해지 프로세스를 따르세요."

16. **IF Brand가 활성 Store를 보유하면, 시스템은 Brand 삭제를 차단해야 한다**
    - BrandPolicy: `delete()` 메서드에서 활성 Store 존재 여부 확인
    - 에러 메시지: "활성 매장이 있는 브랜드는 삭제할 수 없습니다."

17. **Brand는 반드시 하나의 Organization에 소속되어야 한다**
    - Migration: `brands` 테이블의 `organization_id` NOT NULL 제약
    - Validation: BrandResource Form에서 organization_id 필수 검증

18. **Store는 반드시 하나의 Brand에 소속되어야 한다**
    - Migration: `stores` 테이블의 `brand_id` NOT NULL 제약
    - Validation: StoreResource Form에서 brand_id 필수 검증

---

## 데이터베이스 설계

### Migration: `brands` 테이블 변경

#### 추가 컬럼
- `relationship_type`: ENUM('owned', 'franchised') NOT NULL DEFAULT 'owned'
- `deleted_at`: TIMESTAMP NULL (Soft Delete)

#### 제약사항
- `organization_id`: NOT NULL (Organization 필수 소속)

### Migration: `stores` 테이블 변경

#### 추가 컬럼
- `relationship_type`: ENUM('owned', 'franchised') NOT NULL DEFAULT 'owned'
- `deleted_at`: TIMESTAMP NULL (Soft Delete)

#### 제약사항
- `brand_id`: NOT NULL (Brand 필수 소속)

---

## Filament 리소스 구조

### BrandResource (OrganizationPanel)

#### Form Schema
- TextInput: name (필수)
- Select: relationship_type (필수)
  - owned: 직영
  - franchised: 입점
- Textarea: description (선택)
- FileUpload: logo (선택)
- Toggle: is_active (기본값: true)

#### Table Schema
- TextColumn: name
- BadgeColumn: relationship_type (색상: owned=success, franchised=warning)
- IconColumn: is_active
- TextColumn: stores_count (관계 카운트)
- TextColumn: created_at
- TextColumn: deleted_at (Soft Delete 상태 표시)

#### Actions
- CreateAction
- EditAction
- DeleteAction (Policy 검증)
- RestoreAction (Soft Delete 복구)
- ForceDeleteAction (Policy 검증)

### StoreResource (BrandPanel)

#### Form Schema
- TextInput: name (필수)
- Select: relationship_type (필수)
  - owned: 직영
  - franchised: 입점
- Textarea: description (선택)
- TextInput: address (필수)
- TextInput: phone (선택)
- FileUpload: logo (선택)
- Toggle: is_active (기본값: true)

#### Table Schema
- TextColumn: name
- BadgeColumn: relationship_type (색상: owned=success, franchised=warning)
- IconColumn: is_active
- TextColumn: address
- TextColumn: phone
- TextColumn: created_at
- TextColumn: deleted_at (Soft Delete 상태 표시)

#### Actions
- CreateAction
- EditAction
- DeleteAction (Policy 검증)
- RestoreAction (Soft Delete 복구)
- ForceDeleteAction (Policy 검증)

---

## Policy 구현

### BrandPolicy

#### 메서드
- `viewAny(User $user)`: Organization 관리자만 허용
- `create(User $user)`: Organization 관리자만 허용
- `update(User $user, Brand $brand)`: Organization 관리자만 허용
- `delete(User $user, Brand $brand)`: 제약 검증 후 허용
  - relationship_type이 franchised이면 차단
  - 활성 Store가 있으면 차단
- `restore(User $user, Brand $brand)`: Organization 관리자만 허용
- `forceDelete(User $user, Brand $brand)`: System Admin만 허용

### StorePolicy

#### 메서드
- `viewAny(User $user)`: Brand 관리자만 허용
- `create(User $user)`: Brand 관리자만 허용
- `update(User $user, Store $store)`: Brand 관리자만 허용
- `delete(User $user, Store $store)`: 제약 검증 후 허용
  - relationship_type이 franchised이면 차단
- `restore(User $user, Store $store)`: Brand 관리자만 허용
- `forceDelete(User $user, Store $store)`: System Admin만 허용

---

## 다국어 지원 (I18N-001 의존)

### 번역 키 (ko.json)

#### BrandResource
- `brand.relationship_type.owned`: "직영"
- `brand.relationship_type.franchised`: "입점"
- `brand.delete_franchised_error`: "입점 브랜드는 삭제할 수 없습니다. 계약 해지 프로세스를 따르세요."
- `brand.delete_active_stores_error`: "활성 매장이 있는 브랜드는 삭제할 수 없습니다."

#### StoreResource
- `store.relationship_type.owned`: "직영"
- `store.relationship_type.franchised`: "입점"
- `store.delete_franchised_error`: "입점 매장은 삭제할 수 없습니다. 계약 해지 프로세스를 따르세요."

---

## 추적성

### TAG 체인
- `@SPEC:BRAND-STORE-MGMT-001` → `@TEST:BRAND-STORE-MGMT-001` (tests/)
- `@SPEC:BRAND-STORE-MGMT-001` → `@CODE:BRAND-STORE-MGMT-001` (app/)

### 관련 문서
- `product.md`: 멀티 테넌시 계층화 아키텍처
- `I18N-001`: 다국어 지원 SPEC
- `TENANCY-AUTHZ-001`: 권한 체계 SPEC

---

_이 SPEC은 `/alfred:2-build BRAND-STORE-MGMT-001` 실행 시 TDD 구현의 기준이 됩니다._

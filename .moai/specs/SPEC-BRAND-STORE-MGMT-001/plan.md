# BRAND-STORE-MGMT-001 구현 계획

## 개요

Filament 기반 브랜드/매장 관리 체계를 TDD 방식으로 구현합니다.

---

## 우선순위별 마일스톤

### 1차 목표: 데이터베이스 및 모델 변경

#### 작업 항목
- [ ] Migration: `brands` 테이블에 `relationship_type`, `deleted_at` 컬럼 추가
- [ ] Migration: `stores` 테이블에 `relationship_type`, `deleted_at` 컬럼 추가
- [ ] Model: Brand에 SoftDeletes 트레이트 추가
- [ ] Model: Store에 SoftDeletes 트레이트 추가
- [ ] Enum: RelationshipType Enum 클래스 생성 (owned, franchised)

#### 검증 기준
- Migration 실행 후 테이블 구조 확인
- Model::factory() 테스트로 Soft Delete 동작 검증
- Enum 값이 올바르게 캐스팅되는지 확인

---

### 2차 목표: BrandResource 구현 (OrganizationPanel)

#### 작업 항목
- [ ] Resource: BrandResource 생성 (OrganizationPanel)
- [ ] Form: BrandResource Form 스키마 구현
  - TextInput: name
  - Select: relationship_type (Enum 연동)
  - Textarea: description
  - FileUpload: logo
  - Toggle: is_active
- [ ] Table: BrandResource Table 스키마 구현
  - TextColumn: name, created_at, deleted_at
  - BadgeColumn: relationship_type (색상 맵핑)
  - IconColumn: is_active
  - TextColumn: stores_count (관계 카운트)
- [ ] Actions: Create, Edit, Delete, Restore, ForceDelete

#### 검증 기준
- Form 필드가 올바르게 렌더링되는지 확인
- Table에서 relationship_type Badge 색상이 올바른지 확인
- Delete Action 실행 시 Soft Delete되는지 확인
- Restore Action 실행 시 복구되는지 확인

---

### 3차 목표: StoreResource 구현 (BrandPanel)

#### 작업 항목
- [ ] Resource: StoreResource 생성 (BrandPanel)
- [ ] Form: StoreResource Form 스키마 구현
  - TextInput: name, address, phone
  - Select: relationship_type (Enum 연동)
  - Textarea: description
  - FileUpload: logo
  - Toggle: is_active
- [ ] Table: StoreResource Table 스키마 구현
  - TextColumn: name, address, phone, created_at, deleted_at
  - BadgeColumn: relationship_type (색상 맵핑)
  - IconColumn: is_active
- [ ] Actions: Create, Edit, Delete, Restore, ForceDelete

#### 검증 기준
- Form 필드가 올바르게 렌더링되는지 확인
- Table에서 relationship_type Badge 색상이 올바른지 확인
- Delete Action 실행 시 Soft Delete되는지 확인
- Restore Action 실행 시 복구되는지 확인

---

### 4차 목표: Policy 구현

#### 작업 항목
- [ ] Policy: BrandPolicy 생성
  - viewAny(): Organization 관리자 확인
  - create(): Organization 관리자 확인
  - update(): Organization 관리자 확인
  - delete(): relationship_type 및 활성 Store 검증
  - restore(): Organization 관리자 확인
  - forceDelete(): System Admin 확인
- [ ] Policy: StorePolicy 생성
  - viewAny(): Brand 관리자 확인
  - create(): Brand 관리자 확인
  - update(): Brand 관리자 확인
  - delete(): relationship_type 검증
  - restore(): Brand 관리자 확인
  - forceDelete(): System Admin 확인

#### 검증 기준
- Policy 메서드가 올바른 권한을 반환하는지 테스트
- delete() 메서드에서 제약사항이 올바르게 검증되는지 확인
- 권한 없는 사용자가 차단되는지 확인

---

### 5차 목표: RelationManager 구현 (선택사항)

#### 작업 항목
- [ ] RelationManager: StoresRelationManager (BrandResource)
  - Table: 브랜드 소속 매장 목록
  - 통계: 전체/활성/비활성 매장 수
- [ ] RelationManager: BrandsRelationManager (OrganizationResource)
  - Table: 조직 소속 브랜드 목록
  - 통계: 전체/활성/비활성 브랜드 수

#### 검증 기준
- RelationManager가 올바른 관계 데이터를 표시하는지 확인
- 통계 카운트가 정확한지 확인

---

### 최종 목표: 다국어 지원 및 통합 테스트

#### 작업 항목
- [ ] 번역 키 추가 (ko.json)
  - BrandResource: relationship_type, 에러 메시지
  - StoreResource: relationship_type, 에러 메시지
- [ ] Activity Log 연동
  - relationship_type 변경 이력 기록
  - Soft Delete/Restore 이력 기록
- [ ] 통합 테스트
  - Organization → Brand → Store 전체 흐름 테스트
  - 권한 기반 접근 제어 테스트
  - Soft Delete 캐스케이드 테스트

#### 검증 기준
- 번역이 올바르게 적용되는지 확인
- Activity Log에 변경 이력이 기록되는지 확인
- 전체 워크플로우가 SPEC 요구사항을 충족하는지 확인

---

## 기술적 접근 방법

### Filament 4 패턴

#### Form Schema
- `TextInput::make('name')->required()`
- `Select::make('relationship_type')->enum(RelationshipType::class)`
- `Toggle::make('is_active')->default(true)`

#### Table Schema
- `BadgeColumn::make('relationship_type')->color(fn ($state) => match($state) { ... })`
- `IconColumn::make('is_active')->boolean()`
- `TextColumn::make('stores_count')->counts('stores')`

#### Actions
- `DeleteAction::make()->using(fn ($record) => $record->delete())`
- `RestoreAction::make()->using(fn ($record) => $record->restore())`

### Laravel Enum 활용

#### RelationshipType Enum
```php
enum RelationshipType: string
{
    case OWNED = 'owned';
    case FRANCHISED = 'franchised';

    public function label(): string
    {
        return match($this) {
            self::OWNED => __('brand.relationship_type.owned'),
            self::FRANCHISED => __('brand.relationship_type.franchised'),
        };
    }
}
```

### Policy 권한 검증

#### BrandPolicy::delete()
```php
public function delete(User $user, Brand $brand): bool
{
    // 입점 브랜드 삭제 차단
    if ($brand->relationship_type === RelationshipType::FRANCHISED) {
        throw new \Exception(__('brand.delete_franchised_error'));
    }

    // 활성 매장이 있으면 삭제 차단
    if ($brand->stores()->where('is_active', true)->exists()) {
        throw new \Exception(__('brand.delete_active_stores_error'));
    }

    return $user->can('delete_brand');
}
```

---

## 아키텍처 설계 방향

### 멀티 패널 계층화
- **OrganizationPanel**: Brand 생성/관리
- **BrandPanel**: Store 생성/관리
- 각 패널은 독립적인 권한 체계 보유

### Soft Delete 전략
- 모든 엔티티는 Soft Delete 지원
- 복구 기능 제공 (RestoreAction)
- 영구 삭제는 System Admin만 가능 (ForceDeleteAction)

### 권한 기반 접근 제어
- Spatie Permission 패키지 활용
- Policy 메서드로 세분화된 권한 관리
- 제약사항은 Policy에서 검증

---

## 리스크 및 대응 방안

### 리스크 1: relationship_type Enum 마이그레이션 호환성
- **리스크**: 기존 데이터가 없는 경우 Enum 적용 문제
- **대응**: Migration에서 기본값 'owned' 설정

### 리스크 2: Soft Delete 캐스케이드 복잡도
- **리스크**: Brand 삭제 시 Store 자동 비활성화 로직 복잡
- **대응**: Eloquent Event (Brand::deleting) 사용

### 리스크 3: Policy 권한 검증 누락
- **리스크**: Policy 메서드 미구현 시 권한 체크 우회
- **대응**: 모든 CRUD 메서드에 Policy 메서드 필수 구현 및 테스트

---

_이 계획은 `/alfred:2-build BRAND-STORE-MGMT-001` 실행 시 TDD 구현 가이드로 사용됩니다._

# BRAND-STORE-MGMT-001 수락 기준

## 개요

Filament 기반 브랜드/매장 관리 체계의 수락 기준을 Given-When-Then 형식으로 정의합니다.

---

## 시나리오 1: Organization 관리자가 Brand 생성

### Given
- 사용자가 Organization 관리자로 로그인되어 있다
- OrganizationPanel에 접근 권한이 있다

### When
- 사용자가 BrandResource의 Create 페이지에 접근한다
- Form에 다음 정보를 입력한다:
  - name: "테스트 브랜드"
  - relationship_type: "owned"
  - description: "직영 브랜드 설명"
- Create 버튼을 클릭한다

### Then
- 새로운 Brand가 데이터베이스에 생성된다
- Brand의 organization_id가 현재 Organization으로 설정된다
- Brand의 relationship_type이 "owned"로 저장된다
- BrandResource Table에 새 Brand가 표시된다
- 성공 메시지가 표시된다

---

## 시나리오 2: Brand 관리자가 Store 생성

### Given
- 사용자가 Brand 관리자로 로그인되어 있다
- BrandPanel에 접근 권한이 있다

### When
- 사용자가 StoreResource의 Create 페이지에 접근한다
- Form에 다음 정보를 입력한다:
  - name: "테스트 매장"
  - relationship_type: "owned"
  - address: "서울시 강남구 테헤란로 123"
  - phone: "02-1234-5678"
- Create 버튼을 클릭한다

### Then
- 새로운 Store가 데이터베이스에 생성된다
- Store의 brand_id가 현재 Brand로 설정된다
- Store의 relationship_type이 "owned"로 저장된다
- StoreResource Table에 새 Store가 표시된다
- 성공 메시지가 표시된다

---

## 시나리오 3: 입점 Brand 삭제 차단

### Given
- 사용자가 Organization 관리자로 로그인되어 있다
- relationship_type이 "franchised"인 Brand가 존재한다

### When
- 사용자가 BrandResource Table에서 Delete Action을 클릭한다

### Then
- 삭제가 차단된다
- 에러 메시지가 표시된다: "입점 브랜드는 삭제할 수 없습니다. 계약 해지 프로세스를 따르세요."
- Brand가 데이터베이스에 유지된다

---

## 시나리오 4: 활성 Store가 있는 Brand 삭제 차단

### Given
- 사용자가 Organization 관리자로 로그인되어 있다
- Brand가 존재하고, is_active = true인 Store가 1개 이상 있다

### When
- 사용자가 BrandResource Table에서 Delete Action을 클릭한다

### Then
- 삭제가 차단된다
- 에러 메시지가 표시된다: "활성 매장이 있는 브랜드는 삭제할 수 없습니다."
- Brand가 데이터베이스에 유지된다

---

## 시나리오 5: 직영 Brand Soft Delete

### Given
- 사용자가 Organization 관리자로 로그인되어 있다
- relationship_type이 "owned"인 Brand가 존재한다
- Brand에 활성 Store가 없다

### When
- 사용자가 BrandResource Table에서 Delete Action을 클릭한다

### Then
- Brand의 deleted_at 컬럼에 현재 타임스탬프가 기록된다
- BrandResource Table에서 Brand가 숨겨진다
- Soft Delete 필터를 적용하면 삭제된 Brand가 표시된다
- 성공 메시지가 표시된다: "브랜드가 삭제되었습니다."

---

## 시나리오 6: Soft Delete된 Brand 복구

### Given
- 사용자가 Organization 관리자로 로그인되어 있다
- deleted_at이 NULL이 아닌 Brand가 존재한다
- Soft Delete 필터가 활성화되어 있다

### When
- 사용자가 BrandResource Table에서 Restore Action을 클릭한다

### Then
- Brand의 deleted_at 컬럼이 NULL로 업데이트된다
- BrandResource Table의 기본 뷰에 Brand가 다시 표시된다
- 성공 메시지가 표시된다: "브랜드가 복구되었습니다."

---

## 시나리오 7: relationship_type Badge 색상 표시

### Given
- 사용자가 OrganizationPanel에 로그인되어 있다
- relationship_type이 "owned"인 Brand와 "franchised"인 Brand가 각각 존재한다

### When
- 사용자가 BrandResource Table을 조회한다

### Then
- relationship_type이 "owned"인 Brand는 success 색상 Badge로 표시된다
- relationship_type이 "franchised"인 Brand는 warning 색상 Badge로 표시된다

---

## 시나리오 8: relationship_type 변경 시 Activity Log 기록

### Given
- 사용자가 Organization 관리자로 로그인되어 있다
- relationship_type이 "owned"인 Brand가 존재한다

### When
- 사용자가 Brand Edit 페이지에서 relationship_type을 "franchised"로 변경한다
- Save 버튼을 클릭한다

### Then
- Brand의 relationship_type이 "franchised"로 업데이트된다
- activity_log 테이블에 다음 내용이 기록된다:
  - subject_type: Brand::class
  - subject_id: Brand의 ID
  - properties: {"old_value": "owned", "new_value": "franchised"}
  - causer_id: 현재 사용자 ID

---

## 시나리오 9: Brand Soft Delete 시 Store 비활성화

### Given
- 사용자가 Organization 관리자로 로그인되어 있다
- Brand가 존재하고, is_active = false인 Store가 3개 있다

### When
- 사용자가 BrandResource Table에서 Delete Action을 클릭한다

### Then
- Brand의 deleted_at 컬럼에 현재 타임스탬프가 기록된다
- Brand의 모든 Store의 is_active가 false로 업데이트된다 (이미 false인 경우 변경 없음)
- BrandResource Table에서 Brand가 숨겨진다

---

## 시나리오 10: StoresRelationManager 표시 (선택사항)

### Given
- 사용자가 Brand 관리자로 로그인되어 있다
- Brand가 5개의 Store를 보유하고 있다 (3개 활성, 2개 비활성)

### When
- 사용자가 BrandResource의 View 페이지에 접근한다

### Then
- StoresRelationManager가 표시된다
- Table에 5개의 Store가 모두 나열된다
- 통계 위젯에 다음이 표시된다:
  - 전체 매장 수: 5
  - 활성 매장 수: 3
  - 비활성 매장 수: 2

---

## 품질 게이트 기준

### 테스트 커버리지
- [ ] 전체 커버리지 ≥ 85%
- [ ] BrandResource 테스트 커버리지 ≥ 90%
- [ ] StoreResource 테스트 커버리지 ≥ 90%
- [ ] BrandPolicy 테스트 커버리지 100%
- [ ] StorePolicy 테스트 커버리지 100%

### 코드 품질
- [ ] Laravel Pint 검사 통과 (0 오류)
- [ ] Larastan Level 8 검사 통과 (0 오류)
- [ ] Rector 제안 사항 검토 및 적용

### 성능
- [ ] BrandResource Table 로딩 시간 < 500ms (100개 레코드 기준)
- [ ] StoreResource Table 로딩 시간 < 500ms (100개 레코드 기준)

### 보안
- [ ] BrandPolicy 모든 메서드 구현 및 테스트
- [ ] StorePolicy 모든 메서드 구현 및 테스트
- [ ] 권한 없는 사용자 접근 차단 확인

### 추적성
- [ ] 모든 소스 코드에 `@CODE:BRAND-STORE-MGMT-001` TAG 포함
- [ ] 모든 테스트 코드에 `@TEST:BRAND-STORE-MGMT-001` TAG 포함
- [ ] Activity Log에 주요 변경 사항 기록 확인

---

## 검증 방법 및 도구

### 자동화 테스트
- **Pest**: Feature 테스트, Unit 테스트
- **Laravel Dusk**: E2E 테스트 (선택사항)

### 수동 테스트
- **Filament Panel**: 실제 사용자 시나리오 재현
- **Database Inspection**: Migration 및 Soft Delete 확인

### 품질 도구
- **Pest**: `composer pest`
- **Pint**: `composer pint`
- **Larastan**: `composer phpstan`
- **Rector**: `composer rector`

---

## 완료 조건 (Definition of Done)

### 필수 조건
- [ ] 모든 시나리오 (1~9) 테스트 통과
- [ ] 품질 게이트 기준 모두 충족
- [ ] BrandResource 및 StoreResource 완전 동작
- [ ] BrandPolicy 및 StorePolicy 모든 메서드 구현
- [ ] 다국어 번역 키 추가 (ko.json)
- [ ] Activity Log 연동 확인

### 선택 조건
- [ ] StoresRelationManager 구현 및 테스트 (시나리오 10)
- [ ] BrandsRelationManager 구현 및 테스트

### 문서화
- [ ] SPEC 문서 최신화 (version 증가)
- [ ] HISTORY 섹션에 변경 이력 기록
- [ ] Living Document 동기화 (`/alfred:3-sync`)

---

_이 수락 기준은 `/alfred:2-build BRAND-STORE-MGMT-001` 실행 시 TDD 구현 검증에 사용됩니다._

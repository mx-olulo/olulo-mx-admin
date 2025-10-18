# SPEC-I18N-001 구현 계획

> Filament 리소스 다국어 지원 시스템 구현 전략

---

## 📋 개요

### 목표
OrganizationResource의 모든 UI 요소를 es-MX, en, ko 3개 언어로 다국어화하여 멕시코 시장 진출을 준비하고, 향후 다국가 확장을 위한 표준 패턴을 수립한다.

### 범위
- **대상 리소스**: OrganizationResource (1개)
- **대상 언어**: es-MX (스페인어-멕시코), en (영어), ko (한국어)
- **번역 항목**: 약 30개 키 × 3개 언어 = 90개
- **영향받는 파일**: 4개 PHP 클래스 + 3개 번역 파일

---

## 🎯 우선순위별 마일스톤

### 1차 목표: 번역 파일 생성 (핵심)
**우선순위**: Critical

**작업 내용**:
1. `lang/es-MX/filament.php` 생성 (기본 언어)
   - organizations 섹션 추가
   - resource, fields, columns, actions, activities 카테고리 정의
   - 스페인어 번역 작성 (30개 키)
   - 기존 dashboard 번역 유지

2. `lang/en/filament.php` 생성 (폴백 언어)
   - organizations 섹션 추가
   - 영어 번역 작성 (30개 키)
   - 폴백 전략 테스트용

3. `lang/ko/filament.php` 생성 (추가 언어)
   - organizations 섹션 추가
   - 한국어 번역 작성 (30개 키)
   - 다국어 확장성 검증용

**완료 조건**:
- 3개 번역 파일 모두 생성됨
- 모든 번역 키가 3개 언어로 제공됨
- `rg "filament.organizations" -n lang/` 실행 시 모든 키가 조회됨

---

### 2차 목표: 코드 적용 (통합)
**우선순위**: High

**작업 내용**:
1. **OrganizationForm.php 수정**
   - 5개 폼 필드에 `__('filament.organizations.fields.*')` 적용
   - 헬퍼 텍스트에 번역 키 적용 (선택)
   - 플레이스홀더에 번역 키 적용 (선택)

2. **OrganizationsTable.php 수정**
   - 6개 테이블 컬럼에 `__('filament.organizations.columns.*')` 적용
   - 테이블 필터에 번역 키 적용 (선택)

3. **ListOrganizationActivities.php 수정**
   - 페이지 제목에 `__('filament.organizations.activities.title', ['name' => $name])` 적용
   - 이벤트 타입 필터에 `__('filament.organizations.activities.event_types.*')` 적용
   - 테이블 컬럼에 번역 키 적용

4. **OrganizationResource.php 수정**
   - 네비게이션 라벨에 `__('filament.organizations.resource.navigation_label')` 적용
   - 모델 라벨에 `__('filament.organizations.resource.label')` 적용
   - 복수 라벨에 `__('filament.organizations.resource.plural_label')` 적용

**완료 조건**:
- 모든 하드코딩된 문자열이 번역 키로 교체됨
- 로케일 변경 시 UI가 즉시 업데이트됨
- 폴백 전략이 정상 동작함 (키 누락 시 en → 키 자체)

---

### 3차 목표: 테스트 작성 (품질)
**우선순위**: High

**작업 내용**:
1. **OrganizationResourceI18nTest.php 작성**
   - 로케일별 UI 표시 검증 (es-MX, en, ko)
   - 폼 필드 라벨 검증
   - 테이블 컬럼 라벨 검증
   - 액션 라벨 검증
   - 활동 로그 번역 검증
   - 폴백 전략 검증

2. **TranslationCompletenessTest.php 작성**
   - 번역 키 완전성 검증 (누락된 키 탐지)
   - 언어별 번역 일관성 검증
   - 번역 파일 구조 검증

**완료 조건**:
- 테스트 커버리지 85% 이상
- 모든 테스트가 통과함
- RED → GREEN → REFACTOR 사이클 완료

---

### 최종 목표: 문서화 및 확장 가이드 (지속성)
**우선순위**: Medium

**작업 내용**:
1. **i18n-guide.md 작성** (선택)
   - 번역 파일 구조 설명
   - 새 언어 추가 가이드
   - 새 리소스 다국어화 가이드
   - 번역 품질 검증 방법
   - 베스트 프랙티스

2. **README.md 업데이트**
   - 다국어 지원 기능 추가
   - 지원 언어 목록 추가

**완료 조건**:
- 개발자가 새 언어를 추가할 수 있음
- 개발자가 새 리소스를 다국어화할 수 있음
- 번역 품질 검증 도구 사용 가능

---

## 🔧 기술적 접근 방법

### 번역 파일 설계 원칙

1. **계층 구조**
   ```
   filament
   ├── dashboard (기존)
   ├── organizations (신규)
   │   ├── resource (네비게이션, 모델 라벨)
   │   ├── fields (폼 필드)
   │   ├── columns (테이블 컬럼)
   │   ├── actions (액션)
   │   └── activities (활동 로그)
   └── common (공통 액션)
   ```

2. **명명 규칙**
   - 카테고리: 단수형 사용 (fields, columns, actions)
   - 키: snake_case 사용 (contact_email, is_active)
   - 네임스페이스: `filament.{resource}.{category}.{key}`

3. **재사용성**
   - 공통 액션(view, edit, delete)은 `common.actions.*`에 정의
   - 리소스별 커스텀 액션은 `{resource}.actions.*`에 정의
   - 날짜 포맷은 `common.formats.*`에 정의 (향후 확장)

### 코드 적용 전략

1. **최소 침습**
   - 기존 코드 구조 유지
   - 하드코딩된 문자열만 번역 키로 교체
   - 로직 변경 없음

2. **폴백 안전성**
   - `__()` 헬퍼 함수는 키 누락 시 키 자체를 반환
   - 개발 환경에서 누락된 키를 로그에 기록
   - 프로덕션에서는 영어 폴백 사용

3. **동적 콘텐츠 처리**
   ```php
   // ✅ 올바른 예: 번역 키에 변수 전달
   __('filament.organizations.activities.title', ['name' => $record->name])

   // ❌ 잘못된 예: 동적 값을 번역하려고 시도
   __($record->name)
   ```

### 테스트 전략

1. **Feature Test (통합 테스트)**
   - Livewire 컴포넌트 렌더링 검증
   - 로케일별 UI 표시 검증
   - 사용자 인터랙션 시뮬레이션

2. **Unit Test (단위 테스트)**
   - 번역 키 존재 여부 검증
   - 번역 파일 구조 검증
   - 폴백 전략 검증

3. **테스트 데이터**
   - Factory를 사용한 Organization 데이터 생성
   - 각 로케일별 번역 검증

---

## 🚨 리스크 및 대응 방안

### 리스크 1: 번역 품질 문제
**발생 가능성**: Medium
**영향도**: High

**시나리오**:
- 기계 번역으로 인한 부자연스러운 문장
- 문맥을 고려하지 않은 번역
- 전문 용어 번역 오류

**완화 방안**:
- [ ] 전문 번역가 검수 의뢰 (스페인어 우선)
- [ ] 네이티브 스피커 리뷰
- [ ] 번역 품질 체크리스트 작성
- [ ] 번역 가이드라인 문서화

**대응 계획**:
- 번역 오류 발견 시 즉시 수정
- 번역 피드백 수집 채널 운영
- 주기적 번역 품질 검토 (분기별)

---

### 리스크 2: 누락된 번역 키
**발생 가능성**: Medium
**영향도**: Medium

**시나리오**:
- 새 UI 요소 추가 시 번역 키 누락
- 일부 언어만 번역 추가
- 개발자 실수로 하드코딩된 문자열 사용

**완화 방안**:
- [ ] TranslationCompletenessTest 작성
- [ ] CI/CD 파이프라인에 번역 검증 추가
- [ ] 개발 환경에서 누락된 키 로깅
- [ ] PR 리뷰 시 번역 체크리스트 확인

**대응 계획**:
- 누락된 키 발견 시 즉시 추가
- 테스트 실패 시 병합 차단
- 개발자 교육 (번역 가이드 공유)

---

### 리스크 3: 성능 저하
**발생 가능성**: Low
**영향도**: Low

**시나리오**:
- 번역 파일 크기 증가로 인한 로딩 시간 증가
- `__()` 헬퍼 함수 호출 오버헤드
- 번역 캐시 미사용

**완화 방안**:
- [ ] 프로덕션 환경에서 번역 캐싱 활성화 (`php artisan config:cache`)
- [ ] 번역 파일 크기 모니터링 (1000개 키 이하)
- [ ] 향후 파일 분리 계획 수립 (100개 리소스 이상 시)

**대응 계획**:
- 성능 저하 발견 시 번역 파일 분리
- APM 도구로 성능 모니터링
- 필요 시 번역 캐시 전략 개선

---

### 리스크 4: 기존 기능 영향
**발생 가능성**: Low
**영향도**: High

**시나리오**:
- 번역 키 적용 중 기존 기능 동작 변경
- 하드코딩된 문자열 제거로 인한 버그
- 로케일 변경 시 예상치 못한 동작

**완화 방안**:
- [ ] TDD 방식으로 테스트 먼저 작성
- [ ] 기존 기능 회귀 테스트 실행
- [ ] Staging 환경에서 충분한 검증
- [ ] 점진적 배포 (Feature Flag 사용)

**대응 계획**:
- 버그 발견 시 즉시 롤백
- 핫픽스 배포 프로세스 준비
- 사용자 피드백 수집

---

## 📊 성공 지표

### 정량적 지표
1. **번역 완성도**: 90개 키 모두 번역 완료 (100%)
2. **테스트 커버리지**: 85% 이상
3. **번역 키 누락률**: 0%
4. **성능 영향**: 페이지 로딩 시간 5% 이내 증가

### 정성적 지표
1. **사용성**: 네이티브 스피커가 자연스럽다고 평가
2. **일관성**: 모든 UI 요소가 동일한 로케일로 표시
3. **확장성**: 새 언어 추가 시 코드 변경 없이 가능
4. **유지보수성**: 개발자가 번역 가이드를 쉽게 이해

---

## 🔄 다음 단계

1. **즉시 실행** (Phase 1)
   - `/alfred:2-build I18N-001` 실행
   - RED → GREEN → REFACTOR 사이클 진행
   - 번역 파일 3개 생성
   - 코드 4개 파일 수정

2. **테스트 검증** (Phase 2)
   - Feature Test 작성 및 실행
   - TranslationCompletenessTest 작성
   - 회귀 테스트 실행

3. **문서화** (Phase 3)
   - `/alfred:3-sync` 실행
   - i18n-guide.md 작성
   - README.md 업데이트

4. **배포 준비** (Phase 4)
   - Staging 환경 검증
   - 번역 품질 검토
   - 프로덕션 배포

---

**작성일**: 2025-10-19
**작성자**: @Alfred (spec-builder)
**관련 SPEC**: @SPEC:I18N-001

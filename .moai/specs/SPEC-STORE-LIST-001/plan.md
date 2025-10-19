# SPEC-STORE-LIST-001 구현 계획

> **SPEC**: 고객 상점 목록 페이지
> **버전**: 0.0.1
> **작성일**: 2025-10-19

---

## 우선순위별 마일스톤

### 1차 목표: Backend Store 목록 API 구현
- HomeController에 Store 목록 조회 로직 추가
- Eager Loading (`with('organization')`) 구현
- `is_active = true` 필터 적용
- 페이지네이션 (10개/페이지) 설정
- Inertia props 전달 구조 정의

### 2차 목표: Frontend Store 목록 UI 구현
- Home.tsx에서 환영 카드 제거
- StoreCard 컴포넌트 생성 (ref/CompanyListPage.tsx 참조)
- Grid 레이아웃 구현 (반응형)
- Organization Badge 표시
- Active Status 표시

### 3차 목표: 검색 필터 구현
- SearchBar 컴포넌트 생성
- useState로 검색어 상태 관리
- filter() 메서드로 클라이언트 사이드 필터링
- 검색 결과 없음 메시지 처리

### 4차 목표: 다국어 및 폴리싱
- 다국어 키 추가 (`customer.home.*`)
- 스켈레톤 UI (로딩 상태) 추가
- 빈 상태 메시지 처리
- 접근성 개선 (ARIA labels)

---

## 기술적 접근 방법

### Backend (Laravel)

#### 1. Controller 수정
**파일**: `app/Http/Controllers/Customer/HomeController.php`

**구현 내용**:
```php
// @CODE:STORE-LIST-001:API | SPEC: SPEC-STORE-LIST-001.md
public function index()
{
    $stores = Store::with('organization')
        ->where('is_active', true)
        ->paginate(10);

    return Inertia::render('Customer/Home', [
        'stores' => $stores
    ]);
}
```

**핵심 포인트**:
- Eager Loading으로 N+1 방지
- `is_active` 필터로 활성 Store만 조회
- 페이지네이션으로 성능 최적화

#### 2. 라우트 확인
**파일**: `routes/web.php`

**기존 라우트 검증**:
```php
Route::middleware(['customer'])->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('customer.home');
});
```

---

### Frontend (React + TypeScript)

#### 1. Home.tsx 수정
**파일**: `resources/js/Pages/Customer/Home.tsx`

**기존 구조**:
```tsx
// 환영 카드 (제거 예정)
<CustomerLayout>
  <WelcomeCard />
</CustomerLayout>
```

**신규 구조**:
```tsx
// @CODE:STORE-LIST-001:UI | SPEC: SPEC-STORE-LIST-001.md
<CustomerLayout>
  <SearchBar value={searchTerm} onChange={setSearchTerm} />
  <StoreGrid stores={filteredStores} />
  <Pagination {...stores.meta} />
</CustomerLayout>
```

#### 2. 컴포넌트 생성

**StoreCard.tsx** (신규):
```tsx
// @CODE:STORE-LIST-001:UI | SPEC: SPEC-STORE-LIST-001.md
interface StoreCardProps {
  store: {
    id: number;
    name: string;
    description?: string;
    organization: {
      name: string;
    };
  };
}

// ref/CompanyListPage.tsx 디자인 적용
// - Organization Badge (primary)
// - Store Name (truncate 1줄)
// - Description (truncate 2줄)
// - Active Indicator (초록색 dot)
```

**SearchBar.tsx** (신규):
```tsx
// @CODE:STORE-LIST-001:UI | SPEC: SPEC-STORE-LIST-001.md
interface SearchBarProps {
  value: string;
  onChange: (value: string) => void;
  placeholder?: string;
}

// 다국어 placeholder: t('customer.home.search_placeholder')
```

#### 3. 검색 로직
```tsx
const [searchTerm, setSearchTerm] = useState('');

const filteredStores = useMemo(() => {
  if (!searchTerm) return stores.data;

  return stores.data.filter(store =>
    store.name.toLowerCase().includes(searchTerm.toLowerCase())
  );
}, [stores.data, searchTerm]);
```

---

### 다국어 (I18N)

#### 다국어 파일 추가

**ko.json** (한국어):
```json
{
  "customer.home.title": "상점 목록",
  "customer.home.search_placeholder": "상점 검색...",
  "customer.home.no_results": "검색 결과가 없습니다",
  "customer.home.no_stores": "등록된 상점이 없습니다",
  "customer.home.organization": "조직",
  "customer.home.active": "운영중"
}
```

**es.json** (스페인어):
```json
{
  "customer.home.title": "Lista de Tiendas",
  "customer.home.search_placeholder": "Buscar tienda...",
  "customer.home.no_results": "No se encontraron resultados",
  "customer.home.no_stores": "No hay tiendas registradas",
  "customer.home.organization": "Organización",
  "customer.home.active": "Activo"
}
```

**en.json** (영어):
```json
{
  "customer.home.title": "Store List",
  "customer.home.search_placeholder": "Search store...",
  "customer.home.no_results": "No results found",
  "customer.home.no_stores": "No stores registered",
  "customer.home.organization": "Organization",
  "customer.home.active": "Active"
}
```

---

## 아키텍처 설계 방향

### 컴포넌트 계층 구조
```
CustomerLayout (기존)
└─ Home.tsx (수정)
    ├─ SearchBar.tsx (신규)
    ├─ StoreGrid.tsx (신규)
    │   └─ StoreCard.tsx (신규)
    └─ Pagination.tsx (기존 재사용)
```

### 데이터 흐름
```
Backend (Laravel)
  ↓ Inertia::render()
Frontend (React)
  ↓ useState(searchTerm)
Client-side Filter
  ↓ useMemo()
Filtered Stores
  ↓ map()
StoreCard Components
```

### 상태 관리
- **검색어**: `useState<string>` (로컬 상태)
- **Store 목록**: Inertia props (서버 상태)
- **언어**: `useTranslation()` (I18N 컨텍스트)

---

## 리스크 및 대응 방안

### 리스크 1: N+1 쿼리 성능 문제
**영향**: Store 수가 증가하면 응답 시간 증가

**대응**:
- ✅ Eager Loading (`with('organization')`) 필수
- ✅ 페이지네이션으로 데이터 제한
- ⚠️ 향후 서버 사이드 검색으로 전환 고려

### 리스크 2: 클라이언트 사이드 검색 한계
**영향**: 페이지네이션 적용 시 전체 데이터 검색 불가

**대응**:
- ✅ 초기 구현은 단순화 (10개/페이지)
- ⚠️ 향후 서버 사이드 검색 API 추가 (SPEC-STORE-SEARCH-001)

### 리스크 3: ref/CompanyListPage.tsx 디자인 불일치
**영향**: UI/UX 일관성 저하

**대응**:
- ✅ ref/ 파일을 직접 참조하여 스타일 복사
- ✅ Tailwind CSS 클래스 재사용
- ✅ 디자인 리뷰 단계에서 검증

### 리스크 4: CustomerLayout 수정 금지
**영향**: 레이아웃 변경 불가

**대응**:
- ✅ CustomerLayout 내부는 건드리지 않음
- ✅ Content 영역만 수정 (Home.tsx)
- ✅ 레이아웃 테스트 추가

---

## 의존성

### 필수 의존성
- **I18N-001**: 다국어 인프라 (useTranslation 훅, 언어 파일)
- **CustomerLayout**: 기존 고객 레이아웃 (수정 금지)
- **Store 모델**: `organization` 관계 정의 필수

### 기술 스택
- **Backend**: Laravel 11, Inertia.js
- **Frontend**: React 18, TypeScript, Tailwind CSS
- **ORM**: Eloquent (Eager Loading)
- **I18N**: react-i18next

---

## 테스트 전략

### Backend Test (Laravel Feature Test)
```php
// @TEST:STORE-LIST-001:API | SPEC: SPEC-STORE-LIST-001.md
test('고객 홈 페이지는 활성 Store 목록을 반환한다')
test('Store 목록은 Organization을 Eager Loading한다')
test('비활성 Store는 목록에 포함되지 않는다')
test('페이지네이션이 올바르게 동작한다')
```

### Frontend Test (Vitest + React Testing Library)
```tsx
// @TEST:STORE-LIST-001:UI | SPEC: SPEC-STORE-LIST-001.md
test('Store 목록이 그리드로 표시된다')
test('검색어 입력 시 필터링이 동작한다')
test('검색 결과 없음 메시지가 표시된다')
test('Organization Badge가 표시된다')
```

### E2E Test (향후 - Playwright)
```typescript
test('사용자는 상점을 검색할 수 있다')
test('언어 전환 시 텍스트가 변경된다')
```

---

## 성능 목표

- **응답 시간**: < 200ms (Store 목록 조회)
- **쿼리 횟수**: ≤ 2회 (Eager Loading)
- **번들 크기**: 신규 컴포넌트 +10KB 이하
- **LCP**: < 2.5s (모바일)

---

## 완료 기준 (Definition of Done)

### Backend
- [ ] HomeController에 Store 목록 로직 추가
- [ ] Eager Loading 적용 (`with('organization')`)
- [ ] `is_active = true` 필터 적용
- [ ] 페이지네이션 설정 (10개/페이지)
- [ ] Feature Test 작성 및 통과

### Frontend
- [ ] Home.tsx 환영 카드 제거 및 Store 목록 UI 추가
- [ ] StoreCard 컴포넌트 생성 (ref/ 디자인 준수)
- [ ] SearchBar 컴포넌트 생성
- [ ] 검색 필터 동작 (클라이언트 사이드)
- [ ] 다국어 키 추가 (ko/es/en)
- [ ] 스켈레톤 UI 추가
- [ ] Component Test 작성 및 통과

### 품질
- [ ] N+1 쿼리 없음 확인 (Laravel Debugbar)
- [ ] 반응형 디자인 검증 (모바일/태블릿/데스크톱)
- [ ] 다국어 전환 동작 확인
- [ ] 접근성 검증 (ARIA labels)

### 문서
- [ ] SPEC 문서 최신화
- [ ] 코드 주석 (`@CODE:STORE-LIST-001`) 추가
- [ ] README 업데이트 (향후)

---

## 다음 단계 연결

- **SPEC-STORE-DETAIL-001**: Store 상세 페이지 (카드 클릭 시 이동)
- **SPEC-ORG-LIST-001**: Organization 목록 페이지
- **SPEC-STORE-SEARCH-001**: 서버 사이드 검색 API

---

**작성자**: @Goos
**최종 수정**: 2025-10-19

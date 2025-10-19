# SPEC-STORE-LIST-001 수락 기준

> **SPEC**: 고객 상점 목록 페이지
> **버전**: 0.0.1
> **작성일**: 2025-10-19

---

## 테스트 시나리오 (Given-When-Then)

### Scenario 1: 활성 Store 목록 조회

**Given**:
- 데이터베이스에 활성 Store 5개가 존재
- 각 Store는 Organization에 속함
- 비활성 Store 3개도 존재

**When**:
- 사용자가 `/` 경로에 접근

**Then**:
- 5개의 활성 Store만 목록에 표시됨
- 각 Store 카드에 Organization 이름이 표시됨
- 비활성 Store는 목록에 포함되지 않음
- 페이지네이션 정보가 표시됨 (총 5개)

**검증 방법**:
```php
// @TEST:STORE-LIST-001:API
test('활성 Store만 목록에 표시된다', function () {
    $org = Organization::factory()->create();
    Store::factory()->count(5)->active()->for($org)->create();
    Store::factory()->count(3)->inactive()->for($org)->create();

    $response = $this->get('/');

    $response->assertInertia(fn ($page) => $page
        ->component('Customer/Home')
        ->has('stores.data', 5)
        ->where('stores.data.0.is_active', true)
    );
});
```

---

### Scenario 2: N+1 쿼리 방지 (Eager Loading)

**Given**:
- 데이터베이스에 활성 Store 10개가 존재
- 각 Store는 서로 다른 Organization에 속함

**When**:
- 사용자가 `/` 경로에 접근
- Store 목록을 조회

**Then**:
- 쿼리 실행 횟수가 2회 이하여야 함
  - 1회: Store 목록 조회
  - 1회: Organization Eager Loading (단일 쿼리)

**검증 방법**:
```php
// @TEST:STORE-LIST-001:API
test('N+1 쿼리가 발생하지 않는다', function () {
    $orgs = Organization::factory()->count(10)->create();
    foreach ($orgs as $org) {
        Store::factory()->active()->for($org)->create();
    }

    DB::enableQueryLog();

    $this->get('/');

    $queries = DB::getQueryLog();
    expect(count($queries))->toBeLessThanOrEqual(2);
});
```

---

### Scenario 3: 검색 필터 동작 (클라이언트 사이드)

**Given**:
- Store "올룰로 강남점", "올룰로 홍대점", "스타벅스 강남역점"이 존재

**When**:
- 사용자가 검색창에 "강남" 입력

**Then**:
- "올룰로 강남점", "스타벅스 강남역점" 2개만 표시됨
- "올룰로 홍대점"은 숨겨짐
- 검색 결과 개수가 2개로 표시됨

**검증 방법**:
```tsx
// @TEST:STORE-LIST-001:UI
test('검색어로 Store를 필터링한다', () => {
  const stores = [
    { id: 1, name: '올룰로 강남점', organization: { name: '올룰로' } },
    { id: 2, name: '올룰로 홍대점', organization: { name: '올룰로' } },
    { id: 3, name: '스타벅스 강남역점', organization: { name: '스타벅스' } }
  ];

  render(<Home stores={{ data: stores }} />);

  const searchInput = screen.getByPlaceholderText(/검색/);
  fireEvent.change(searchInput, { target: { value: '강남' } });

  expect(screen.getByText('올룰로 강남점')).toBeInTheDocument();
  expect(screen.getByText('스타벅스 강남역점')).toBeInTheDocument();
  expect(screen.queryByText('올룰로 홍대점')).not.toBeInTheDocument();
});
```

---

### Scenario 4: 검색 결과 없음 처리

**Given**:
- Store "올룰로 강남점", "올룰로 홍대점"이 존재

**When**:
- 사용자가 검색창에 "부산" 입력

**Then**:
- "검색 결과가 없습니다" 메시지가 표시됨
- Store 카드는 표시되지 않음

**검증 방법**:
```tsx
// @TEST:STORE-LIST-001:UI
test('검색 결과가 없으면 메시지를 표시한다', () => {
  const stores = [
    { id: 1, name: '올룰로 강남점', organization: { name: '올룰로' } }
  ];

  render(<Home stores={{ data: stores }} />);

  const searchInput = screen.getByPlaceholderText(/검색/);
  fireEvent.change(searchInput, { target: { value: '부산' } });

  expect(screen.getByText(/검색 결과가 없습니다/)).toBeInTheDocument();
  expect(screen.queryByText('올룰로 강남점')).not.toBeInTheDocument();
});
```

---

### Scenario 5: 빈 Store 목록 처리

**Given**:
- 데이터베이스에 활성 Store가 없음

**When**:
- 사용자가 `/` 경로에 접근

**Then**:
- "등록된 상점이 없습니다" 메시지가 표시됨
- 검색창은 표시되지 않거나 비활성화됨

**검증 방법**:
```php
// @TEST:STORE-LIST-001:API
test('활성 Store가 없으면 빈 목록을 반환한다', function () {
    Store::factory()->count(3)->inactive()->create();

    $response = $this->get('/');

    $response->assertInertia(fn ($page) => $page
        ->component('Customer/Home')
        ->has('stores.data', 0)
    );
});
```

```tsx
// @TEST:STORE-LIST-001:UI
test('Store가 없으면 메시지를 표시한다', () => {
  render(<Home stores={{ data: [] }} />);

  expect(screen.getByText(/등록된 상점이 없습니다/)).toBeInTheDocument();
});
```

---

### Scenario 6: 페이지네이션 동작

**Given**:
- 데이터베이스에 활성 Store 25개가 존재

**When**:
- 사용자가 `/` 경로에 접근 (page=1)

**Then**:
- 첫 10개 Store가 표시됨
- 페이지네이션 컨트롤이 표시됨
- "다음 페이지" 버튼이 활성화됨

**When**:
- 사용자가 "다음 페이지" 버튼 클릭

**Then**:
- 11~20번 Store가 표시됨
- "이전 페이지", "다음 페이지" 버튼이 모두 활성화됨

**검증 방법**:
```php
// @TEST:STORE-LIST-001:API
test('페이지네이션이 올바르게 동작한다', function () {
    Store::factory()->count(25)->active()->create();

    $response = $this->get('/?page=1');
    $response->assertInertia(fn ($page) => $page
        ->has('stores.data', 10)
        ->where('stores.current_page', 1)
        ->where('stores.last_page', 3)
    );

    $response = $this->get('/?page=2');
    $response->assertInertia(fn ($page) => $page
        ->has('stores.data', 10)
        ->where('stores.current_page', 2)
    );
});
```

---

### Scenario 7: Organization Badge 표시

**Given**:
- Store "올룰로 강남점"이 Organization "올룰로 코리아"에 속함

**When**:
- 사용자가 `/` 경로에 접근

**Then**:
- Store 카드 상단에 "올룰로 코리아" Badge가 표시됨
- Badge는 primary 색상으로 표시됨

**검증 방법**:
```tsx
// @TEST:STORE-LIST-001:UI
test('Organization Badge를 표시한다', () => {
  const stores = [
    {
      id: 1,
      name: '올룰로 강남점',
      organization: { name: '올룰로 코리아' }
    }
  ];

  render(<Home stores={{ data: stores }} />);

  expect(screen.getByText('올룰로 코리아')).toBeInTheDocument();
  expect(screen.getByText('올룰로 코리아')).toHaveClass('badge-primary');
});
```

---

### Scenario 8: 다국어 지원 (한국어 → 영어)

**Given**:
- 사용자의 언어 설정이 한국어(ko)

**When**:
- 사용자가 언어를 영어(en)로 변경

**Then**:
- "상점 목록" → "Store List"
- "상점 검색..." → "Search store..."
- "검색 결과가 없습니다" → "No results found"

**검증 방법**:
```tsx
// @TEST:STORE-LIST-001:UI
test('언어 전환 시 텍스트가 변경된다', () => {
  const { rerender } = render(<Home stores={{ data: [] }} />, {
    wrapper: ({ children }) => (
      <I18nextProvider i18n={i18nKo}>{children}</I18nextProvider>
    )
  });

  expect(screen.getByText('상점 목록')).toBeInTheDocument();

  rerender(
    <I18nextProvider i18n={i18nEn}>
      <Home stores={{ data: [] }} />
    </I18nextProvider>
  );

  expect(screen.getByText('Store List')).toBeInTheDocument();
});
```

---

### Scenario 9: 반응형 디자인 (모바일/데스크톱)

**Given**:
- Store 6개가 존재

**When**:
- 사용자가 모바일(375px)에서 접근

**Then**:
- Store 카드가 1열로 표시됨

**When**:
- 사용자가 데스크톱(1280px)에서 접근

**Then**:
- Store 카드가 3열로 표시됨

**검증 방법**:
```tsx
// @TEST:STORE-LIST-001:UI
test('반응형 그리드가 올바르게 동작한다', () => {
  const stores = Array.from({ length: 6 }, (_, i) => ({
    id: i + 1,
    name: `Store ${i + 1}`,
    organization: { name: 'Org' }
  }));

  window.innerWidth = 375; // 모바일
  const { container, rerender } = render(<Home stores={{ data: stores }} />);
  expect(container.querySelector('.grid-cols-1')).toBeInTheDocument();

  window.innerWidth = 1280; // 데스크톱
  rerender(<Home stores={{ data: stores }} />);
  expect(container.querySelector('.grid-cols-3')).toBeInTheDocument();
});
```

---

### Scenario 10: 스켈레톤 UI (로딩 상태)

**Given**:
- Store 목록 로딩이 진행 중

**When**:
- 사용자가 `/` 경로에 접근

**Then**:
- 스켈레톤 UI (회색 placeholder)가 표시됨
- Store 카드 영역에 애니메이션 표시

**검증 방법**:
```tsx
// @TEST:STORE-LIST-001:UI
test('로딩 중 스켈레톤 UI를 표시한다', () => {
  render(<Home stores={{ data: [], loading: true }} />);

  expect(screen.getAllByTestId('skeleton-card')).toHaveLength(10);
});
```

---

## 품질 게이트 기준

### 기능 요구사항
- [ ] 활성 Store만 목록에 표시됨
- [ ] Organization 정보가 함께 표시됨
- [ ] 검색 필터가 동작함
- [ ] 페이지네이션이 동작함
- [ ] 다국어 지원 (ko/es/en)

### 성능 요구사항
- [ ] N+1 쿼리 없음 (쿼리 ≤ 2회)
- [ ] 응답 시간 < 200ms
- [ ] LCP < 2.5s (모바일)

### 보안 요구사항
- [ ] 비활성 Store는 목록에 포함되지 않음
- [ ] XSS 방지 (React 자동 이스케이프)

### UI/UX 요구사항
- [ ] ref/CompanyListPage.tsx 디자인 준수
- [ ] 반응형 그리드 (모바일 1열, 데스크톱 3열)
- [ ] 스켈레톤 UI 표시
- [ ] 빈 상태 메시지 표시

### 테스트 요구사항
- [ ] Backend Feature Test 통과 (최소 4개)
- [ ] Frontend Component Test 통과 (최소 6개)
- [ ] 테스트 커버리지 ≥ 85%

### 코드 품질 요구사항
- [ ] `@CODE:STORE-LIST-001` TAG 추가
- [ ] TypeScript 타입 정의
- [ ] ESLint/Prettier 통과
- [ ] 함수 ≤ 50 LOC

### 문서 요구사항
- [ ] SPEC 문서 최신화
- [ ] 코드 주석 추가 (TAG 체인)
- [ ] README 업데이트 (선택)

---

## 검증 방법

### 자동 검증
```bash
# Backend Test
php artisan test --filter STORE-LIST-001

# Frontend Test
npm run test -- STORE-LIST-001

# N+1 쿼리 확인
php artisan debugbar:queries
```

### 수동 검증
1. 브라우저에서 `/` 접근
2. 활성 Store 목록 확인
3. 검색창에 "강남" 입력 → 필터링 확인
4. 언어 전환 (ko → en) → 텍스트 변경 확인
5. 모바일/데스크톱 반응형 확인

### 코드 리뷰 체크리스트
- [ ] Eager Loading 적용 확인
- [ ] `is_active` 필터 확인
- [ ] 다국어 키 추가 확인
- [ ] ref/ 디자인 준수 확인
- [ ] CustomerLayout 수정 없음 확인

---

## 완료 조건 (Definition of Done)

### 필수 조건
- ✅ 모든 테스트 시나리오 통과 (10개)
- ✅ 품질 게이트 기준 충족 (6개 카테고리)
- ✅ 코드 리뷰 승인 (1명 이상)
- ✅ `@CODE:STORE-LIST-001` TAG 추가

### 선택 조건
- ⚠️ E2E 테스트 추가 (향후)
- ⚠️ 성능 모니터링 설정 (향후)

---

**작성자**: @Goos
**최종 수정**: 2025-10-19

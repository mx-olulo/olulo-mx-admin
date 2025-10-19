# 구현 계획: AUTH-REDIRECT-001

> **SPEC**: @SPEC:AUTH-REDIRECT-001 - 인증 후 지능형 테넌트 리다이렉트 시스템
>
> **목표**: 로그인 후 사용자 경험 개선 - 테넌트 수에 따른 자동 리다이렉트 및 계류페이지 제공

---

## 우선순위별 마일스톤

### Phase 1: AuthController 리다이렉트 로직 개선 (우선순위: High)
**목표**: 로그인 성공 시 테넌트 수 기반 리다이렉트 로직 구현

**작업 항목**:
1. `AuthController::redirectAfterLogin()` 메서드 추가
2. `countUserTenants()` 헬퍼 메서드 구현
3. `redirectToSingleTenant()` 자동 리다이렉트 로직 구현
4. 라우트 설정 (`routes/web.php`)

**완료 조건**:
- 테넌트 0개 → 온보딩 리다이렉트 작동
- 테넌트 1개 → 자동 패널 리다이렉트 작동
- 테넌트 2개+ → 계류페이지 리다이렉트 작동

---

### Phase 2: TenantSelector 계류페이지 생성 (우선순위: High)
**목표**: 여러 테넌트 소속 시 선택할 수 있는 UI 제공

**작업 항목**:
1. `TenantSelectorController` 생성
2. `tenant-selector.blade.php` 뷰 생성
3. 탭 컴포넌트 구현 (Organization/Store/Brand)
4. 테넌트 카드 컴포넌트 생성
5. Brand 제약사항 적용 (생성 버튼 제거)

**완료 조건**:
- 3가지 테넌트 타입 탭 표시
- 각 탭에 소속 테넌트 목록 표시
- Organization/Store 생성 버튼 작동
- Brand 생성 버튼 없음
- Brand 탭에 안내 메시지 표시

---

### Phase 3: 온보딩 통합 (우선순위: Medium)
**목표**: 테넌트 0개 또는 특정 타입 없을 때 온보딩 위저드 연결

**작업 항목**:
1. 온보딩 라우트 연결 (`onboarding.organization`, `onboarding.store`)
2. `OnboardingService` 호출 검증
3. 온보딩 완료 후 리다이렉트 처리

**완료 조건**:
- Organization 온보딩 시작 가능
- Store 온보딩 시작 가능
- 온보딩 완료 후 해당 패널로 이동

---

### Phase 4: 권한 검증 및 보안 강화 (우선순위: High)
**목표**: 무단 테넌트 접근 차단

**작업 항목**:
1. `authorizeTenantAccess()` 메서드 구현
2. 멤버십 검증 로직 추가
3. 403 에러 핸들링

**완료 조건**:
- 권한 없는 테넌트 접근 시 403 반환
- 에러 메시지 표시

---

### Phase 5: 테스트 작성 (우선순위: High)
**목표**: TDD RED-GREEN-REFACTOR 사이클 완료

**작업 항목**:
1. Feature 테스트: `tests/Feature/Auth/RedirectTest.php`
2. Unit 테스트: `tests/Unit/TenantSelectorTest.php`
3. 시나리오별 테스트 케이스 15개 작성

**완료 조건**:
- 테스트 커버리지 ≥ 85%
- 모든 시나리오 테스트 통과

---

## 기술적 접근 방법

### 1. 테넌트 멤버십 확인
**현재 코드 분석** (app/Models/User.php):
```php
// 예상되는 관계 (381 LOC 중)
public function organizations(): BelongsToMany
{
    return $this->belongsToMany(Organization::class);
}

public function stores(): BelongsToMany
{
    return $this->belongsToMany(Store::class);
}

public function brands(): BelongsToMany
{
    return $this->belongsToMany(Brand::class);
}
```

**활용 방법**:
- `$user->organizations()->count()` - Organization 수 확인
- `$user->stores()->count()` - Store 수 확인
- `$user->brands()->count()` - Brand 수 확인

### 2. 리다이렉트 로직 최적화
**성능 고려사항**:
- 테넌트 수 확인 쿼리 1회로 제한
- Eager Loading 사용 (`$user->load(['organizations', 'stores', 'brands'])`)
- 캐싱 고려 (세션에 최근 방문 테넌트 저장)

### 3. UI/UX 설계
**Filament 컴포넌트 활용**:
- Filament 스타일 가이드 준수 (Tailwind CSS)
- 반응형 디자인 (모바일/데스크톱)
- 접근성 준수 (ARIA 레이블)

**Brand 제약사항 UI**:
```blade
{{-- Brand 탭 --}}
@if($brands->isEmpty())
    <div class="empty-state">
        <p>소속된 Brand가 없습니다.</p>
        <p class="text-gray-500">
            Brand는 Organization 패널에서 생성할 수 있습니다.
        </p>
    </div>
@endif
```

---

## 아키텍처 설계

### 컴포넌트 다이어그램
```
┌─────────────────────────────────────────────┐
│          AuthController                     │
│  - redirectAfterLogin()                     │
│  - countUserTenants()                       │
│  - redirectToSingleTenant()                 │
└─────────────┬───────────────────────────────┘
              │
              ├─ 0개 → OnboardingService
              ├─ 1개 → Panel 직접 리다이렉트
              └─ 2개+ → TenantSelectorController
                        │
                        └─────────────────────┐
                        ┌─────────────────────▼─────┐
                        │  TenantSelectorController │
                        │  - index()                │
                        │  - selectTenant()         │
                        │  - authorizeTenantAccess()│
                        └─────────────┬─────────────┘
                                      │
                                      ▼
                        ┌──────────────────────────┐
                        │  tenant-selector.blade   │
                        │  - Organization 탭       │
                        │  - Store 탭              │
                        │  - Brand 탭 (생성X)     │
                        └──────────────────────────┘
```

### 데이터 흐름
```
1. 로그인 성공
   ↓
2. AuthController::redirectAfterLogin($user)
   ↓
3. 테넌트 수 확인 (countUserTenants)
   ├─ 0개 → redirect('/onboarding/organization')
   ├─ 1개 → redirect('/{tenant_type}/{id}')
   └─ 2개+ → redirect('/tenant/selector')
       ↓
4. TenantSelectorController::index()
   ↓
5. 사용자 테넌트 선택
   ↓
6. TenantSelectorController::selectTenant()
   ↓
7. 권한 검증 (authorizeTenantAccess)
   ↓
8. redirect('/{tenant_type}/{id}')
```

---

## 리스크 및 대응 방안

### 1. 복잡한 멤버십 관계
**리스크**: 사용자가 100개 이상 테넌트 소속 시 쿼리 성능 저하

**대응 방안**:
- Eager Loading 사용
- 페이지네이션 추가 (각 탭당 10개씩 표시)
- 검색 기능 추가 (Phase 2+ 고려)

### 2. Brand 제약사항 누락
**리스크**: 계류페이지에 Brand 생성 버튼 실수로 노출

**대응 방안**:
- 테스트 케이스 작성 (Brand 생성 버튼 없음 확인)
- UI 리뷰 체크리스트 작성
- 정책 문서화 (docs/auth/brand-policy.md)

### 3. 세션 만료
**리스크**: 계류페이지 표시 중 세션 만료 시 무한 루프

**대응 방안**:
- 미들웨어로 세션 유효성 확인
- 세션 만료 시 로그인 페이지로 명시적 리다이렉트

### 4. 온보딩 중단
**리스크**: 온보딩 시작 후 중단 시 복귀 경로 불명확

**대응 방안**:
- 온보딩 진행 상태 세션에 저장
- "나중에 완료하기" 버튼 제공
- 계류페이지로 돌아가기 링크 제공

---

## 성능 목표

- 리다이렉트 결정: < 100ms
- 계류페이지 로드: < 500ms
- 테넌트 선택 후 리다이렉트: < 200ms

---

## 확장 가능성 (Future Work)

- 최근 방문 테넌트 자동 선택 (선택 필드)
- 즐겨찾기 테넌트 기능
- 테넌트 검색 기능
- 다국어 지원 (i18n 통합)

---

**다음 단계**: `/alfred:2-build AUTH-REDIRECT-001` 실행 → TDD 구현

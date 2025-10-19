# 인수 기준: AUTH-REDIRECT-001

> **SPEC**: @SPEC:AUTH-REDIRECT-001 - 인증 후 지능형 테넌트 리다이렉트 시스템
>
> **목적**: 로그인 성공 후 사용자 경험을 테넌트 수에 따라 최적화

---

## Given-When-Then 시나리오

### 시나리오 1: 신규 사용자 (테넌트 0개)
**Given**: 사용자가 로그인했고, 어떤 테넌트에도 소속되지 않음
**When**: 로그인 성공
**Then**:
- Organization 온보딩 페이지로 리다이렉트됨
- URL: `/onboarding/organization`
- 200 상태 코드 반환

---

### 시나리오 2: 단일 Organization 소속
**Given**: 사용자가 정확히 1개 Organization에 소속됨
**When**: 로그인 성공
**Then**:
- 해당 Organization 패널로 자동 리다이렉트됨
- URL: `/organization/{id}`
- 계류페이지를 거치지 않음

---

### 시나리오 3: 단일 Store 소속
**Given**: 사용자가 정확히 1개 Store에 소속됨
**When**: 로그인 성공
**Then**:
- 해당 Store 패널로 자동 리다이렉트됨
- URL: `/store/{id}`
- 계류페이지를 거치지 않음

---

### 시나리오 4: 단일 Brand 소속
**Given**: 사용자가 정확히 1개 Brand에 소속됨
**When**: 로그인 성공
**Then**:
- 해당 Brand 패널로 자동 리다이렉트됨
- URL: `/brand/{id}`
- 계류페이지를 거치지 않음

---

### 시나리오 5: 복수 테넌트 소속 (2개 Organization)
**Given**: 사용자가 2개 Organization에 소속됨
**When**: 로그인 성공
**Then**:
- 테넌트 계류페이지로 리다이렉트됨
- URL: `/tenant/selector`
- Organization 탭에 2개 카드 표시됨
- "+ Organization 생성" 버튼 표시됨

---

### 시나리오 6: 복수 테넌트 소속 (혼합)
**Given**: 사용자가 1개 Organization, 2개 Store, 1개 Brand에 소속됨 (총 4개)
**When**: 로그인 성공
**Then**:
- 테넌트 계류페이지로 리다이렉트됨
- Organization 탭: 1개 카드 + 생성 버튼
- Store 탭: 2개 카드 + 생성 버튼
- Brand 탭: 1개 카드 + **생성 버튼 없음**

---

### 시나리오 7: 계류페이지에서 Organization 선택
**Given**: 사용자가 계류페이지의 Organization 탭을 보고 있음
**When**: 특정 Organization 카드 클릭
**Then**:
- 해당 Organization 패널로 리다이렉트됨
- URL: `/organization/{id}`
- 302 상태 코드 반환

---

### 시나리오 8: 계류페이지에서 Store 선택
**Given**: 사용자가 계류페이지의 Store 탭을 보고 있음
**When**: 특정 Store 카드 클릭
**Then**:
- 해당 Store 패널로 리다이렉트됨
- URL: `/store/{id}`
- 302 상태 코드 반환

---

### 시나리오 9: 계류페이지에서 Brand 선택
**Given**: 사용자가 계류페이지의 Brand 탭을 보고 있음
**When**: 특정 Brand 카드 클릭
**Then**:
- 해당 Brand 패널로 리다이렉트됨
- URL: `/brand/{id}`
- 302 상태 코드 반환

---

### 시나리오 10: Brand 생성 버튼 없음 (핵심 제약)
**Given**: 사용자가 계류페이지의 Brand 탭을 보고 있음
**When**: 페이지 렌더링 완료
**Then**:
- "+ Brand 생성" 버튼이 **표시되지 않음**
- 안내 메시지 표시: "Brand는 Organization 패널에서 생성할 수 있습니다."
- DOM에 `button[data-action="create-brand"]` 요소 없음

---

### 시나리오 11: 권한 없는 테넌트 접근 차단
**Given**: 사용자가 Organization A에만 소속됨
**When**: Organization B로 직접 접근 시도 (`/organization/999`)
**Then**:
- 403 Forbidden 에러 반환
- 에러 메시지: "You do not have access to this tenant."
- 로그인 페이지로 리다이렉트되지 않음

---

### 시나리오 12: Organization 생성 버튼 클릭
**Given**: 사용자가 계류페이지의 Organization 탭을 보고 있음
**When**: "+ Organization 생성" 버튼 클릭
**Then**:
- Organization 온보딩 위저드로 리다이렉트됨
- URL: `/onboarding/organization`
- 200 상태 코드 반환

---

### 시나리오 13: Store 생성 버튼 클릭
**Given**: 사용자가 계류페이지의 Store 탭을 보고 있음
**When**: "+ Store 생성" 버튼 클릭
**Then**:
- Store 온보딩 위저드로 리다이렉트됨
- URL: `/onboarding/store`
- 200 상태 코드 반환

---

### 시나리오 14: 빈 테넌트 타입 안내 (Organization 없음)
**Given**: 사용자가 1개 Store에만 소속됨 (Organization 0개)
**When**: 계류페이지의 Organization 탭 클릭
**Then**:
- "소속된 Organization이 없습니다." 메시지 표시
- "+ Organization 생성" 버튼 표시
- 카드 목록은 비어있음

---

### 시나리오 15: 빈 테넌트 타입 안내 (Brand 없음)
**Given**: 사용자가 1개 Organization에만 소속됨 (Brand 0개)
**When**: 계류페이지의 Brand 탭 클릭
**Then**:
- "소속된 Brand가 없습니다." 메시지 표시
- "+ Brand 생성" 버튼 **표시되지 않음**
- 안내 메시지: "Brand는 Organization 패널에서 생성할 수 있습니다."

---

### 시나리오 16: 세션 만료 후 계류페이지 접근
**Given**: 사용자의 세션이 만료됨
**When**: `/tenant/selector` URL 직접 접근
**Then**:
- 로그인 페이지로 리다이렉트됨
- 302 상태 코드 반환
- 에러 메시지: "세션이 만료되었습니다. 다시 로그인하세요."

---

### 시나리오 17: 직접 테넌트 URL 접근 (권한 있음)
**Given**: 사용자가 Organization A에 소속됨
**When**: `/organization/A` URL 직접 접근
**Then**:
- Organization A 패널 접근 허용
- 200 상태 코드 반환
- 계류페이지를 거치지 않음

---

### 시나리오 18: 온보딩 완료 후 리다이렉트
**Given**: 사용자가 Organization 온보딩을 완료함
**When**: 온보딩 위저드 마지막 단계 완료
**Then**:
- 생성된 Organization 패널로 리다이렉트됨
- URL: `/organization/{new_id}`
- 성공 메시지: "Organization이 생성되었습니다."

---

## 품질 게이트 기준

### 1. 코드 품질
- [ ] `AuthController` 리다이렉트 로직 ≤ 300 LOC
- [ ] `TenantSelectorController` ≤ 50 LOC
- [ ] 복잡도 ≤ 10 (Cyclomatic Complexity)
- [ ] 함수당 ≤ 50 LOC
- [ ] 매개변수 ≤ 5개

### 2. 테스트 커버리지
- [ ] 전체 커버리지 ≥ 85%
- [ ] Feature 테스트 18개 이상 작성
- [ ] Unit 테스트 10개 이상 작성
- [ ] Brand 제약사항 테스트 5개 이상

### 3. 보안
- [ ] 모든 테넌트 접근에 권한 검증 적용
- [ ] SQL Injection 방지 (Eloquent ORM 사용)
- [ ] XSS 방지 (Blade escaping 사용)
- [ ] CSRF 보호 (Laravel 기본 설정)

### 4. 성능
- [ ] 리다이렉트 결정 < 100ms
- [ ] 계류페이지 로드 < 500ms
- [ ] 데이터베이스 쿼리 ≤ 3회 (Eager Loading)

### 5. 접근성
- [ ] ARIA 레이블 모든 버튼에 추가
- [ ] 키보드 네비게이션 지원
- [ ] 색상 대비비 4.5:1 이상
- [ ] 스크린 리더 테스트 통과

### 6. 다국어 지원 (선택)
- [ ] 모든 UI 텍스트 i18n 키로 대체
- [ ] 한국어/영어 번역 파일 생성

---

## 검증 방법 및 도구

### 1. Feature 테스트
**파일**: `tests/Feature/Auth/RedirectTest.php`

```php
// @TEST:AUTH-REDIRECT-001 | SPEC: SPEC-AUTH-REDIRECT-001.md

public function test_redirect_to_onboarding_when_no_tenants()
{
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post('/login');

    $response->assertRedirect('/onboarding/organization');
}

public function test_auto_redirect_when_single_tenant()
{
    $user = User::factory()->create();
    $org = Organization::factory()->create();
    $user->organizations()->attach($org);
    $this->actingAs($user);

    $response = $this->post('/login');

    $response->assertRedirect("/organization/{$org->id}");
}

public function test_show_selector_when_multiple_tenants()
{
    $user = User::factory()->create();
    Organization::factory(2)->create()->each(fn($org) => $user->organizations()->attach($org));
    $this->actingAs($user);

    $response = $this->post('/login');

    $response->assertRedirect('/tenant/selector');
}

public function test_brand_tab_has_no_create_button()
{
    $user = User::factory()->create();
    Organization::factory(2)->create()->each(fn($org) => $user->organizations()->attach($org));
    $this->actingAs($user);

    $response = $this->get('/tenant/selector');

    $response->assertDontSee('+ Brand 생성');
    $response->assertSee('Brand는 Organization 패널에서 생성할 수 있습니다.');
}

public function test_unauthorized_tenant_access_forbidden()
{
    $user = User::factory()->create();
    $otherOrg = Organization::factory()->create();
    $this->actingAs($user);

    $response = $this->get("/organization/{$otherOrg->id}");

    $response->assertStatus(403);
    $response->assertSeeText('You do not have access to this tenant.');
}
```

### 2. Unit 테스트
**파일**: `tests/Unit/TenantSelectorTest.php`

```php
public function test_count_user_tenants()
{
    $user = User::factory()->create();
    Organization::factory(2)->create()->each(fn($org) => $user->organizations()->attach($org));
    Store::factory(1)->create()->each(fn($store) => $user->stores()->attach($store));

    $controller = new AuthController();
    $count = $controller->countUserTenants($user);

    $this->assertEquals(3, $count);
}
```

### 3. 수동 테스트 체크리스트
- [ ] 신규 사용자 온보딩 흐름 테스트
- [ ] 3가지 테넌트 타입 각각 단일 소속 테스트
- [ ] 복수 테넌트 계류페이지 UI 확인
- [ ] Brand 생성 버튼 없음 확인
- [ ] 권한 없는 접근 차단 확인
- [ ] 온보딩 완료 후 리다이렉트 확인

### 4. 린터 및 정적 분석
```bash
# PHP CS Fixer
php-cs-fixer fix app/Http/Controllers/Auth/AuthController.php

# PHPStan
phpstan analyse app/Http/Controllers/Auth --level=8

# Larastan
./vendor/bin/phpstan analyse
```

---

## 완료 조건 (Definition of Done)

### 필수 조건
- [x] SPEC 문서 작성 완료 (spec.md)
- [ ] 구현 계획 작성 완료 (plan.md)
- [ ] 인수 기준 작성 완료 (acceptance.md)
- [ ] TDD RED-GREEN-REFACTOR 완료
  - [ ] RED: 18개 Feature 테스트 작성 및 실패 확인
  - [ ] GREEN: 구현 완료 및 테스트 통과
  - [ ] REFACTOR: 코드 품질 개선 완료
- [ ] 테스트 커버리지 ≥ 85%
- [ ] Brand 제약사항 테스트 5개 통과
- [ ] 권한 검증 테스트 통과
- [ ] 문서 동기화 완료 (`/alfred:3-sync`)

### 선택 조건
- [ ] 다국어 지원 추가 (i18n)
- [ ] 최근 방문 테넌트 자동 선택 기능
- [ ] 접근성 테스트 통과 (스크린 리더)

### 검증 단계
1. **코드 리뷰**:
   - [ ] TRUST 5원칙 준수 확인
   - [ ] @TAG 체인 완전성 검증
   - [ ] Brand 제약사항 누락 없음 확인

2. **테스트 실행**:
   ```bash
   php artisan test --filter=RedirectTest
   php artisan test --coverage --min=85
   ```

3. **수동 QA**:
   - [ ] 로컬 환경 테스트
   - [ ] 스테이징 환경 배포
   - [ ] 실제 사용자 시나리오 검증

4. **문서 업데이트**:
   - [ ] README.md 업데이트
   - [ ] docs/auth/redirect.md 생성
   - [ ] CHANGELOG.md 항목 추가

---

## 릴리스 기준

### v0.0.1 (Draft)
- SPEC 문서 작성 완료
- 계획 및 인수 기준 수립

### v0.1.0 (TDD 완료)
- RED-GREEN-REFACTOR 완료
- 모든 테스트 통과
- 문서 동기화 완료

### v1.0.0 (프로덕션 준비)
- 스테이징 환경 배포 완료
- 사용자 승인 획득
- 성능 목표 달성
- 접근성 검증 완료

---

**다음 단계**: `/alfred:2-build AUTH-REDIRECT-001` 실행 → TDD 구현

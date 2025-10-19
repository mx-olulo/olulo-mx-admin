# 수락 기준: TENANCY-AUTHZ-001

> **멀티 테넌시 패널 접근 권한 검증 로직 개선**

---

## Given-When-Then 테스트 시나리오

### 시나리오 1: 온보딩 위자드 접근 허용 (신규 사용자)

**Given**:
- 사용자가 인증되어 있음
- 사용자가 어떤 테넌트에도 속하지 않음
- App 패널에 접근 시도

**When**:
- 현재 URL이 `/app/onboarding`임
- `canAccessPanel('app')` 호출

**Then**:
- 메서드가 `true` 반환
- 온보딩 위자드 페이지 표시
- 데이터베이스 쿼리 실행 안 함 (멤버십 검증 스킵)

**검증 코드**:
$user = User::factory()->create();
$panel = Filament::getPanel('app');

// URL 모킹
request()->setUrl('/app/onboarding');

expect($user->canAccessPanel($panel))->toBeTrue();

---

### 시나리오 2: 대시보드 접근 거부 (테넌트 없음)

**Given**:
- 사용자가 인증되어 있음
- 사용자가 어떤 테넌트에도 속하지 않음
- 테넌트 컨텍스트가 설정되지 않음

**When**:
- 현재 URL이 `/app` (대시보드)임
- `canAccessPanel('app')` 호출

**Then**:
- 메서드가 `false` 반환
- 접근 거부 페이지 또는 리다이렉트
- 에러 로그 없음 (정상적인 거부)

**검증 코드**:
$user = User::factory()->create();
$panel = Filament::getPanel('app');

// 테넌트 컨텍스트 없음
tenancy()->end();

expect($user->canAccessPanel($panel))->toBeFalse();

---

### 시나리오 3: 테넌트 멤버십 검증 (정상 접근)

**Given**:
- 사용자가 인증되어 있음
- 사용자가 Tenant A의 멤버임
- 테넌트 컨텍스트가 Tenant A로 설정됨

**When**:
- App 패널 접근 시도
- `canAccessPanel('app')` 호출

**Then**:
- 메서드가 `true` 반환
- 대시보드 페이지 표시
- 데이터베이스 쿼리 1회만 실행

**검증 코드**:
$user = User::factory()->create();
$tenant = Tenant::factory()->create();
$user->tenants()->attach($tenant);

tenancy()->initialize($tenant);
$panel = Filament::getPanel('app');

DB::enableQueryLog();
expect($user->canAccessPanel($panel))->toBeTrue();
expect(DB::getQueryLog())->toHaveCount(1); // 쿼리 최적화 검증

---

### 시나리오 4: 멤버십 없는 테넌트 접근 거부

**Given**:
- 사용자가 인증되어 있음
- 사용자가 Tenant A의 멤버임
- 테넌트 컨텍스트가 Tenant B로 설정됨 (멤버가 아님)

**When**:
- App 패널 접근 시도
- `canAccessPanel('app')` 호출

**Then**:
- 메서드가 `false` 반환
- 접근 거부
- 보안 위반 로그 기록 (선택)

**검증 코드**:
$user = User::factory()->create();
$tenantA = Tenant::factory()->create();
$tenantB = Tenant::factory()->create();
$user->tenants()->attach($tenantA);

tenancy()->initialize($tenantB); // 멤버가 아닌 테넌트
$panel = Filament::getPanel('app');

expect($user->canAccessPanel($panel))->toBeFalse();

---

### 시나리오 5: 쿼리 최적화 검증 (canAccessTenant)

**Given**:
- 사용자가 10개 테넌트의 멤버임
- 특정 테넌트 접근 권한 확인

**When**:
- `canAccessTenant($tenant)` 호출

**Then**:
- 정확히 1개 쿼리만 실행
- 쿼리가 EXISTS 절을 사용
- 결과가 올바름 (true/false)

**검증 코드**:
$user = User::factory()->create();
$tenants = Tenant::factory()->count(10)->create();
$user->tenants()->attach($tenants);

$targetTenant = $tenants->first();

DB::enableQueryLog();
$result = $user->canAccessTenant($targetTenant);
$queries = DB::getQueryLog();

expect($queries)->toHaveCount(1);
expect($queries[0]['query'])->toContain('exists');
expect($result)->toBeTrue();

---

### 시나리오 6: Public 패널 접근 제어

**Given**:
- 사용자가 인증되어 있음
- 테넌트 컨텍스트가 설정됨

**When**:
- Public 패널 접근 시도
- `canAccessPanel('public')` 호출

**Then**:
- 테넌트 멤버인 경우 `true` 반환
- 멤버가 아닌 경우 `false` 반환

**검증 코드**:
$user = User::factory()->create();
$tenant = Tenant::factory()->create();
$user->tenants()->attach($tenant);

tenancy()->initialize($tenant);
$panel = Filament::getPanel('public');

expect($user->canAccessPanel($panel))->toBeTrue();

---

## 품질 게이트 기준

### 기능 요구사항
- [ ] **TC-001**: 온보딩 위자드 접근 허용 (신규 사용자)
- [ ] **TC-002**: 대시보드 접근 거부 (테넌트 없음)
- [ ] **TC-003**: 테넌트 멤버십 검증 (정상 접근)
- [ ] **TC-004**: 멤버십 없는 테넌트 접근 거부
- [ ] **TC-005**: 쿼리 최적화 검증 (1개 쿼리)
- [ ] **TC-006**: Public 패널 접근 제어

### 성능 요구사항
- [ ] `canAccessTenant()` 실행 시 쿼리 1회만 실행
- [ ] EXISTS 쿼리 사용으로 메모리 사용량 최소화
- [ ] 평균 응답 시간 < 10ms (로컬 환경)

### 보안 요구사항
- [ ] 테넌트 격리 보장 (다른 테넌트 리소스 접근 불가)
- [ ] 온보딩 위자드에서도 인증 상태 유지
- [ ] SQL Injection 방지 (Eloquent ORM 사용)

### 코드 품질 요구사항
- [ ] 메서드 복잡도 ≤10 (Cyclomatic Complexity)
- [ ] 메서드 길이 ≤50 LOC
- [ ] PHPStan level 5 통과
- [ ] 테스트 커버리지 ≥95%

---

## 검증 방법 및 도구

### 자동화된 테스트
**도구**: PHPUnit + Pest
**실행 명령**:
./vendor/bin/pest tests/Feature/UserTenancyTest.php

**커버리지 측정**:
./vendor/bin/pest --coverage --min=95

### 쿼리 성능 측정
**도구**: Laravel Debugbar / Telescope
**검증 항목**:
- 쿼리 횟수: `DB::getQueryLog()` 사용
- 쿼리 타입: EXISTS 절 포함 확인
- 실행 시간: < 10ms

### 정적 분석
**도구**: PHPStan
**실행 명령**:
./vendor/bin/phpstan analyse app/Models/User.php --level=5

### 코드 복잡도 분석
**도구**: PHPMD (PHP Mess Detector)
**기준**:
- Cyclomatic Complexity ≤10
- NPath Complexity ≤200

---

## 완료 조건 (Definition of Done)

### 기능 완료
- [x] 모든 테스트 시나리오 통과 (TC-001 ~ TC-006)
- [x] 실패했던 기존 테스트 3건 통과
- [x] 새로운 온보딩 위자드 테스트 추가

### 성능 완료
- [x] `canAccessTenant()` 쿼리 1회 실행 확인
- [x] 쿼리 성능 벤치마크 통과 (< 10ms)
- [x] 메모리 사용량 개선 검증

### 보안 완료
- [x] 테넌트 격리 테스트 통과
- [x] 권한 우회 시나리오 테스트 (Negative Testing)
- [x] SQL Injection 취약점 검증

### 문서 완료
- [x] User.php 메서드 DocBlock 추가
- [x] @CODE:TENANCY-AUTHZ-001 TAG 추가
- [x] SPEC 문서 HISTORY 업데이트 (v0.1.0)

### 코드 리뷰 완료
- [ ] 팀원 리뷰 승인 (1명 이상)
- [ ] PHPStan 경고 0건
- [ ] 코드 스타일 검사 통과 (PHP-CS-Fixer)

---

**승인 기준**: 모든 체크리스트 항목 완료 시 `/alfred:3-sync` 실행 가능

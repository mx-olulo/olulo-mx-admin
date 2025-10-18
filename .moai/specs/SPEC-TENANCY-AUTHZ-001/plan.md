# 구현 계획: TENANCY-AUTHZ-001

> **멀티 테넌시 패널 접근 권한 검증 로직 개선**

---

## 우선순위별 마일스톤

### 1차 목표: 테스트 실패 원인 해결
- **우선순위**: Critical
- **범위**: User.php 권한 검증 메서드 수정
- **산출물**:
  - `canAccessPanel()` 온보딩 위자드 예외 처리 추가
  - `canAccessTenant()` 쿼리 최적화 (2개 → 1개)
  - 실패한 테스트 3건 통과 확인

### 2차 목표: 엣지 케이스 검증
- **우선순위**: High
- **범위**: 추가 테스트 케이스 작성
- **산출물**:
  - 슈퍼 어드민 권한 테스트
  - 테넌트 컨텍스트 없는 경우 테스트
  - Public 패널 권한 테스트

### 3차 목표: 문서화 및 코드 리뷰
- **우선순위**: Medium
- **범위**: 주석 및 문서 업데이트
- **산출물**:
  - User.php 메서드 DocBlock 보강
  - docs/tenancy/ 권한 정책 문서 업데이트
  - TAG 체인 검증

---

## 기술적 접근 방법

### 1. canAccessPanel() 개선 전략

#### AS-IS (현재 코드)
- 테넌트 멤버십 검증만 수행
- 온보딩 위자드 예외 처리 없음
- 테넌트 컨텍스트 검증 부족

#### TO-BE (개선 코드)
public function canAccessPanel(Panel $panel): bool
{
    return match ($panel->getId()) {
        'admin' => $this->hasRole('super_admin'),
        'app' => $this->canAccessAppPanel(),
        'public' => $this->canAccessPublicPanel(),
        default => false,
    };
}

private function canAccessAppPanel(): bool
{
    // 1. 온보딩 위자드 예외 처리
    if (request()->is('app/onboarding*')) {
        return true;
    }

    // 2. 테넌트 컨텍스트 검증
    $tenant = tenant();
    if (!$tenant) {
        return false;
    }

    // 3. 멤버십 검증
    return $this->canAccessTenant($tenant);
}

private function canAccessPublicPanel(): bool
{
    $tenant = tenant();
    return $tenant && $this->canAccessTenant($tenant);
}

#### 설계 결정 사항
- **Match 표현식 활용**: 패널별 권한 로직 명확히 분리
- **Private 메서드 추출**: 복잡한 조건문을 의미 있는 이름의 메서드로 추출
- **Early Return 패턴**: 가드절 우선 사용으로 중첩 제거

### 2. canAccessTenant() 쿼리 최적화

#### AS-IS (2개 쿼리)
public function canAccessTenant(Tenant $tenant): bool
{
    return $this->tenants->contains($tenant);
}

// 실행 쿼리:
// 1. SELECT * FROM tenant_user WHERE user_id = ?
// 2. Collection::contains() 메모리 체크

#### TO-BE (1개 쿼리)
public function canAccessTenant(Tenant $tenant): bool
{
    return $this->tenants()->where('id', $tenant->id)->exists();
}

// 실행 쿼리:
// SELECT EXISTS(SELECT 1 FROM tenant_user WHERE user_id = ? AND tenant_id = ?)

#### 성능 이점
- **쿼리 횟수**: 2회 → 1회 (50% 감소)
- **메모리 사용량**: 전체 컬렉션 로드 불필요
- **응답 속도**: EXISTS 쿼리가 더 빠름 (인덱스 활용)

### 3. 테스트 전략

#### RED 단계 (실패하는 테스트)
현재 실패 중인 테스트 3건:
1. **UserTenancyTest.php:322** - 쿼리 카운트 검증
2. **UserTenancyTest.php:268** - 테넌트 없는 사용자 접근 거부
3. **UserTenancyTest.php:396** - 멤버십 없는 사용자 접근 거부

#### GREEN 단계 (구현)
- User.php 메서드 수정
- 테스트 통과 확인

#### REFACTOR 단계 (리팩토링)
- Private 메서드 추출
- DocBlock 추가
- 코드 복잡도 검증

---

## 아키텍처 설계 방향

### 계층 구조
User Model (app/Models/User.php)
  ├─ canAccessPanel(Panel $panel): bool
  │   ├─ canAccessAppPanel(): bool (private)
  │   └─ canAccessPublicPanel(): bool (private)
  └─ canAccessTenant(Tenant $tenant): bool

### 의존성 관리
- **Laravel Request**: 온보딩 경로 체크 (`request()->is()`)
- **Filament Panel**: 패널 ID 식별
- **Stancl Tenancy**: 테넌트 컨텍스트 조회 (`tenant()`)
- **Eloquent ORM**: 멤버십 검증 쿼리

### 보안 고려사항
1. **최소 권한 원칙**: 기본적으로 접근 거부, 명시적 허용만
2. **테넌트 격리**: 다른 테넌트 리소스 접근 차단
3. **온보딩 보안**: 위자드 경로에서도 인증 상태는 유지

---

## 리스크 및 대응 방안

### 리스크 1: 온보딩 위자드 무한 리다이렉트
- **시나리오**: `/app/onboarding`에서 테넌트 생성 실패 → 다시 리다이렉트
- **대응**: Filament 미들웨어에서 온보딩 경로 화이트리스트 확인

### 리스크 2: 기존 사용자 로그인 실패
- **시나리오**: 테넌트 컨텍스트가 설정되지 않은 상태로 접근
- **대응**: 테넌트 선택 페이지로 리다이렉트 (별도 구현 필요)

### 리스크 3: 성능 저하 (exists() 쿼리)
- **시나리오**: 대량의 사용자/테넌트 관계에서 쿼리 느려짐
- **대응**: `tenant_user` 테이블에 복합 인덱스 생성 (user_id, tenant_id)

---

## 완료 조건 (Definition of Done)

### 기능 완료
- [ ] 실패한 테스트 3건 모두 통과
- [ ] 새로운 테스트 케이스 추가 (온보딩 위자드 예외)
- [ ] `canAccessTenant()` 쿼리 1회 실행 확인

### 품질 완료
- [ ] 코드 복잡도 ≤10
- [ ] 메서드 길이 ≤50 LOC
- [ ] PHPStan level 5 통과

### 문서 완료
- [ ] User.php 메서드 DocBlock 추가
- [ ] @CODE:TENANCY-AUTHZ-001 TAG 추가
- [ ] SPEC 문서 HISTORY 업데이트

---

**다음 단계**: `/alfred:2-build TENANCY-AUTHZ-001` 실행

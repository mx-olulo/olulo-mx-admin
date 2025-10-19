---
id: AUTH-REDIRECT-001
version: 0.1.0
status: completed
created: 2025-10-19
updated: 2025-10-19
author: @Goos
priority: high
category: feature
labels:
  - authentication
  - redirect
  - tenant-selection
  - ux
  - laravel-12
depends_on:
  - TENANCY-AUTHZ-001
  - ONBOARD-001
scope:
  packages:
    - app/Http/Controllers/Auth
    - resources/views/auth
  files:
    - AuthController.php
    - tenant-selector.blade.php
---

# @SPEC:AUTH-REDIRECT-001: 인증 후 지능형 테넌트 리다이렉트 시스템

## HISTORY

### v0.0.1 (2025-10-19)
- **INITIAL**: 인증 후 지능형 테넌트 리다이렉트 시스템 명세 작성
- **AUTHOR**: @Goos
- **REASON**: 로그인 후 사용자 경험 개선 - 테넌트 수에 따른 자동 리다이렉트 및 계류페이지 제공

### v0.1.0 (2025-10-19)
- **CHANGED**: TDD 구현 완료 (RED-GREEN-REFACTOR 완료)
- **AUTHOR**: @Goos (Claude Code)
- **REVIEW**: QA 검증 완료 (95/100 점수)
- **REASON**: 인증 후 지능형 테넌트 리다이렉트 시스템 구현 완료
- **RELATED**:
  - 18개 Feature 테스트 모두 통과
  - PHPStan Level 5 에러 0개
  - 코드 품질: AuthController 278 LOC, TenantSelectorController 47 LOC
  - 보안 검증: 5/5 통과 (권한, SQL Injection, XSS, CSRF, 입력 검증)
  - 성능: 리다이렉트 <80ms, 페이지 로드 <300ms
  - QA 보고서: `.moai/specs/SPEC-AUTH-REDIRECT-001/qa-report.md`
  - Git 커밋: 1fb60bd (TDD 구현), 9f002e5 (LOC 최적화)

---

## Environment (현재 환경 및 문제점)

### 기술 스택
- Laravel 12
- Filament V4
- MySQL 8.0
- Laravel Sanctum (SPA 인증)

### 테넌트 아키텍처
- Organization: 최상위 테넌트
- Store: Organization 하위 테넌트
- Brand: Organization 하위 테넌트 (특수 제약)

### 현재 문제점
- 로그인 성공 후 테넌트 수와 관계없이 일률적 리다이렉트
- 여러 테넌트 소속 시 사용자가 선택할 수 없음
- 테넌트 없는 신규 사용자의 온보딩 경로 불명확
- Brand 생성 권한이 계류페이지에 잘못 노출될 위험

### 관련 SPEC
- @SPEC:TENANCY-AUTHZ-001 (v0.1.0, completed) - 테넌트 권한 시스템
- @SPEC:ONBOARD-001 (v0.1.1, completed) - 온보딩 위저드

---

## Assumptions (전제 조건)

1. 사용자는 Laravel Sanctum으로 인증됨
2. 사용자는 0개 이상의 Organization/Store/Brand에 소속 가능
3. 테넌트 멤버십 정보는 `User` 모델에서 조회 가능
4. 온보딩 시스템(`OnboardingService`)이 구현되어 있음
5. Filament 패널은 `/organization/{id}`, `/store/{id}`, `/brand/{id}` 경로 사용
6. Brand는 Organization 패널에서만 생성 가능 (비즈니스 정책)

---

## Requirements (요구사항)

### Ubiquitous Requirements (기본 요구사항)
- 시스템은 로그인 성공 시 사용자의 테넌트 멤버십을 확인해야 한다
- 시스템은 테넌트 수에 따라 적절한 리다이렉트 경로를 결정해야 한다
- 시스템은 테넌트 계류페이지를 제공해야 한다 (Organization/Store/Brand 탭 3개)
- 시스템은 Brand 신규 생성 버튼을 계류페이지에 표시하지 않아야 한다

### Event-driven Requirements (이벤트 기반)
- WHEN 로그인 성공 AND 모든 테넌트 0개이면, 시스템은 Organization 온보딩으로 리다이렉트해야 한다
- WHEN 로그인 성공 AND 정확히 1개 테넌트 소속이면, 시스템은 해당 테넌트 패널로 자동 리다이렉트해야 한다
- WHEN 로그인 성공 AND 2개 이상 테넌트 소속이면, 시스템은 테넌트 계류페이지로 리다이렉트해야 한다
- WHEN 계류페이지에서 Organization 선택이면, 시스템은 `/organization/{id}`로 이동해야 한다
- WHEN 계류페이지에서 Store 선택이면, 시스템은 `/store/{id}`로 이동해야 한다
- WHEN 계류페이지에서 Brand 선택이면, 시스템은 `/brand/{id}`로 이동해야 한다
- WHEN 계류페이지에서 "Organization 생성" 클릭이면, 시스템은 Organization 온보딩 위저드를 실행해야 한다
- WHEN 계류페이지에서 "Store 생성" 클릭이면, 시스템은 Store 온보딩 위저드를 실행해야 한다
- WHEN 계류페이지에서 권한 없는 테넌트 접근 시도이면, 시스템은 403 에러를 반환해야 한다

### State-driven Requirements (상태 기반)
- WHILE 여러 Organization 소속일 때, 시스템은 Organization 탭에 목록을 표시해야 한다
- WHILE 여러 Store 소속일 때, 시스템은 Store 탭에 목록을 표시해야 한다
- WHILE 여러 Brand 소속일 때, 시스템은 Brand 탭에 목록을 표시해야 한다
- WHILE 특정 테넌트 타입 소속 0개일 때, 시스템은 해당 탭에 "생성하기" 안내를 표시해야 한다 (Brand 제외)
- WHILE 계류페이지 표시 중일 때, 시스템은 세션을 유지해야 한다

### Optional Features (선택적 기능)
- WHERE 사용자가 최근 방문한 테넌트 기록이 있으면, 시스템은 해당 테넌트를 기본 선택할 수 있다
- WHERE 특정 테넌트로 직접 접근 시도이면, 시스템은 권한 확인 후 허용할 수 있다

### Constraints (제약사항)
- IF Brand 탭 표시이면, 시스템은 "Brand 생성" 버튼을 표시하지 않아야 한다
- IF Brand 생성 요청이면, 시스템은 Organization 패널로 리다이렉트해야 한다
- IF 테넌트 선택 시도이면, 시스템은 멤버십 권한 검증을 먼저 수행해야 한다
- IF 세션 만료 상태이면, 시스템은 로그인 페이지로 리다이렉트해야 한다
- 리다이렉트 로직은 300 LOC 이하여야 한다
- 계류페이지 컨트롤러는 50 LOC 이하여야 한다

---

## Specifications (상세 명세)

### 1. AuthController 리다이렉트 로직 개선

**파일**: `app/Http/Controllers/Auth/AuthController.php`

**현재 상태** (추정):
```php
// 로그인 성공 후 단순 리다이렉트
return redirect('/dashboard');
```

**개선 방향**:
```php
// @CODE:AUTH-REDIRECT-001:DOMAIN | SPEC: SPEC-AUTH-REDIRECT-001.md

public function redirectAfterLogin(User $user): RedirectResponse
{
    // 1. 테넌트 멤버십 수 확인
    $tenantCount = $this->countUserTenants($user);

    // 2. 테넌트 0개 → 온보딩
    if ($tenantCount === 0) {
        return redirect()->route('onboarding.organization');
    }

    // 3. 테넌트 1개 → 자동 리다이렉트
    if ($tenantCount === 1) {
        return $this->redirectToSingleTenant($user);
    }

    // 4. 테넛트 2개+ → 계류페이지
    return redirect()->route('tenant.selector');
}

private function countUserTenants(User $user): int
{
    return $user->organizations()->count()
         + $user->stores()->count()
         + $user->brands()->count();
}

private function redirectToSingleTenant(User $user): RedirectResponse
{
    if ($org = $user->organizations()->first()) {
        return redirect("/organization/{$org->id}");
    }
    if ($store = $user->stores()->first()) {
        return redirect("/store/{$store->id}");
    }
    if ($brand = $user->brands()->first()) {
        return redirect("/brand/{$brand->id}");
    }

    throw new \LogicException('No tenant found');
}
```

### 2. TenantSelector 계류페이지

**파일**: `resources/views/auth/tenant-selector.blade.php`

**UI 구조**:
- 탭 3개: Organization / Store / Brand
- 각 탭: 테넌트 카드 목록 (이름, 설명, 멤버 수)
- 생성 버튼: Organization (+), Store (+), Brand (없음)

**Blade 템플릿 구조**:
```blade
{{-- @CODE:AUTH-REDIRECT-001:UI | SPEC: SPEC-AUTH-REDIRECT-001.md --}}

<div class="tenant-selector-container">
    <h2>테넌트 선택</h2>

    <x-tabs>
        <x-tab name="organization" label="Organization">
            @forelse($organizations as $org)
                <x-tenant-card
                    :tenant="$org"
                    :url="route('organization.panel', $org->id)"
                />
            @empty
                <p>소속된 Organization이 없습니다.</p>
            @endforelse

            <x-button href="{{ route('onboarding.organization') }}">
                + Organization 생성
            </x-button>
        </x-tab>

        <x-tab name="store" label="Store">
            @forelse($stores as $store)
                <x-tenant-card
                    :tenant="$store"
                    :url="route('store.panel', $store->id)"
                />
            @empty
                <p>소속된 Store가 없습니다.</p>
            @endforelse

            <x-button href="{{ route('onboarding.store') }}">
                + Store 생성
            </x-button>
        </x-tab>

        <x-tab name="brand" label="Brand">
            @forelse($brands as $brand)
                <x-tenant-card
                    :tenant="$brand"
                    :url="route('brand.panel', $brand->id)"
                />
            @empty
                <p>소속된 Brand가 없습니다.</p>
                <p class="text-gray-500">
                    Brand는 Organization 패널에서 생성할 수 있습니다.
                </p>
            @endforelse

            {{-- Brand 생성 버튼 없음 --}}
        </x-tab>
    </x-tabs>
</div>
```

### 3. TenantSelectorController

**파일**: `app/Http/Controllers/TenantSelectorController.php`

```php
// @CODE:AUTH-REDIRECT-001:API | SPEC: SPEC-AUTH-REDIRECT-001.md

class TenantSelectorController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        return view('auth.tenant-selector', [
            'organizations' => $user->organizations,
            'stores' => $user->stores,
            'brands' => $user->brands,
        ]);
    }

    public function selectTenant(Request $request)
    {
        $validated = $request->validate([
            'tenant_type' => 'required|in:organization,store,brand',
            'tenant_id' => 'required|integer',
        ]);

        // 권한 검증
        $this->authorizeTenantAccess(
            $validated['tenant_type'],
            $validated['tenant_id']
        );

        // 리다이렉트
        return redirect("/{$validated['tenant_type']}/{$validated['tenant_id']}");
    }

    private function authorizeTenantAccess(string $type, int $id): void
    {
        $user = auth()->user();

        $hasAccess = match($type) {
            'organization' => $user->organizations()->where('id', $id)->exists(),
            'store' => $user->stores()->where('id', $id)->exists(),
            'brand' => $user->brands()->where('id', $id)->exists(),
        };

        abort_if(!$hasAccess, 403, 'You do not have access to this tenant.');
    }
}
```

### 4. 온보딩 통합

**기존 파일 활용**:
- `app/Services/OnboardingService.php` (95 LOC)
- `app/Filament/Organization/Pages/OnboardingWizard.php` (80 LOC)
- `app/Filament/Store/Pages/OnboardingWizard.php` (80 LOC)

**라우트 추가**:
```php
// routes/web.php

Route::middleware('auth')->group(function () {
    Route::get('/tenant/selector', [TenantSelectorController::class, 'index'])
        ->name('tenant.selector');
    Route::post('/tenant/select', [TenantSelectorController::class, 'selectTenant'])
        ->name('tenant.select');

    Route::get('/onboarding/organization', [OrganizationOnboardingController::class, 'start'])
        ->name('onboarding.organization');
    Route::get('/onboarding/store', [StoreOnboardingController::class, 'start'])
        ->name('onboarding.store');
});
```

### 5. Brand 제약사항 구현

**계류페이지에서 Brand 생성 버튼 제거**:
- Brand 탭에는 목록만 표시
- "Organization 패널에서 생성" 안내 메시지 표시

**Organization 패널에서 Brand 생성 허용**:
- Organization 패널 → Brand 관리 → "Brand 생성" 버튼 표시
- 권한: Organization 소유자/관리자만 가능

---

## Traceability (@TAG 체인)

### TAG 흐름
```
@SPEC:AUTH-REDIRECT-001
  → @TEST:AUTH-REDIRECT-001 (tests/Feature/Auth/RedirectTest.php)
  → @CODE:AUTH-REDIRECT-001:DOMAIN (AuthController.php)
  → @CODE:AUTH-REDIRECT-001:API (TenantSelectorController.php)
  → @CODE:AUTH-REDIRECT-001:UI (tenant-selector.blade.php)
  → @DOC:AUTH-REDIRECT-001 (docs/auth/redirect.md)
```

### 의존성
- **depends_on**:
  - @SPEC:TENANCY-AUTHZ-001 (테넌트 권한 시스템)
  - @SPEC:ONBOARD-001 (온보딩 위저드)
- **related_specs**:
  - @SPEC:I18N-001 (다국어 지원)

---

## 참고 문서
- CLAUDE.md: MoAI-ADK 워크플로우
- development-guide.md: TRUST 5원칙
- spec-metadata.md: SPEC 메타데이터 표준

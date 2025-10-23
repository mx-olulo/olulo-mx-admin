# 테넌시 — 호스트 기반 미들웨어 설계

> **상태**: ⏸️ 계획 단계 (미구현)
>
> 현재는 **Filament 기반 수동 테넌트 선택 방식**을 사용 중입니다.

**최종 업데이트**: 2025-10-23

---

## 현재 테넌트 선택 방식

### Filament Tenancy (구현 완료)

사용자가 로그인 후 테넌트(Organization/Brand/Store)를 **수동으로 선택**하는 방식:

```
사용자 로그인
    ↓
지능형 리다이렉트 (@CODE:AUTH-REDIRECT-001)
├── 테넌트 0개 → /org/new (온보딩 위자드)
├── 테넌트 1개 → 자동 리다이렉트 (/org/{id}, /store/{id}, /brand/{id})
└── 테넌트 2+개 → /tenant/selector (수동 선택)
    ↓
선택 완료 후
    ↓
/{panel}/{tenant}/dashboard
예: /org/1/dashboard, /store/5/dashboard
```

**장점**:
- ✅ Filament 내장 UI 활용 (테넌트 스위처)
- ✅ URL에 테넌트 ID 명시적 포함
- ✅ 보안 검증 자동 (`canAccessTenant()`)

**단점**:
- ⚠️ URL이 길어짐 (`/org/1/products` vs `menu.dev.olulo.com.mx/products`)
- ⚠️ 고객앱에는 부적합 (서브도메인 기반이 더 자연스러움)

**관련 문서**: [auth/redirect.md](../auth/redirect.md)

---

## 향후 계획: 호스트 기반 테넌시

### 목적

**고객앱 전용 서브도메인 기반 테넌트 식별**:
- 서브도메인(예: `menu.dev.olulo.com.mx`)에서 매장을 자동 식별 (`stores.code`)
- URL을 짧고 간결하게 유지 (`/products` vs `/store/5/products`)
- 모든 쿼리에 `store_id` 자동 스코핑

### 설계 개요

#### 1. 미들웨어 동작

```
HTTP 요청
    ↓
Host 헤더 파싱
├── subdomain.env.olulo.com.mx → subdomain 추출
└── stores.code = subdomain 조회
    ↓
컨텍스트 바인딩
├── app()->instance('tenant', ['store_id' => <id>, 'code' => subdomain])
└── 글로벌 스코프 또는 리포지토리 레벨에서 store_id 주입
```

#### 2. 라우트 적용 (개념)

```php
// routes/customer-api.php
Route::middleware(['tenant.host'])->group(function () {
    Route::get('/menu', [MenuController::class, 'index']);
    Route::get('/products/{id}', [ProductController::class, 'show']);
    Route::post('/orders', [OrderController::class, 'store']);
});
```

#### 3. 컨트롤러 사용

```php
class MenuController extends Controller
{
    public function index()
    {
        // 테넌트 컨텍스트 자동 주입
        $tenant = app('tenant');
        $storeId = $tenant['store_id'];

        // 글로벌 스코프로 자동 필터링
        $products = Product::all(); // WHERE store_id = ?

        return response()->json($products);
    }
}
```

#### 4. 예외 처리

- **매칭 실패**: `stores.code`에 해당하는 서브도메인이 없으면 404 반환
- **상위 도메인 직접 접근**: `olulo.com.mx` 접근 시 랜딩 페이지 또는 차단
- **누락 스코프 탐지**: 테넌시 스코프가 없는 엔드포인트 로깅 (보안 모니터링)

### 테스트 포인트

- ✅ 존재하는 매장 서브도메인 접근 → 컨텍스트 주입 확인
- ✅ 존재하지 않는 서브도메인 접근 → 404
- ✅ 글로벌 스코프 자동 적용 확인 (Product::all() → WHERE store_id = ?)
- ✅ 멀티테넌시 스코프 누락 엔드포인트 탐지 로깅

---

## 구현 로드맵

### Phase 1: 미들웨어 개발 (향후)

1. `app/Http/Middleware/IdentifyTenantByHost.php` 생성
2. Host 헤더 파싱 로직 구현
3. `stores.code` 조회 및 컨텍스트 바인딩
4. 예외 처리 (404, 기본 매장 설정)

### Phase 2: 글로벌 스코프 적용 (향후)

1. `app/Models/Concerns/BelongsToStore.php` Trait 생성
2. Eloquent 글로벌 스코프 등록 (`addGlobalScope()`)
3. 모든 Store 관련 모델에 Trait 적용
4. 테스트 작성 (Feature/TenancyTest.php)

### Phase 3: 보안 모니터링 (향후)

1. 테넌시 스코프 누락 감지 로깅
2. Sentry/CloudWatch 통합
3. 경고 알림 설정

---

## 현재 우선순위

**호스트 기반 테넌시는 현재 우선순위가 낮습니다**:

- ✅ **Filament Tenancy**가 관리자 패널에 충분히 작동함
- ✅ **고객앱 개발은 Phase 2 이후** 시작 예정
- ⏸️ 호스트 기반 테넌시는 고객앱 본격 개발 시 구현

**당장 필요한 작업**:
1. Filament 기반 RBAC 안정화 ✅ (완료)
2. 온보딩 위자드 완성 (진행 중)
3. 지능형 리다이렉트 개선 (진행 중)

---

## 관련 문서

- **[rbac-system.md](../rbac-system.md)**: TenantUser 기반 RBAC (구현 완료)
- **[auth/redirect.md](../auth/redirect.md)**: 지능형 테넌트 리다이렉트 (구현 완료)
- **[whitepaper.md](../whitepaper.md)**: 전체 시스템 설계

---

## 참고 자료

**Laravel 멀티테넌시 패키지**:
- [Spatie Laravel Multitenancy](https://github.com/spatie/laravel-multitenancy)
- [Tenancy for Laravel](https://tenancyforlaravel.com/)

**Filament Tenancy 공식 문서**:
- [Filament Tenancy Guide](https://filamentphp.com/docs/4.x/panels/tenancy)

---

**최종 검토**: 2025-10-23
**작성자**: @Alfred
**상태**: ⏸️ 계획 단계, 현재 Filament Tenancy 사용 중

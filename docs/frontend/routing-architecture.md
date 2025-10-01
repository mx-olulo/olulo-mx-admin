# 고객앱 라우팅 아키텍처

## 문서 목적
고객앱의 라우팅 전략을 정의하고, 서브도메인 기반 테넌시와 경로 기반 페이지 라우팅의 조화로운 설계를 제시합니다.

## 관련 문서
- 이슈 #4 범위 명세: [docs/frontend/issue-4-scope.md](issue-4-scope.md)
- 인증 설계: [docs/auth.md](../auth.md)
- 테넌시 설계: [docs/tenancy/host-middleware.md](../tenancy/host-middleware.md)
- 환경/도메인: [docs/devops/environments.md](../devops/environments.md)

## 설계 원칙

### 1. Hybrid Pattern (서브도메인 + 경로 조합)
고객앱은 **서브도메인 기반 테넌시**와 **경로 기반 기능 라우팅**을 병행합니다.

- **서브도메인**: 매장 식별 (`{store}.olulo.com.mx`)
- **경로**: 기능별 페이지 (`/app`, `/pickup`, `/brands`, `/my`, `/help`)

**예시**:
- `store1.olulo.com.mx/app?table=1&seat=A` → 매장1 테이블 오더
- `store2.olulo.com.mx/pickup` → 매장2 픽업 주문
- `brands.olulo.com.mx` → 브랜드 목록 (전체 플랫폼)

### 2. 예약어 우선순위
특정 경로는 플랫폼 공통 기능으로 예약되며, 매장 식별자로 사용할 수 없습니다.

**예약어 목록**:
- `/app` — 테이블 오더 진입점
- `/pickup` — 픽업 주문
- `/brands` — 브랜드/매장 목록
- `/my` — 마이페이지 (주문내역, 설정)
- `/help` — 고객센터 (FAQ, 문의)
- `/auth/*` — 인증 관련 (로그인, 로그아웃)
- `/admin` — 관리자 페이지 (Filament)
- `/nova` — 마스터 관리자 (Nova)

**충돌 방지**:
- 매장 코드(`stores.code`)는 예약어와 중복 불가
- 예: `app`, `pickup`, `brands`, `my`, `help`, `auth`, `admin`, `nova` 등은 매장 코드로 사용 금지

### 3. 테넌시 해석 우선순위
1. **예약어 경로**: `/app`, `/pickup` 등 → 서브도메인으로 매장 식별
2. **동적 경로**: `/{store}/menu` → 경로 파라미터로 매장 식별
3. **서브도메인**: `store1.olulo.com.mx` → 호스트로 매장 식별

## 라우팅 구조

### Level 1: 플랫폼 공통 라우트 (예약어)

#### `/app` — 테이블 오더 진입점
**목적**: QR 코드 스캔 후 테이블 오더 시작

**URL 패턴**:
```
{store}.olulo.com.mx/app?table={table_id}&seat={seat_id}&token={qr_token}
```

**파라미터**:
- `table`: 테이블 ID (필수)
- `seat`: 좌석 ID (선택)
- `token`: QR 코드 인증 토큰 (선택)

**플로우**:
1. QR 스캔 → `/app?table=1&seat=A&token=xyz`
2. 토큰 검증 (백엔드)
3. 세션 시작 (`order_sessions` 생성)
4. 온보딩 → 메뉴 리스트

**구현 상태**: Placeholder (이슈 #4)

#### `/pickup` — 픽업 주문
**목적**: 매장 방문 전 미리 주문 후 픽업

**URL 패턴**:
```
{store}.olulo.com.mx/pickup
```

**플로우**:
1. 매장 선택 (또는 서브도메인으로 자동 식별)
2. 메뉴 선택
3. 픽업 시간 예약
4. 결제 (온라인/오프라인)
5. 픽업 알림 (WhatsApp)

**구현 상태**: 미구현 (후속 이슈)

#### `/brands` — 브랜드/매장 목록
**목적**: 전체 플랫폼 매장 탐색

**URL 패턴**:
```
brands.olulo.com.mx
brands.olulo.com.mx?category={category_id}&location={location}
```

**플로우**:
1. 지역/카테고리별 매장 목록
2. 매장 선택 → 해당 매장 서브도메인으로 이동
3. 검색/필터링

**구현 상태**: 미구현 (후속 이슈)

#### `/my` — 마이페이지
**목적**: 사용자 개인화 영역

**URL 패턴**:
```
{store}.olulo.com.mx/my
{store}.olulo.com.mx/my/orders
{store}.olulo.com.mx/my/settings
```

**하위 경로**:
- `/my/orders` — 주문 내역
- `/my/settings` — 설정 (언어, 통화, 알림)
- `/my/favorites` — 즐겨찾기 메뉴
- `/my/points` — 포인트/리워드

**구현 상태**: 미구현 (후속 이슈)

#### `/help` — 고객센터
**목적**: 고객 지원 및 문의

**URL 패턴**:
```
{store}.olulo.com.mx/help
{store}.olulo.com.mx/help/faq
{store}.olulo.com.mx/help/contact
```

**하위 경로**:
- `/help/faq` — 자주 묻는 질문
- `/help/contact` — 문의하기 (WhatsApp, 이메일)
- `/help/terms` — 이용약관
- `/help/privacy` — 개인정보처리방침

**구현 상태**: 미구현 (후속 이슈)

#### `/auth/*` — 인증 라우트
**목적**: Firebase 인증 플로우

**URL 패턴**:
```
{store}.olulo.com.mx/auth/login
{store}.olulo.com.mx/auth/logout
{store}.olulo.com.mx/auth/callback
```

**하위 경로**:
- `/auth/login` — FirebaseUI 로그인 페이지
- `/auth/logout` — 로그아웃 처리
- `/auth/callback` — OAuth 리다이렉트 (선택)

**구현 상태**: Placeholder (이슈 #4)

### Level 2: 매장별 동적 라우트 (향후 구현)

#### `/{store}/menu` — 메뉴 목록
**목적**: 특정 매장의 메뉴 탐색 (서브도메인 대신 경로로 매장 식별)

**URL 패턴**:
```
olulo.com.mx/{store}/menu
olulo.com.mx/{store}/menu?category={category_id}
```

**플로우**:
1. 경로 파라미터(`{store}`)로 매장 조회
2. 메뉴 목록 표시
3. 카테고리/검색 필터링

**구현 상태**: 미구현 (문서로만 정의)

**참고**: 서브도메인 방식(`{store}.olulo.com.mx/app`)과 병행 가능

#### `/{store}/table/{table}` — 테이블 직접 접근
**목적**: QR 없이 URL로 테이블 접근 (디버깅/테스트용)

**URL 패턴**:
```
olulo.com.mx/{store}/table/{table_id}?seat={seat_id}
```

**플로우**:
1. 경로 파라미터로 매장/테이블 조회
2. 세션 시작 (토큰 검증 생략, 개발 환경만)
3. 메뉴 리스트

**구현 상태**: 미구현 (문서로만 정의)

**보안**: 프로덕션에서는 토큰 필수 또는 경로 비활성화

### Level 3: 서브도메인 기반 라우트 (주요 방식)

#### `{store}.olulo.com.mx/` — 매장 홈
**목적**: 매장별 랜딩 페이지

**플로우**:
1. 서브도메인으로 매장 식별
2. 매장 소개/프로모션
3. `/app`, `/pickup` 등 진입 버튼

**구현 상태**: 미구현 (후속 이슈)

## 라우팅 해석 로직 (백엔드)

### 미들웨어: `TenantResolver`
**파일**: `app/Http/Middleware/TenantResolver.php` (향후 구현)

**책임**:
1. 요청 호스트 파싱 (`request()->getHost()`)
2. 서브도메인 추출 (예: `store1.olulo.com.mx` → `store1`)
3. `stores` 테이블 조회 (`where('code', $subdomain)`)
4. 테넌트 컨텍스트 바인딩 (`app()->instance('tenant', $store)`)
5. 예외: 예약어 경로는 서브도메인 필수, 동적 경로는 파라미터 우선

**예시**:
```php
// Pseudo-code
public function handle($request, $next)
{
    $host = $request->getHost();
    $subdomain = $this->extractSubdomain($host); // 'store1'

    // 예약어 경로는 서브도메인 필수
    if (in_array($request->path(), ['app', 'pickup', 'my', 'help'])) {
        $store = Store::where('code', $subdomain)->firstOrFail();
        app()->instance('tenant', $store);
    }

    // 동적 경로는 파라미터 우선
    if ($request->route('store')) {
        $store = Store::where('code', $request->route('store'))->firstOrFail();
        app()->instance('tenant', $store);
    }

    return $next($request);
}
```

### 라우트 정의 예시
**파일**: `routes/web.php` (향후)

```php
// 예약어 경로 (서브도메인 필수)
Route::middleware(['tenant'])->group(function () {
    Route::get('/app', [CustomerController::class, 'app']);
    Route::get('/pickup', [CustomerController::class, 'pickup']);
    Route::get('/my', [CustomerController::class, 'my']);
    Route::get('/help', [CustomerController::class, 'help']);
});

// 동적 경로 (경로 파라미터)
Route::get('/{store}/menu', [MenuController::class, 'index']);
Route::get('/{store}/table/{table}', [TableController::class, 'show']);
```

## 프론트엔드 라우팅 (Inertia.js)

### Inertia 페이지 구조
**디렉터리**: `resources/js/Pages/`

```
Pages/
├── App.tsx                  # /app (테이블 오더 진입)
├── Pickup.tsx               # /pickup (픽업 주문)
├── Brands.tsx               # /brands (매장 목록)
├── My/
│   ├── Index.tsx            # /my (마이페이지 홈)
│   ├── Orders.tsx           # /my/orders (주문 내역)
│   └── Settings.tsx         # /my/settings (설정)
├── Help/
│   ├── Index.tsx            # /help (고객센터 홈)
│   ├── FAQ.tsx              # /help/faq (FAQ)
│   └── Contact.tsx          # /help/contact (문의)
├── Auth/
│   ├── Login.tsx            # /auth/login (로그인)
│   └── Callback.tsx         # /auth/callback (OAuth)
├── Menu/
│   ├── List.tsx             # /{store}/menu (메뉴 목록)
│   └── Detail.tsx           # /{store}/menu/{menu_id} (메뉴 상세)
└── Dashboard.tsx            # /dashboard (로그인 성공 확인, placeholder)
```

### Inertia 링크 예시
```tsx
// 같은 매장 내 이동
<Link href="/app">테이블 오더</Link>
<Link href="/pickup">픽업 주문</Link>
<Link href="/my/orders">주문 내역</Link>

// 다른 매장으로 이동 (전체 URL)
<a href="https://store2.olulo.com.mx/app">매장2로 이동</a>

// 동적 경로
<Link href={`/${storeCode}/menu`}>메뉴 보기</Link>
```

## 충돌 방지 전략

### 1. 매장 코드 검증
**마이그레이션**: `create_stores_table.php` (향후)

```php
// Pseudo-code
Schema::create('stores', function (Blueprint $table) {
    $table->id();
    $table->string('code')->unique(); // 매장 식별자
    // ...

    // 제약: 예약어 검증 (validation rule)
});
```

**Validation Rule**:
```php
'code' => [
    'required',
    'string',
    'max:50',
    'unique:stores',
    Rule::notIn([
        'app', 'pickup', 'brands', 'my', 'help',
        'auth', 'admin', 'nova', 'api', 'sanctum'
    ]),
],
```

### 2. 라우트 우선순위 설정
Laravel 라우트 정의 시 **예약어 경로를 먼저 등록**하여 동적 경로보다 우선 매칭되도록 설정.

```php
// 예약어 우선 (위에 정의)
Route::get('/app', [CustomerController::class, 'app']);
Route::get('/pickup', [CustomerController::class, 'pickup']);

// 동적 경로 (아래 정의)
Route::get('/{store}/menu', [MenuController::class, 'index']);
```

### 3. 서브도메인 검증
**미들웨어**: `TenantResolver` (향후)

- 서브도메인이 `stores.code`에 존재하지 않으면 404 반환
- `www`, `api`, `admin` 등 시스템 예약 서브도메인은 테넌트로 해석하지 않음

## SEO 및 메타데이터 (향후)

### 동적 메타태그
- 매장별 타이틀/설명 자동 생성
- Open Graph/Twitter Card 지원
- 다국어 hreflang 태그

### Sitemap
- 매장별 sitemap 생성 (`/{store}/sitemap.xml`)
- 전체 플랫폼 sitemap index

## 모바일 딥링크 (향후)

### Universal Links (iOS)
- `https://{store}.olulo.com.mx/app?table=1` → 앱으로 열기

### App Links (Android)
- `https://{store}.olulo.com.mx/app?table=1` → 앱으로 열기

## 구현 우선순위

### 프로젝트 1 (이슈 #4)
- [x] `/app` (Placeholder)
- [x] `/auth/login` (Placeholder)
- [x] `/dashboard` (Placeholder)
- [ ] 라우팅 아키텍처 문서 (본 문서)

### 프로젝트 2 (후속 이슈)
- [ ] `TenantResolver` 미들웨어 구현
- [ ] `/pickup` 페이지
- [ ] `/my` 페이지 (마이페이지)
- [ ] `/{store}/menu` 동적 라우트

### 프로젝트 3 (후속 이슈)
- [ ] `/brands` 페이지 (매장 목록)
- [ ] `/help` 페이지 (고객센터)
- [ ] SEO 메타태그 자동 생성
- [ ] 모바일 딥링크 설정

## 테스트 시나리오

### 1. 서브도메인 기반 테넌시
- [ ] `store1.olulo.com.mx/app` → 매장1로 식별
- [ ] `store2.olulo.com.mx/app` → 매장2로 식별
- [ ] `invalid.olulo.com.mx/app` → 404 에러

### 2. 예약어 우선순위
- [ ] `/app` → 테이블 오더 진입 (서브도메인으로 매장 식별)
- [ ] `/pickup` → 픽업 주문 (서브도메인으로 매장 식별)
- [ ] `/app-store/menu` → 동적 경로 (매장 코드 `app-store`)

### 3. 동적 경로 파라미터
- [ ] `/store1/menu` → 매장1 메뉴 목록
- [ ] `/store1/table/5` → 매장1 테이블5 접근
- [ ] `/invalid-store/menu` → 404 에러

### 4. 교차 도메인 이동
- [ ] `store1.olulo.com.mx` → `store2.olulo.com.mx` 이동 시 세션 유지 불가 확인
- [ ] 세션 쿠키 도메인: `.olulo.com.mx` (서브도메인 간 공유)

## 관련 기술 스택
- **백엔드**: Laravel 12 (라우팅, 미들웨어)
- **프론트엔드**: Inertia.js + React (SPA-like 경험)
- **세션**: Sanctum SPA (동일 루트 도메인)
- **도메인**: 서브도메인 기반 멀티테넌시

## 참고 문서
- Laravel 라우팅: https://laravel.com/docs/12.x/routing
- Inertia.js 라우팅: https://inertiajs.com/routing
- 테넌시 설계: [docs/tenancy/host-middleware.md](../tenancy/host-middleware.md)
- 환경/도메인: [docs/devops/environments.md](../devops/environments.md)

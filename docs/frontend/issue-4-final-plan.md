# 이슈 #4 최종 구현 계획 — 고객앱 부트스트랩

## 문서 목적
이 문서는 사용자 피드백을 반영하여 수정된 이슈 #4의 최종 구현 계획입니다.

## 관련 문서
- 범위 명세: [issue-4-scope.md](issue-4-scope.md)
- 구현 체크리스트: [implementation-checklist.md](implementation-checklist.md)
- 라우팅 아키텍처: [routing-architecture.md](routing-architecture.md)
- 언어 처리: [language-strategy.md](language-strategy.md)

## 주요 수정사항

### 1. 라우트 충돌 해결

#### 문제점
- `/app`: 목적이 불명확한 임시 경로
- `/auth/login`: 관리자 로그인과 충돌
- `/dashboard`: 고객 UX에 부적합 (B2C 서비스)

#### 해결방안
- `/` → QR 파라미터 처리 진입점
- `/customer/auth/login` → 고객 전용 로그인
- `/my/orders` → 마이페이지 (주문 내역)

### 2. 사용자 시나리오 반영

#### 테이블 오더 플로우
```
QR 스캔 → /?store=x&table=y&seat=z
         ↓
   세션에 컨텍스트 저장
         ↓
   메뉴 표시 (로그인 선택적)
         ↓
   주문 생성 (로그인 필수)
         ↓
   /my/orders (주문 내역 확인)
```

#### 픽업 주문 플로우
```
/pickup → 매장 선택
        ↓
   메뉴 선택
        ↓
   로그인 (필요 시)
        ↓
   주문 생성
        ↓
   /my/orders (주문 상태 추적)
```

### 3. 관리자/고객 경로 분리

#### 관리자 라우트 (기존 유지)
```
/                    # welcome 페이지
/login              # → /auth/login 리다이렉트
/auth/login         # 관리자 로그인 (FirebaseUI)
/auth/logout        # 로그아웃
/admin              # Filament 패널
/nova               # Nova 패널
/__/auth/{path}     # Firebase 프록시
```

#### 고객 라우트 (신규)
```
/                           # QR 진입/매장 선택
/customer/auth/login        # 고객 로그인 (FirebaseUI)
/customer/auth/callback     # Firebase 콜백
/my/orders                  # 주문 내역
/my/profile                 # 프로필 (placeholder)
/menu                       # 메뉴 조회 (placeholder)
/cart                       # 장바구니 (placeholder)
/pickup                     # 픽업 매장 선택 (placeholder)
```

## 구현 범위 (최종)

### ✅ 실제 구현
1. **백엔드**
   - `CustomerContextMiddleware`: QR 파라미터 세션 저장
   - `Customer\AuthController`: 고객 로그인/콜백
   - `Customer\HomeController`: `/` 페이지 렌더링
   - `Customer\ProfileController`: `/my/orders` 렌더링
   - 라우트 정의 (`routes/web.php`)

2. **프론트엔드**
   - Inertia.js + React 19 + TypeScript 설정
   - Placeholder 페이지 3개:
     - `Pages/Home.tsx` (QR 진입)
     - `Pages/Customer/Auth/Login.tsx` (로그인)
     - `Pages/My/Orders.tsx` (마이페이지)
   - Firebase 초기화 및 FirebaseUI 통합
   - API 클라이언트 (axios + CSRF)

3. **문서**
   - 라우팅 아키텍처 (기능 중심)
   - QR 플로우 상세
   - 예약어 목록

### ❌ 제외 (문서로만)
- 매장/테이블/메뉴 모델 구현
- 실제 데이터 CRUD
- 복잡한 UI 컴포넌트
- 비즈니스 로직 (장바구니, 결제)

## Placeholder 페이지 상세

### 1. `/` (Home.tsx)
**목적**: QR 스캔 진입 및 파라미터 처리

**URL 예시**:
```
/?store=tacos-maya&table=5&seat=2
```

**UI**:
```
┌────────────────────────────────┐
│  Olulo MX - 고객앱             │
├────────────────────────────────┤
│                                │
│  QR 스캔 정보:                 │
│  매장: tacos-maya              │
│  테이블: 5                     │
│  좌석: 2                       │
│                                │
│  [ 로그인하기 ]                │
│  [ 비회원으로 계속 ]           │
│                                │
└────────────────────────────────┘
```

**기능**:
- 쿼리 파라미터 파싱 (`useSearchParams`)
- 세션 저장 (백엔드 미들웨어)
- CSRF 쿠키 획득 (`/sanctum/csrf-cookie`)
- 파라미터 없으면 매장 선택 안내

### 2. `/customer/auth/login` (Login.tsx)
**목적**: 고객 전용 Firebase 로그인

**UI**:
```
┌────────────────────────────────┐
│  고객 로그인                   │
├────────────────────────────────┤
│                                │
│  [FirebaseUI 컨테이너]         │
│  - 이메일/비밀번호             │
│  - Google 로그인               │
│                                │
│  < 뒤로 가기                   │
│                                │
└────────────────────────────────┘
```

**플로우**:
1. FirebaseUI 로그인 성공 → ID Token
2. `POST /customer/auth/firebase/callback`
3. 응답 204 → `/my/orders`
4. 실패 → 에러 메시지

### 3. `/my/orders` (Orders.tsx)
**목적**: 로그인 성공 확인 및 보호 API 테스트

**UI**:
```
┌────────────────────────────────┐
│  내 주문 내역                  │
├────────────────────────────────┤
│  사용자: user@example.com      │
│  포인트: 0                     │
│                                │
│  최근 방문:                    │
│  tacos-maya, 테이블 5          │
│                                │
│  주문 내역:                    │
│  (주문 없음)                   │
│                                │
│  [ 로그아웃 ]                  │
└────────────────────────────────┘
```

**기능**:
- `GET /api/user` 호출 (보호 API)
- `GET /api/my/orders` 호출 (빈 배열)
- 세션 컨텍스트 표시
- 로그아웃 버튼

## Laravel 라우트 구조 (최종)

```php
// routes/web.php

// 관리자 라우트 (기존 유지)
Route::get('/', function () {
    return view('welcome');
});

Route::prefix('auth')->name('auth.')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

// Firebase 프록시 (기존 유지)
Route::any('__/auth/{path}', ...);

// 고객 라우트 (신규)
Route::middleware(['web', 'locale', 'customer.context'])->group(function () {
    // QR 진입점
    Route::get('/', [Customer\HomeController::class, 'index'])
        ->name('customer.home');

    // 고객 인증
    Route::prefix('customer/auth')->name('customer.auth.')->group(function () {
        Route::get('/login', [Customer\AuthController::class, 'showLogin'])
            ->name('login');
        Route::post('/firebase/callback', [Customer\AuthController::class, 'firebaseCallback'])
            ->name('firebase.callback');
    });

    // 개인 영역 (인증 필요)
    Route::middleware('auth:web')->prefix('my')->name('my.')->group(function () {
        Route::get('/orders', [Customer\ProfileController::class, 'orders'])
            ->name('orders');
        Route::get('/profile', [Customer\ProfileController::class, 'profile'])
            ->name('profile');
    });
});
```

## API 라우트 (최종)

```php
// routes/api.php

// 고객 인증 API
Route::prefix('customer/auth')->name('api.customer.auth.')->group(function () {
    Route::post('/firebase-login', [Customer\AuthController::class, 'apiFirebaseLogin'])
        ->name('firebase.login');
});

// 보호 API (인증 필요)
Route::middleware('auth:web')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::prefix('my')->group(function () {
        Route::get('/orders', [Customer\OrderController::class, 'index']);
        Route::get('/profile', [Customer\ProfileController::class, 'show']);
    });
});
```

## 미들웨어 구조 (제외)

### ~~CustomerContextMiddleware~~ - **리소스 구현 후 작성**
QR 파라미터(`store`, `table`, `seat`)를 세션에 저장하는 미들웨어는
Store/Table 모델이 생성된 이후에 유효성 검증과 함께 구현합니다.

**제외 이유**:
- Store/Table 모델이 아직 없어 유효성 검증 불가
- 실제 리소스 구현 시 함께 작성하는 것이 효율적

### ~~LocaleMiddleware~~ - **Laravel 기본 기능 사용**
Laravel 기본 `App::setLocale()` 메서드를 컨트롤러/미들웨어에서 직접 호출합니다.

**제외 이유**:
- Laravel 기본 기능으로 충분
- 복잡한 언어 감지 로직은 필요 시 추가 가능

## 작업 순서 및 예상 시간

### Phase 1: 백엔드 기초 (1-2시간)
- [x] Inertia.js Laravel 패키지 설치
- [ ] ~~미들웨어 구현~~: **제외** - 리소스 구현 후 작성
- [x] 컨트롤러 생성 (`Customer\*`)
- [ ] 라우트 정의 (web.php, api.php)

### Phase 2: 프론트엔드 설정 (2-3시간)
- [ ] Inertia React 클라이언트 설치
- [ ] TypeScript 설정
- [ ] Firebase 초기화
- [ ] API 클라이언트 (axios) 설정

### Phase 3: Placeholder 구현 (3-4시간)
- [ ] Home.tsx (QR 진입)
- [ ] Login.tsx (FirebaseUI)
- [ ] Orders.tsx (마이페이지)
- [ ] 레이아웃 컴포넌트

### Phase 4: 테스트 및 문서 (1-2시간)
- [ ] 플로우 테스트 (CSRF → 로그인 → 세션)
- [ ] 라우트 충돌 확인
- [ ] 문서 최종 업데이트

**총 예상 시간**: 8-12시간

## 완료 기준 (DoD)

### 기능 요구사항
- [ ] `/?store=x&table=y&seat=z` 접근 시 세션에 컨텍스트 저장
- [ ] `/customer/auth/login` 로그인 성공 → `/my/orders` 리다이렉트
- [ ] `/my/orders`에서 `GET /api/user` 호출 성공
- [ ] 로그아웃 → 세션 종료 → `/` 리다이렉트
- [ ] 관리자 로그인 `/auth/login` 정상 동작 (영향 없음)

### 비기능 요구사항
- [ ] `vendor/bin/pint` 통과
- [ ] `vendor/bin/phpstan analyse` 통과
- [ ] TypeScript 컴파일 에러 없음
- [ ] Vite 빌드 성공

### 문서 요구사항
- [ ] issue-4-scope.md 업데이트 완료
- [ ] routing-architecture.md 작성 완료
- [ ] implementation-checklist.md 작성 완료
- [ ] README에 실행 방법 추가

## 후속 작업 (별도 이슈)

### 이슈 #5 (예상): 코어 모델 생성
- Store, Table, Menu, MenuItem 모델/마이그레이션
- 기본 CRUD API
- 시더 데이터

### 이슈 #6 (예상): 메뉴 조회 구현
- 메뉴 리스트 페이지
- 메뉴 상세 페이지
- 카테고리 필터링

### 이슈 #7 (예상): 주문 생성 플로우
- 장바구니 기능
- 주문 생성 API
- 주문 확인 페이지

## 참고사항

### 충돌 방지 규칙
1. **예약어 사용 금지**: 매장 slug로 `customer`, `my`, `pickup`, `auth` 등 사용 불가
2. **경로 우선순위**: 예약어 라우트를 매장 라우트보다 먼저 정의
3. **미들웨어 순서**: `locale` → `customer.context` → `auth`

### 보안 고려사항
1. **QR 파라미터 검증**: store/table 값 유효성 확인 (향후)
2. **세션 격리**: 고객/관리자 세션 구분
3. **CSRF 보호**: 모든 POST 요청에 CSRF 토큰 필수
4. **역할 분리**: 고객은 관리자 경로 접근 불가

## 질의응답 (반영사항)

### Q1: `/app` 사용 이유는?
**A**: 실제 매장 리소스가 없어서 임시 사용. → **해결**: `/`로 변경, QR 파라미터 처리

### Q2: `/auth/login` 충돌?
**A**: 관리자 로그인과 충돌. → **해결**: `/customer/auth/login`으로 분리

### Q3: 로그인 후 대시보드?
**A**: 고객에게 부적합. → **해결**: `/my/orders` (마이페이지)로 변경

## 결론

이슈 #4는 **고객앱 부트스트랩과 인증 플로우 구현**에만 집중합니다.
- 관리자/고객 경로 완전 분리
- QR 스캔 시나리오 반영
- 사용자 UX 중심 설계 (마이페이지 랜딩)
- 매장/테이블/메뉴 구현은 후속 이슈로 분리

**다음 단계**: `implementation-checklist.md`를 기준으로 구현 시작

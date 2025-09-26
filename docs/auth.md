# 인증 설계 — Firebase + Sanctum(SPA, 동일 루트 도메인)

본 문서는 동일 루트 도메인(예: `api.example.com`, `store1.example.com`, `admin.example.com`)을 공유하는 서브도메인 구조에서 Firebase 인증과 Laravel Sanctum을 병행하여 세션을 관리하는 방법을 정의합니다.

## 목표
- FirebaseUI로 로그인(고객/관리자 공통) → Firebase ID Token 획득
- 백엔드에서 Firebase ID Token 검증 후 Laravel 세션(Sanctum SPA 쿠키) 확립
- 이후 고객/관리자 화면에서 세션 쿠키 기반으로 보호 라우트 접근

## 환경 변수 예시(.env)
### 개발(dev)
```
APP_URL=https://admin.dev.olulo.com.mx
SESSION_DRIVER=redis
SESSION_DOMAIN=.dev.olulo.com.mx
SANCTUM_STATEFUL_DOMAINS=admin.dev.olulo.com.mx,menu.dev.olulo.com.mx

# Firebase (예시 값)
FIREBASE_PROJECT_ID=...
FIREBASE_CLIENT_EMAIL=...
FIREBASE_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----\n...\n-----END PRIVATE KEY-----\n"
FIREBASE_WEB_API_KEY=...
```

### 스테이징(staging)
```
APP_URL=https://admin.demo.olulo.com.mx
SESSION_DRIVER=redis
SESSION_DOMAIN=.demo.olulo.com.mx
SANCTUM_STATEFUL_DOMAINS=admin.demo.olulo.com.mx,menu.demo.olulo.com.mx
```

### 프로덕션(prod)
```
APP_URL=https://admin.olulo.com.mx
SESSION_DRIVER=redis
SESSION_DOMAIN=.olulo.com.mx
SANCTUM_STATEFUL_DOMAINS=admin.olulo.com.mx,menu.olulo.com.mx
```

## CORS/CSRF 설정 요지
- CORS 허용 오리진: 고객/관리/관리자 서브도메인 전부 명시(https 필수)
- 인증 방식: 쿠키+자격증명 허용(`Access-Control-Allow-Credentials: true`)
- CSRF: React 측에서 `/sanctum/csrf-cookie` 호출 후 `X-XSRF-TOKEN` 헤더 포함

## 인증 플로우(고객/관리자 공통)
```mermaid
sequenceDiagram
  participant R as React(App)
  participant API as Laravel(API)
  participant F as Firebase Auth

  R->>API: GET /sanctum/csrf-cookie
  API-->>R: Set-Cookie: XSRF-TOKEN, laravel_session (도메인=.example.com)
  R->>F: FirebaseUI Login (Google/Email 등)
  F-->>R: ID Token 반환
  R->>API: POST /api/auth/firebase-login { idToken }
  API->>API: Firebase ID 토큰 검증 + 사용자 매핑/생성
  API-->>R: 204 No Content (세션 확립 완료)
  R->>API: 이후 보호 API 호출(쿠키 + X-XSRF-TOKEN)
```

## 엔드포인트 스펙
- POST `/api/auth/firebase-login`
  - Request(JSON): `{ "idToken": "<Firebase ID Token>" }`
  - Response: 204 No Content
  - 동작: Firebase ID 토큰 검증 → 로컬 사용자 조회/생성(`users.firebase_uid`) → `Auth::login($user)` → Sanctum SPA 세션 확립
- POST `/api/auth/logout`
  - Request: 빈 바디
  - Response: 204 No Content
  - 동작: 세션 로그아웃 + 쿠키 무효화

## 미들웨어/가드 구성
- `EnsureFrontendRequestsAreStateful`를 고객/관리자 SPA API 그룹에 적용
- 관리자 라우트(Filament/Nova): `web` 가드 + 정책/권한(스페티 퍼미션) 적용
- 고객 SPA API: 세션 보호 또는 비회원 허용 엔드포인트 혼합(결제/민감 정보는 보호)

## 라우트 그룹 예시(개념)
```
# routes/api.php
Route::prefix('auth')->group(function(){
    Route::post('firebase-login', [AuthController::class, 'firebaseLogin']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:web');
});

# routes/web.php (관리자 UI)
Route::middleware(['auth:web'])->group(function(){
    // /admin (Filament)
    // /nova  (Master)
});

# routes/api.php (고객 API)
Route::middleware(['auth:web'])->group(function(){
    // 보호 API (주문 조회 등)
});

# 게스트 허용 API (주문 생성 일부, 메뉴 조회 등)
Route::get('/customer/menus', ...);
```

## 테넌시(서브도메인) 해석
- 미들웨어에서 `request()->getHost()`로 서브도메인을 파싱하여 `stores.code`를 조회
- 요청 컨텍스트에 `tenant(store_id)`를 바인딩하고, 쿼리를 `store_id`로 스코핑

## 보안 체크리스트
- HTTPS 필수, Secure 쿠키, SameSite=Lax
- Firebase ID 토큰 만료 처리(재인증 루틴), 서버 시간 동기화
- 관리자/고객 권한 분리: `spatie/laravel-permission`으로 역할/권한 모델링

## Trouble-shooting
- 세션이 유지되지 않으면 `SESSION_DOMAIN`과 `SANCTUM_STATEFUL_DOMAINS` 구성 확인
- CORS에서 `credentials: true` 누락, 또는 XSRF 토큰 헤더 누락 여부 확인
- 서로 다른 루트 도메인 간에는 이 방식이 제한되므로 Bearer 토큰 방식을 고려

## 관련 문서
- 화이트페이퍼: `docs/whitepaper.md`
- 환경별 도메인/CORS: `docs/devops/environments.md`
- 프로젝트 1: `docs/milestones/project-1.md`
- QA 체크리스트: `docs/qa/checklist.md`

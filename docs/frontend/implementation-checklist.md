# 이슈 #4 구현 체크리스트

## 문서 목적
이슈 #4 "[Frontend] 고객앱 부트스트랩(CSRF, FirebaseUI, 교환 호출)" 구현을 위한 단계별 체크리스트입니다.

## 관련 문서
- 범위 명세: [docs/frontend/issue-4-scope.md](issue-4-scope.md)
- React 부트스트랩: [docs/frontend/react-bootstrap.md](react-bootstrap.md)
- 인증 설계: [docs/auth.md](../auth.md)

## Phase 1: 백엔드 기초 설정

### 1.1 Inertia.js 설치 및 통합
- [ ] `composer require inertiajs/inertia-laravel` 설치
- [ ] `npm install @inertiajs/react react react-dom` 설치
- [ ] Inertia 미들웨어 등록 (`bootstrap/app.php`)
- [ ] Inertia 루트 뷰 생성 (`resources/views/app.blade.php`)
- [ ] Vite 설정 업데이트 (`vite.config.js` - React 플러그인 추가)

### 1.2 라우트 정의
**파일**: `routes/web.php`, `routes/api.php`

**Web Routes** (`routes/web.php`):
- [ ] `GET /app` → `CustomerController@app` (Inertia 진입점)
- [ ] `GET /auth/login` → `CustomerController@login` (FirebaseUI 페이지)
- [ ] `GET /dashboard` → `CustomerController@dashboard` (보호 라우트, 로그인 성공 확인)

**API Routes** (`routes/api.php`):
- [ ] `POST /api/auth/firebase-login` → `FirebaseAuthController@login` (기존 확인/보강)
- [ ] `POST /api/auth/logout` → `FirebaseAuthController@logout`
- [ ] `GET /api/user` → 현재 사용자 정보 반환 (보호 API)

### 1.3 미들웨어 구현
**LocaleMiddleware**:
- [ ] `php artisan make:middleware LocaleMiddleware` 실행
- [ ] `Accept-Language` 헤더 파싱 로직 구현
- [ ] 지원 언어: `ko`, `es-MX`, `en` (우선순위 순)
- [ ] `app()->setLocale($locale)` 설정
- [ ] `bootstrap/app.php`에 미들웨어 등록 (`web` 그룹)

**세션 미들웨어**:
- [ ] `EnsureFrontendRequestsAreStateful` 확인 (Sanctum, 기존)
- [ ] API 라우트 그룹에 적용 확인

### 1.4 컨트롤러 생성
**CustomerController**:
- [ ] `php artisan make:controller CustomerController` 실행
- [ ] `app()` 메서드: 쿼리 파라미터(`store`, `table`, `seat`) 수집, Inertia 렌더링
- [ ] `login()` 메서드: FirebaseUI 페이지 렌더링
- [ ] `dashboard()` 메서드: 인증 확인, 사용자 정보 전달

**FirebaseAuthController**:
- [ ] 기존 `login()` 메서드 확인/보강 (Firebase ID Token 검증)
- [ ] `logout()` 메서드 구현 (`Auth::logout()`, 세션 무효화)

## Phase 2: 프론트엔드 설정

### 2.1 TypeScript 설정
- [ ] `npm install -D typescript @types/react @types/react-dom` 설치
- [ ] `tsconfig.json` 생성 (React, DOM, ES2022 타겟)
- [ ] Vite React 플러그인 설정 (`vite.config.ts`)

### 2.2 Firebase 초기화
**파일**: `resources/js/lib/firebase.ts`
- [ ] Firebase 설정 환경변수 정의 (`.env` 또는 import.meta.env)
- [ ] `initializeApp()` 호출
- [ ] `getAuth()` export
- [ ] FirebaseUI 설정 객체 정의 (이메일/패스워드, Google)

### 2.3 API 클라이언트 설정
**파일**: `resources/js/lib/api.ts`
- [ ] `axios` 인스턴스 생성
- [ ] `withCredentials: true` 설정
- [ ] CSRF 토큰 자동 첨부 (`X-XSRF-TOKEN` 헤더)
- [ ] `/sanctum/csrf-cookie` 자동 호출 로직 (초기화)
- [ ] 401/419 에러 핸들러 (재인증 리다이렉트)

### 2.4 Inertia 초기화
**파일**: `resources/js/app.tsx`
- [ ] Inertia React 어댑터 import
- [ ] `createInertiaApp()` 설정
- [ ] 페이지 컴포넌트 동적 import (`./Pages/${name}.tsx`)
- [ ] DOM 렌더링 (`createRoot`)

## Phase 3: Placeholder 페이지 구현

### 3.1 공통 레이아웃
**파일**: `resources/js/Layouts/AppLayout.tsx`
- [ ] 헤더 컴포넌트 (타이틀, 로고 placeholder)
- [ ] 푸터 컴포넌트 (저작권 정보)
- [ ] 기본 스타일링 (TailwindCSS + daisyUI)
- [ ] children prop으로 페이지 콘텐츠 렌더링

### 3.2 `/app` — 진입점 페이지
**파일**: `resources/js/Pages/App.tsx`

**UI 요소**:
- [ ] 헤더: "Olulo MX - 고객앱"
- [ ] 쿼리 파라미터 표시 영역 (`store`, `table`, `seat`)
- [ ] "로그인하기" 버튼 (→ `/auth/login`)
- [ ] "계속하기 (비회원)" 버튼 (→ `/dashboard`, placeholder)

**기능**:
- [ ] URL 쿼리 파라미터 추출 (`new URLSearchParams()`)
- [ ] 로컬 스토리지에 저장 (`localStorage.setItem()`)
- [ ] CSRF 쿠키 자동 획득 (`/sanctum/csrf-cookie` 호출)

### 3.3 `/auth/login` — Firebase 로그인 페이지
**파일**: `resources/js/Pages/Auth/Login.tsx`

**UI 요소**:
- [ ] FirebaseUI 컨테이너 (`<div id="firebaseui-auth-container">`)
- [ ] 로딩 스피너 (FirebaseUI 초기화 중)

**기능**:
- [ ] FirebaseUI `start()` 호출 (`useEffect`)
- [ ] 로그인 성공 콜백: `signInSuccessWithAuthResult`
- [ ] ID Token 획득: `user.getIdToken()`
- [ ] `POST /api/auth/firebase-login` 호출 (ID Token 전송)
- [ ] 응답 성공(204) → `/dashboard` 리다이렉트
- [ ] 실패 → 에러 메시지 표시 (toast/alert)

### 3.4 `/dashboard` — 로그인 성공 확인 페이지
**파일**: `resources/js/Pages/Dashboard.tsx`

**UI 요소**:
- [ ] 헤더: "로그인 성공!"
- [ ] 사용자 정보 표시 영역 (이름, 이메일)
- [ ] 세션 파라미터 표시 (`store`, `table`, `seat`)
- [ ] "로그아웃" 버튼 (→ 세션 종료 후 `/app`)

**기능**:
- [ ] `GET /api/user` 호출 (보호 API)
- [ ] 성공 시: 사용자 정보 표시
- [ ] 실패(401) 시: `/auth/login`으로 리다이렉트
- [ ] "로그아웃" 버튼: `POST /api/auth/logout` 호출 후 `/app` 이동

### 3.5 FirebaseAuth 공통 컴포넌트
**파일**: `resources/js/Components/FirebaseAuth.tsx`
- [ ] FirebaseUI 래퍼 컴포넌트
- [ ] Props: `onSuccess`, `onError`
- [ ] FirebaseUI 설정 주입
- [ ] Cleanup 로직 (`componentWillUnmount` 또는 `useEffect` cleanup)

## Phase 4: 라우팅 및 플로우 테스트

### 4.1 라우팅 연결
- [ ] `/app` → `/auth/login` 이동 확인
- [ ] `/auth/login` → 로그인 성공 → `/dashboard` 리다이렉트 확인
- [ ] `/dashboard` → 비인증 상태 → `/auth/login` 리다이렉트 확인
- [ ] "로그아웃" → `/app` 이동 확인

### 4.2 파라미터 보존 테스트
- [ ] `/app?store=test&table=1&seat=A` 진입
- [ ] 로컬 스토리지에 파라미터 저장 확인
- [ ] `/dashboard`에서 파라미터 표시 확인
- [ ] 새로고침 후에도 파라미터 유지 확인

### 4.3 API 호출 테스트
- [ ] `/sanctum/csrf-cookie` 호출 → 쿠키 설정 확인 (브라우저 DevTools)
- [ ] `POST /api/auth/firebase-login` → 204 응답 확인
- [ ] `GET /api/user` → 사용자 정보 반환 확인
- [ ] `POST /api/auth/logout` → 세션 종료 확인

### 4.4 에러 핸들링 테스트
- [ ] 잘못된 ID Token → 401 에러 처리 확인
- [ ] 세션 만료(419) → 재인증 리다이렉트 확인
- [ ] 네트워크 에러 → 에러 메시지 표시 확인

## Phase 5: 문서 작성

### 5.1 라우팅 아키텍처 문서
**파일**: `docs/frontend/routing-architecture.md`
- [ ] Hybrid Pattern 정의 (서브도메인 + 경로)
- [ ] 예약어 목록 정의 (`/app`, `/pickup`, `/brands`, `/my`, `/help`, `/auth/*`)
- [ ] 향후 매장 라우트 구조 설계 (`/{store}/menu`, `/{store}/table/{table}`)
- [ ] 충돌 방지 전략 (예약어 우선순위)

### 5.2 언어 처리 전략 문서
**파일**: `docs/frontend/language-strategy.md`
- [ ] 브라우저 언어 감지 로직 설명
- [ ] LocaleMiddleware 동작 방식
- [ ] i18n 통합 계획 (`react-i18next`)
- [ ] 번역 파일 구조 (`lang/{locale}/messages.json`)
- [ ] 언어 전환 UI 설계 (향후 구현)

### 5.3 실행 방법 문서
**파일**: `README.md` (기존 파일 업데이트)
- [ ] 로컬 개발 환경 설정 방법
- [ ] `.env` 설정 가이드 (Firebase 설정 포함)
- [ ] 개발 서버 실행: `composer run dev`
- [ ] 프론트엔드 빌드: `npm run build`
- [ ] 테스트 방법: 수동 테스트 시나리오

## Phase 6: 품질 검증

### 6.1 코드 품질
- [ ] `vendor/bin/pint` 실행 (코드 스타일 수정)
- [ ] `vendor/bin/phpstan analyse` 실행 (정적 분석 통과)
- [ ] TypeScript 컴파일 에러 없음 (`npm run build`)
- [ ] Vite 빌드 성공 확인

### 6.2 기능 검증
- [ ] 완료 기준 모두 충족 (issue-4-scope.md 참조)
- [ ] 브라우저 호환성 테스트 (Chrome, Safari, Firefox)
- [ ] 모바일 반응형 확인 (개발자 도구)

### 6.3 문서 검증
- [ ] 모든 문서 교차참조 링크 확인
- [ ] 코드 예시 정확성 확인
- [ ] 문서 일관성 점검 (용어, 형식)

## Phase 7: PR 준비

### 7.1 커밋 정리
- [ ] 작은 단위 커밋 (atomic commits)
- [ ] 커밋 메시지 한국어 작성 (접두사: `feat:`, `chore:`, `docs:`)
- [ ] 각 커밋이 독립적으로 빌드 가능한지 확인

### 7.2 브랜치 전략
- [ ] 브랜치 생성: `feature/issue-4-customer-app-bootstrap`
- [ ] `develop` 브랜치에서 분기
- [ ] 정기적 rebase로 최신 상태 유지

### 7.3 PR 생성
**PR 제목**: `feat(frontend): 고객앱 부트스트랩 구현 (Inertia + Firebase 인증)`

**PR 본문 템플릿**:
```markdown
## 목적
이슈 #4 "[Frontend] 고객앱 부트스트랩(CSRF, FirebaseUI, 교환 호출)" 구현

## 변경 사항
- Inertia.js + React + TypeScript 설정
- Firebase 인증 통합 (FirebaseUI)
- CSRF 쿠키 획득 및 세션 확립 플로우
- Placeholder 페이지 3개 (`/app`, `/auth/login`, `/dashboard`)
- LocaleMiddleware 구현 (언어 자동 감지)
- 라우팅 아키텍처 및 언어 처리 문서 작성

## 테스트 방법
1. `composer run dev` 실행
2. 브라우저에서 `http://localhost:8000/app?store=test&table=1&seat=A` 접속
3. "로그인하기" 클릭 → FirebaseUI 로그인
4. 로그인 성공 → `/dashboard`에서 사용자 정보 확인
5. "로그아웃" → `/app`로 복귀 확인

## 체크리스트
- [ ] pint 통과
- [ ] larastan 통과
- [ ] TypeScript 컴파일 성공
- [ ] Vite 빌드 성공
- [ ] 완료 기준 모두 충족 (issue-4-scope.md)
- [ ] 문서 작성 완료

## 관련 이슈
Closes #4

## 참고 문서
- [이슈 #4 범위 명세](../docs/frontend/issue-4-scope.md)
- [구현 체크리스트](../docs/frontend/implementation-checklist.md)
- [라우팅 아키텍처](../docs/frontend/routing-architecture.md)
- [언어 처리 전략](../docs/frontend/language-strategy.md)
```

- [ ] PR 생성 후 CODEOWNERS에 리뷰 요청
- [ ] CI/CD 워크플로우 통과 확인

## 후속 작업 (별도 이슈)
- [ ] 매장/테이블/메뉴 모델 생성 및 마이그레이션 (이슈 분리)
- [ ] 메뉴 조회 API 구현 (이슈 분리)
- [ ] 주문 생성 플로우 구현 (이슈 분리)
- [ ] 실제 UI 컴포넌트 이식 (`ref/` 기반, 이슈 분리)
- [ ] 다국어 번역 파일 작성 (이슈 분리)
- [ ] 통화 선택 및 환율 표시 (이슈 분리)

## 참고 사항
- 매장/테이블/메뉴 관련 코드는 절대 생성하지 않음 (문서로만 유지)
- Placeholder 페이지는 최소한의 UI로 구성
- 인증 플로우에만 집중, 비즈니스 로직은 후속 이슈에서 처리
- 모든 커밋은 한국어로 작성 (접두사 제외)

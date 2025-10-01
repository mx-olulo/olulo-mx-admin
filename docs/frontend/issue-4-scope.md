# 이슈 #4 범위 명세 — 고객앱 부트스트랩

## 문서 목적
이 문서는 이슈 #4 "[Frontend] 고객앱 부트스트랩(CSRF, FirebaseUI, 교환 호출)"의 정확한 범위를 정의하고, 실제 구현 대상과 문서로만 설계할 부분을 명확히 구분합니다.

## 관련 문서
- React 부트스트랩: [docs/frontend/react-bootstrap.md](react-bootstrap.md)
- 인증 설계: [docs/auth.md](../auth.md)
- 프로젝트 1 계획: [docs/milestones/project-1.md](../milestones/project-1.md)
- 환경/도메인 설정: [docs/devops/environments.md](../devops/environments.md)

## 현재 프로젝트 상태
- Laravel 12 + Filament 4 백엔드 설정 완료
- Firebase Authentication 의존성 설치 완료 (kreait/firebase-php, firebase, firebaseui)
- Laravel Sanctum 설치 완료
- **중요**: 매장(Store), 테이블(Table), 메뉴(Menu) 모델/마이그레이션/리소스 **아직 미생성**
- 프론트엔드: Vite + TailwindCSS 4 + daisyUI 설정 완료, React 아직 미설치

## 작업 범위 원칙

### ✅ 포함 (실제 구현)
1. **Inertia.js 설치 및 기본 설정**
   - `@inertiajs/react`, `@inertiajs/laravel` 설치
   - Laravel 서버사이드 렌더링 설정
   - 기본 레이아웃 구조 생성

2. **Firebase 인증 통합**
   - CSRF 쿠키 획득 플로우 (`/sanctum/csrf-cookie`)
   - FirebaseUI 로그인 페이지 구현
   - ID Token 교환 엔드포인트 호출 (`POST /api/auth/firebase-login`)
   - 세션 확립 확인

3. **기본 라우팅 구조 (Placeholder)**
   - 진입점: `/` (QR 파라미터 처리)
   - 로그인: `/customer/auth/login` (FirebaseUI, 고객 전용)
   - 마이페이지: `/my/orders` (로그인 성공 확인)
   - 상태 확인: `/api/user` (보호 API 테스트)

4. **세션/상태 관리 기초**
   - 쿼리 파라미터 보존 (`store`, `table`, `seat`)
   - 로컬 스토리지/세션 스토리지 활용 계획
   - API 클라이언트 기초 (`axios` with `withCredentials: true`)

5. **라우팅 아키텍처 문서 작성**
   - Hybrid Pattern 정의 (문서)
   - 예약어 목록 정의 (문서)
   - 향후 매장 라우트 구조 설계 (문서)

6. **언어 처리 기초**
   - 브라우저 언어 감지 로직 (문서)
   - LocaleMiddleware 구현 (백엔드)
   - i18n 통합 계획 (문서)

### ❌ 제외 (문서로만 작성)
1. **매장/테이블/메뉴 모델 구현**
   - 실제 Eloquent 모델 생성 제외
   - 스키마 설계는 문서로만 유지 (`docs/models/core-tables.md`)

2. **실제 데이터 CRUD**
   - 메뉴 조회/주문 생성 API 제외
   - 더미 데이터/목업만 사용

3. **복잡한 UI 컴포넌트**
   - ref/ 디렉터리 UI 전체 이식 제외
   - 기본적인 레이아웃/버튼/폼만 구현

4. **고급 기능**
   - 장바구니, 결제, 리뷰, 서비스콜 등 제외
   - 인증 플로우에만 집중

## 최소 구현 범위 상세

### 백엔드 구현
1. **라우트 정의** (`routes/web.php`, `routes/api.php`)
   - `GET /` → QR 파라미터 처리 및 Inertia 진입점
   - `GET /customer/auth/login` → FirebaseUI 페이지 (고객 전용)
   - `POST /customer/auth/firebase/callback` → Firebase ID Token 검증
   - `POST /api/auth/logout` → 세션 종료
   - `GET /api/user` → 현재 사용자 정보 (보호 API)
   - `GET /my/orders` → 로그인 성공 확인 페이지 (마이페이지)

2. **미들웨어**
   - `LocaleMiddleware`: Accept-Language 헤더 감지, `app()->setLocale()` 설정
   - `CustomerContextMiddleware`: QR 파라미터 (`store`, `table`, `seat`) 세션 저장
   - `EnsureFrontendRequestsAreStateful`: Sanctum SPA 세션 미들웨어 (기존)

3. **컨트롤러**
   - `App\Http\Controllers\Customer\AuthController`: 고객 로그인/로그아웃 처리
   - `App\Http\Controllers\Customer\HomeController`: Inertia 페이지 렌더링
   - `App\Http\Controllers\Customer\ProfileController`: 마이페이지 렌더링

### 프론트엔드 구현
1. **Inertia 페이지**
   - `resources/js/Pages/Home.tsx`: QR 진입점 (파라미터 처리)
   - `resources/js/Pages/Customer/Auth/Login.tsx`: FirebaseUI 로그인
   - `resources/js/Pages/My/Orders.tsx`: 마이페이지 (주문 내역)

2. **공통 컴포넌트**
   - `resources/js/Layouts/AppLayout.tsx`: 기본 레이아웃
   - `resources/js/Components/FirebaseAuth.tsx`: FirebaseUI 래퍼

3. **API 클라이언트**
   - `resources/js/lib/api.ts`: axios 인스턴스 (CSRF 자동 처리)
   - `resources/js/lib/firebase.ts`: Firebase 초기화

4. **상태 관리**
   - 쿼리 파라미터 파싱 및 보존 로직
   - 로컬 스토리지 활용 (선택적)

## Placeholder 페이지 상세

### 1. `/` — QR 진입점
**목적**: QR 스캔 시뮬레이션, 파라미터 보존 확인

**UI 요소**:
- 헤더: "Olulo MX - 고객앱"
- QR 컨텍스트 표시: "매장: {store}, 테이블: {table}, 좌석: {seat}"
- "로그인하기" 버튼 → `/customer/auth/login`로 이동
- "비회원으로 계속" 버튼 → 메뉴 보기 (placeholder)

**기능**:
- URL 쿼리 파라미터 추출 (`?store=x&table=y&seat=z`)
- 세션에 컨텍스트 저장 (`CustomerContextMiddleware`)
- CSRF 쿠키 자동 획득 (`/sanctum/csrf-cookie`)
- 파라미터 없으면 매장 선택 화면 표시 (placeholder)

### 2. `/customer/auth/login` — Firebase 로그인 (고객 전용)
**목적**: FirebaseUI 통합 확인, 관리자 로그인과 분리

**UI 요소**:
- FirebaseUI 컨테이너
- 로그인 방식: 이메일/패스워드, Google (테스트)
- "뒤로 가기" 링크 → `/`로 이동

**플로우**:
1. FirebaseUI 로그인 성공 → ID Token 획득
2. `POST /customer/auth/firebase/callback` 호출 (ID Token 전송)
3. 응답 성공(204) → `/my/orders`로 리다이렉트
4. 실패 → 에러 메시지 표시

### 3. `/my/orders` — 마이페이지 (로그인 성공 확인)
**목적**: 세션 확립 및 보호 API 호출 확인

**UI 요소**:
- 헤더: "내 주문 내역"
- 사용자 정보 표시 (`GET /api/user` 응답)
- 세션 컨텍스트 표시: "최근 방문: {store}, {table}"
- "로그아웃" 버튼 → 세션 종료 후 `/`로 이동
- 주문 내역 placeholder (더미 데이터)

**기능**:
- `GET /api/user` 호출 (보호 API)
- 성공 시: 사용자 이름, 이메일, 포인트 표시
- 실패 시: 로그인 페이지로 리다이렉트
- 주문 내역 API 호출 (`GET /api/my/orders`) - 빈 배열 응답

## 라우팅 아키텍처 (문서)

### 기능 중심 라우팅 정의
- **고객 진입**: `/` (QR 파라미터 처리)
- **고객 인증**: `/customer/auth/*` (관리자와 분리)
- **개인 영역**: `/my/*` (주문, 프로필, 포인트)
- **픽업**: `/pickup/*` (매장 선택, 주문 상태)
- **예약어 목록** (향후 확장):
  - `/`: 고객 홈
  - `/customer/*`: 고객 전용 기능
  - `/my/*`: 개인 영역
  - `/pickup/*`: 픽업 주문
  - `/menu`: 메뉴 조회
  - `/cart`: 장바구니
  - `/checkout`: 결제

### 향후 매장 라우트 (문서로만)
- `/{store}/menu`: 메뉴 목록 (실제 구현 제외)
- `/{store}/table/{table}`: 테이블 주문 (실제 구현 제외)
- 구조만 정의, 구현은 후속 이슈에서 진행

## 언어 처리 (문서)

### 브라우저 언어 감지
- `Accept-Language` 헤더 파싱
- 우선순위: `ko` > `es-MX` > `en`
- LocaleMiddleware에서 자동 설정

### i18n 통합 계획 (실제 구현 제외)
- 라이브러리: `react-i18next`
- 번역 파일 구조: `lang/{locale}/messages.json`
- 언어 전환 UI는 placeholder로만

## 작업 우선순위 및 예상 시간

### Phase 1: 백엔드 기초 (2-3시간)
1. Inertia.js 설치 및 Laravel 통합 설정
2. 라우트 정의 (web, api)
3. LocaleMiddleware 구현
4. FirebaseAuthController 확인/보강

### Phase 2: 프론트엔드 설정 (2-3시간)
5. React + TypeScript + Inertia 설정
6. Firebase 초기화 및 FirebaseUI 통합
7. API 클라이언트 (`axios`) 설정
8. 기본 레이아웃 구조 생성

### Phase 3: Placeholder 페이지 (3-4시간)
9. `/app` 페이지 구현 (파라미터 보존)
10. `/auth/login` 페이지 구현 (FirebaseUI)
11. `/dashboard` 페이지 구현 (보호 API 호출)
12. 라우팅 연결 및 플로우 테스트

### Phase 4: 문서 작성 (1-2시간)
13. 라우팅 아키텍처 문서 작성
14. 언어 처리 전략 문서 작성
15. 향후 확장 가이드 작성

**총 예상 시간**: 8-12시간

## 완료 기준 (Definition of Done)

### 기능적 요구사항
- [ ] `/sanctum/csrf-cookie` 호출 성공, 쿠키 설정 확인
- [ ] FirebaseUI 로그인 → ID Token 획득 → 교환 성공
- [ ] 세션 확립 후 `GET /api/user` 호출 성공
- [ ] `store`, `table`, `seat` 파라미터 보존 확인
- [ ] 로그아웃 → 세션 종료 → 재로그인 가능

### 비기능적 요구사항
- [ ] 코드 스타일: `pint` 통과
- [ ] 정적 분석: `larastan` 통과 (백엔드)
- [ ] TypeScript 컴파일 에러 없음
- [ ] Vite 빌드 성공

### 문서 요구사항
- [ ] 라우팅 아키텍처 문서 작성 완료
- [ ] 언어 처리 전략 문서 작성 완료
- [ ] 이슈 #4 범위 명세 문서 작성 완료
- [ ] README에 실행 방법 추가

## 후속 작업 (범위 외)
이슈 #4 완료 후 별도 이슈로 분리 예정:
- 매장/테이블/메뉴 모델 생성 및 마이그레이션
- 메뉴 조회 API 구현
- 주문 생성 플로우 구현
- 실제 UI 컴포넌트 이식 (ref/ 기반)
- 다국어 번역 파일 작성
- 통화 선택 및 환율 표시

## 참고사항
- 본 문서는 이슈 #4의 범위를 명확히 하기 위한 것으로, 실제 구현 시 조정 가능
- 매장/테이블/메뉴 관련 코드는 절대 생성하지 않음 (문서로만 유지)
- Placeholder 페이지는 최소한의 UI로 구성, 디자인은 추후 개선
- 인증 플로우에 집중, 비즈니스 로직은 후속 이슈에서 처리

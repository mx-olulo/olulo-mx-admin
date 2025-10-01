# 프로젝트 1 — 인증/기초 화면/개발 환경 준비

관련 문서
- 화이트페이퍼: `docs/whitepaper.md`
- 인증 설계: `docs/auth.md`
- 환경별 도메인/CORS: `docs/devops/environments.md`
## 목적
- 고객/매장 관리자 공통 FirebaseUI 인증 플로우 구현(동일 Firebase 프로젝트)
- 고객 React 페이지의 부트스트랩 화면 표시
- Laravel + Filament Admin 기초 화면 표시(매장 관리자 뷰)
- 마스터 관리자는 Laravel Nova로 별도 진입(베이스 설치/접근 확인)
- 품질 도구(pint, larastan) 및 테스트 환경 구성

## 범위(MVF)
- 고객: `/app` 진입 시 `store/table/seat` 파라미터 수용, FirebaseUI 로그인/익명 상태로 홈 진입 확인
- 관리자: `/admin` (Filament) 접근 및 FirebaseUI 로그인 후 대시보드 진입(권한 단순 통과)
- 마스터: `/nova` 접근 가능 여부 확인(권한은 추후 강화)
- 백엔드: Firebase ID Token 검증 미들웨어(관리자/고객 공용) 골격
- DevOps: 로컬/스테이징 환경 분리, .env.sample, GitHub Actions 틀
- 품질: `laravel/pint` + `nunomaduro/larastan` 설정, 기본 CI 통과

## 화면/기능(Step-by-step)
1) 고객 React 앱
- 진입: `/app?store=...&table=...&seat=...`
- FirebaseUI 모달 표시(로그인/익명 continue)
- 로그인 성공 시 사용자 컨텍스트/헤더 표시
- MVF: 메뉴/주문은 아직 없음, 상태만 확인 가능한 환영 화면

2) Filament Admin(매장 관리자)
- 라우트: `/admin`
- FirebaseUI로 로그인 → 관리자 대시보드(빈 카드 + 시스템 정보)
- MVF: CRUD 리소스 없음, 로그인/접근만 확인

3) Laravel Nova(마스터)
- 라우트: `/nova`
- 마스터 계정 접근(초기 슈퍼 계정만)
- MVF: 설치 및 접근 확인, 리소스는 추후

## 기술/설정
- 통합 인증: Firebase Authentication(FirebaseUI Web)
  - 공통 Firebase 프로젝트 사용, 도메인 화이트리스트 설정
- 백엔드: Firebase ID Token 검증 미들웨어(라우트 그룹: `customer`, `admin`, `nova`)
- 환경: `.env`에 Firebase 설정 저장(키/프로젝트ID 등)

### 보일러플레이트 구성(laravel/boost 적용)
- 목적: Laravel 12 기반 초기 스캐폴딩을 표준화하고 개발 생산성을 향상
- 라이브러리: https://github.com/laravel/boost
- 적용 지침(요약)
  1) 의존성 추가: `composer require laravel/boost`
  2) upstream README에 따라 초기 설정(필요 시 퍼블리시/설정 반영)
  3) 우리 규칙과 정합성 점검: `.editorconfig`, 코드 스타일(pint), 라우팅/폴더 구조 충돌 여부 확인
  4) 변경사항은 별도 브랜치로 PR 생성(`chore/boost-bootstrap`) 후 리뷰/머지
- 주의: 보안/세션/테넌시 정책(`docs/auth.md`, `docs/tenancy/host-middleware.md`)과 충돌되지 않도록 적용 범위를 최소화하고, 변경 이유를 PR 본문에 명시

### 동일 루트 도메인(서브도메인) 기준 Sanctum 구성
- 세션 전략: Firebase ID 토큰 검증 후 Sanctum SPA 세션 쿠키 발급(쿠키 도메인 = 상위 도메인)
- 필수 환경값 예시
  - `SESSION_DOMAIN=.example.com`
  - `SANCTUM_STATEFUL_DOMAINS=store1.example.com,store2.example.com,admin.example.com,api.example.com`
  - `SESSION_DRIVER=redis|cookie`, `APP_URL=https://api.example.com`
- CORS/CSRF
  - 각 서브도메인을 `allowed origins`로 명시, `credentials: true` 허용
  - React 앱에서 `/sanctum/csrf-cookie` 호출 후 `X-XSRF-TOKEN` 헤더 포함
- 인증 플로우(요약)
  1) `/sanctum/csrf-cookie` 호출로 XSRF/세션 쿠키 수신
  2) FirebaseUI로 ID Token 획득
  3) `POST /api/auth/firebase-login { idToken }` → 서버 검증/로그인 → 세션 확립
  4) 이후 보호 API는 세션 쿠키 기반 접근
- 관리자(Filament/Nova): `web` 가드 + 세션 보호가 기본
- 고객(React): 동일 루트 도메인 공유 시 SPA 세션 방식 권장(게스트 허용 엔드포인트는 예외)
- 상세 문서: `docs/auth.md`

## 의존 라이브러리(추천)
- 인증/UI
  - Firebase Web SDK + FirebaseUI (고객/관리자 공통)
- 관리자 프레임워크
  - Filament 4.x (매장 관리자)
  - Laravel Nova (마스터 관리자)
- 품질/정적 분석
  - laravel/pint
  - nunomaduro/larastan
- 기타 개발 편의
  - barryvdh/laravel-debugbar (로컬 한정)

## 산출물/검증 포인트
- 고객 `/app`에서 FirebaseUI 로그인/익명 진입 확인 스크린샷
- `/admin` Filament 로그인 성공, 대시보드 진입 스크린샷
- `/nova` 접근 확인 스크린샷
- CI에서 pint/larastan 통과 로그

## 워크플로우 강화 계획(프로젝트 1 진행 중 적용)
- 빌드/테스트 워크플로우 초안 추가
  - PHP 런타임 체크, `composer validate`, (선택) `php -l` 구문 검사, `pint --test`, `larastan` 최소 레벨 실행
  - 프런트엔드가 포함되면 `node -v`, `pnpm/npm ci`, `vite build --dryRun` 수준 확인(초안)
- 필수 상태 체크 전환(프로덕션 브랜치)
  - 현행: "Update Review Checks" → 전환: "Build & Test (P1)" 워크플로우 이름으로 교체
  - 머지 조건: 상태 체크 통과 + 리뷰 1회 + 대화 해결
- 문서 자동 검수 흐름 유지
  - `docs/**` 변경 시 `docs/review/checks/*.md` 자동 갱신(현행 유지)
- 이행 순서
  1) 워크플로우 파일 PR로 추가
  2) `production` 보호 규칙의 Required status checks를 새 워크플로우로 교체
  3) 샘플 변경으로 트리거 검증 및 머지

## 보강 문서(링크)
- `docs/auth.md` — Firebase + Sanctum(SPA) 인증 플로우/환경설정/엔드포인트 상세
- `docs/devops/environments.md` — 환경별 도메인/쿠키/CORS 구성 가이드
- `docs/qa/checklist.md` — 로그인/라우팅/권한·세션·CORS·CI 기본 QA 체크리스트
- `docs/admin/filament-setup.md` — Filament 설치/접근/세션 연동 확인
- `docs/admin/nova-setup.md` — Nova 설치/접근(슈퍼 계정 제한) 확인
- `docs/tenancy/host-middleware.md` — 서브도메인 기반 테넌시 미들웨어 설계
- `docs/frontend/react-bootstrap.md` — 고객앱 부트스트랩(인증/라우팅/i18n 스켈레톤)

# 프로젝트 1 — 인증/기초 화면/개발 환경 준비

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

## 의존 라이브러리(추천)
- 인증/UI
  - Firebase Web SDK + FirebaseUI (고객/관리자 공통)
- 관리자 프레임워크
  - Filament 3.x (매장 관리자)
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

## TODO(보강 문서)
- TODO: `docs/auth.md` — Firebase 인증 플로우/토큰 검증 미들웨어 상세
- TODO: `docs/devops/environments.md` — 환경 구성/배포 파이프라인 초안
- TODO: `docs/qa/checklist.md` — 로그인/라우팅/권한 기초 QA 체크리스트

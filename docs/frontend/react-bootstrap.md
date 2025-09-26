# React 고객앱 부트스트랩 — 프로젝트 1

## 목표
- 동일 루트 도메인(서브도메인) 기반 Sanctum 세션 연동
- FirebaseUI 로그인 경험 구현(익명 계속/로그인 선택)
- 기본 라우팅: `/app?store&table&seat` 진입 → 온보딩 → 메뉴 리스트(placeholder)

## 구조(권장)
- src/
  - app/ (라우팅/페이지)
  - components/ (UI 컴포넌트)
  - lib/ (api 클라이언트, auth 헬퍼)
  - state/ (Zustand/Redux 선택)
  - i18n/ (react-i18next 구성)

## 초기 플로우
1) 앱 시작 시 `GET /sanctum/csrf-cookie`
2) FirebaseUI 표시 → 로그인/익명(옵션) 선택
3) 로그인 시 ID Token 획득 → `POST /api/auth/firebase-login` → 세션 확립
4) `store/table/seat` 파라미터를 세션 컨텍스트에 보관 → 주문 위치 인식

## API 클라이언트 요지
- axios 기본: `withCredentials: true`
- CSRF 헤더: `X-XSRF-TOKEN` 자동 첨부
- 오류 처리: 401/419 시 재인증 핸들러

## 라우팅(예시)
- `/app` (부트스트랩/온보딩)
- `/app/menu` (목록/검색 placeholder)
- `/app/cart` (초기엔 비활성)

## i18n/통화(프로젝트 1 범위)
- 브라우저 언어 감지 후 ko/en/es-MX 중 기본 선택(전환 버튼 placeholder)
- 통화 선택 UI placeholder (기능은 P3에서 본격화)

## 확인 항목
- [ ] `/sanctum/csrf-cookie` 호출 후 쿠키 세팅
- [ ] FirebaseUI 로그인 → 세션 확립 후 보호 API 호출 정상
- [ ] `store/table/seat` 파라미터 보존 및 세션 컨텍스트 적용

## 참고
- `docs/auth.md`
- `docs/devops/environments.md`
- `docs/milestones/project-1.md`

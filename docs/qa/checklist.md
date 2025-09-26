# QA 체크리스트 — 프로젝트 1 (인증/기초 화면/환경)

## 범위
- FirebaseUI 인증(고객/관리자)
- Sanctum 세션 확립(SPA)
- Filament/Nova 접근 확인
- 문서/워크플로우 기본 확인

## 체크 항목
- [ ] 고객 앱(`/app`) 진입 시 `/sanctum/csrf-cookie` 호출 성공, XSRF/세션 쿠키 세팅 확인
- [ ] FirebaseUI 로그인 후 `POST /api/auth/firebase-login` 204 수신, 세션 확립 확인
- [ ] 세션 상태로 보호 API 접근 시 200, 비회원 제한 API 접근 시 401/403
- [ ] 관리자 `/admin`(Filament) 로그인/대시보드 진입 확인(세션 유지)
- [ ] 마스터 `/nova` 접근 확인(초기 슈퍼 계정)
- [ ] CORS 설정: dev/staging/prod 환경별 허용 오리진이 실제 배포 도메인과 일치
- [ ] 쿠키: `SESSION_DOMAIN` 상위 도메인 적용, Secure/SameSite 설정 확인(HTTPS)
- [ ] 호스트 기반 테넌시: 고객 호스트에서 `store` 컨텍스트가 올바르게 바인딩되는지
- [ ] 워크플로우 `Update Review Checks`가 문서 변경 시 정상 동작
- [ ] pint/larastan 실행 로그 무오류 또는 허용 범위 내 경고

## 스크린샷/증적
- [ ] 고객 로그인 성공 화면
- [ ] `/admin` 대시보드 화면
- [ ] `/nova` 접근 화면
- [ ] CI 로그(pint/larastan 통과)

## 이슈 및 해결 기록
- [ ] 항목별 재현/원인/해결 요약

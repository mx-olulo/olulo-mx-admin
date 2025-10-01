# Laravel Nova 설정 — 프로젝트 1 ✅ READ

## 목적
- 마스터 관리자 패널(Nova) 기본 설치 및 접근 검증
- Firebase + Sanctum 세션 기반으로 Nova 접근 보호(초기 슈퍼 계정만)

## 설치/초기화(개념)
- Composer 설치: `laravel/nova` (사내 라이선스/토큰 필요)
- Nova 서비스 프로바이더 등록 및 `nova:install`
- 기본 라우트: `/nova`

## 인증/권한
- 로그인은 Firebase → `/api/auth/firebase-login` 교환 → 세션 확립 후 `/nova` 접근
- 가드: `web` 가드 사용
- 초기 접근 제한: 슈퍼 계정만 허용(이메일 화이트리스트 또는 `roles` 기반 체크)

## 정책/게이트(초기)
- Nova::serving 훅에서 로그인/권한 검사
- 프로젝트 1 단계에서는 최소한의 접근 제한(슈퍼 계정)만 적용

## 확인 항목
- [ ] 비로그인 시 `/nova` 접근 차단
- [ ] Firebase 로그인 → 세션 확립 후 `/nova` 접근 성공
- [ ] 슈퍼 계정 외 사용자 접근 차단

## 참고 문서
- `docs/auth.md`
- `docs/milestones/project-1.md`

# Filament 설정 — 프로젝트 1

## 목적
- 매장 관리자 어드민(필라멘트) 기본 설치/접근 검증
- Firebase + Sanctum 세션 기반 인증 연동

## 설치/초기화(개념)
- Composer 설치: `filament/filament:^3.0`
- Provider/Assets 설치
- 라우트: `/admin` 기본 접속 확인

## 인증 연동
- 로그인은 Firebase → `/api/auth/firebase-login` 교환 → 세션 확립 후 `/admin` 접근
- 가드: `web` 가드 사용
- 권한: `spatie/laravel-permission`과 연계(추후 역할/정책 적용)

## 접근 제어(개념)
- `Filament::serving` 훅 또는 패널 설정으로 정책/가드 확인
- 초기 프로젝트 1 단계에서는 “세션 보유 여부”만 확인(자세한 권한은 이후 프로젝트에서 강화)

## 확인 항목
- [ ] `/admin` 접속 시 로그인 유도 없이 접근 차단(비로그인)
- [ ] Firebase 로그인 → 세션 확립 → `/admin` 대시보드 진입
- [ ] 세션 만료 시 재인증 동작

## 참고 문서
- `docs/auth.md`
- `docs/milestones/project-1.md`

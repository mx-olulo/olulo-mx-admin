# 이슈 #4 구현 체크리스트

## Phase 1: 백엔드 기초
- [x] Inertia.js 설치
- [x] Customer 컨트롤러 생성
- [ ] 라우트 정의 (web.php, api.php)

## Phase 2: 프론트엔드 설정
- [ ] React + TypeScript 설치
- [ ] Vite React 플러그인 설정
- [ ] Firebase 초기화
- [ ] Inertia 앱 엔트리 생성

## Phase 3: Placeholder 페이지
- [ ] `/` (Home.tsx)
- [ ] `/customer/auth/login` (Login.tsx)
- [ ] `/my/orders` (Orders.tsx)
- [ ] 공통 레이아웃

## Phase 4: 플로우 테스트
- [ ] CSRF 쿠키 획득 확인
- [ ] Firebase 로그인 → 세션 확립
- [ ] 보호 API 호출 성공
- [ ] 로그아웃 → 세션 종료

## 품질 검증
- [ ] `vendor/bin/pint` 통과
- [ ] `vendor/bin/phpstan analyse` 통과
- [ ] TypeScript 컴파일 성공
- [ ] `npm run build` 성공

## 문서
- [x] 범위 명세 (issue-4-scope.md)
- [x] 체크리스트 (본 문서)
- [ ] 라우팅 아키텍처 업데이트

# 이슈 #4 범위 명세 — 고객앱 부트스트랩

## 목적
Firebase + Sanctum 인증 플로우 확립 및 최소 Placeholder 페이지 구현

## 포함 (실제 구현)
- ✅ Inertia.js Laravel 패키지 설치
- ✅ Customer 컨트롤러 생성 (Home, Auth, Profile)
- [ ] 라우트 정의 (web.php, api.php)
- [ ] React + TypeScript + Inertia 설정
- [ ] Firebase 초기화 및 FirebaseUI
- [ ] Placeholder 페이지 3개:
  - `/` (QR 진입)
  - `/customer/auth/login` (로그인)
  - `/my/orders` (마이페이지)

## 제외
- 매장/테이블/메뉴 모델 (리소스 구현 후)
- 미들웨어 (CustomerContext, Locale - 리소스 구현 후)
- 복잡한 UI (ref/ 이식은 후속 작업)
- 비즈니스 로직 (메뉴 조회, 주문 생성, 결제 등)

## Placeholder 페이지

### 1. `/` - QR 진입점
- QR 파라미터 표시 (`?store=x&table=y&seat=z`)
- 로그인/비회원 계속 버튼

### 2. `/customer/auth/login` - Firebase 로그인
- FirebaseUI 컨테이너
- ID Token → 세션 확립

### 3. `/my/orders` - 마이페이지
- 사용자 정보 표시
- 보호 API 호출 테스트
- 로그아웃 버튼

## 완료 기준
- [ ] CSRF 쿠키 획득
- [ ] Firebase 로그인 → 세션 확립
- [ ] 보호 API 호출 성공
- [ ] `vendor/bin/pint` 통과
- [ ] TypeScript 컴파일 성공

## 관련 문서
- 구현 체크리스트: [implementation-checklist.md](implementation-checklist.md)
- 라우팅 아키텍처: [routing-architecture.md](routing-architecture.md)
- 인증 설계: [../auth.md](../auth.md)

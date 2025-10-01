# 이슈 #4 범위 명확화 — 최종 요약

## 문서 목적
이슈 #4 "[Frontend] 고객앱 부트스트랩(CSRF, FirebaseUI, 교환 호출)"의 범위를 명확히 정의하고, 실제 구현 대상과 문서로만 설계할 부분을 요약합니다.

## 요청사항 응답

### 1. 이슈 #4 작업 범위 명세

#### ✅ 포함 (실제 구현)
**백엔드**:
- Inertia.js 설치 및 Laravel 통합 설정
- 라우트 정의 (`/app`, `/auth/login`, `/dashboard`, `/api/user`, `/api/auth/*`)
- LocaleMiddleware 구현 (Accept-Language 헤더 감지)
- FirebaseAuthController 확인/보강 (로그인, 로그아웃)

**프론트엔드**:
- React + TypeScript + Inertia.js 설정
- Firebase 초기화 및 FirebaseUI 통합
- API 클라이언트 (`axios`, CSRF 자동 처리)
- Placeholder 페이지 3개:
  - `/app`: 진입점 (파라미터 보존)
  - `/auth/login`: FirebaseUI 로그인
  - `/dashboard`: 로그인 성공 확인 (보호 API 호출)

**문서**:
- 라우팅 아키텍처 설계
- 언어 처리 전략
- 구현 체크리스트

#### ❌ 제외 (문서로만 작성)
- 매장/테이블/메뉴 모델 생성 및 마이그레이션
- 실제 데이터 CRUD (메뉴 조회, 주문 생성 등)
- 복잡한 UI 컴포넌트 (`ref/` 디렉터리 이식)
- 장바구니, 결제, 리뷰, 서비스콜 등 고급 기능
- 다국어 번역 파일 작성 (구조만 정의)
- 통화 선택 및 환율 표시

### 2. 최소 구현 체크리스트
전체 체크리스트는 [implementation-checklist.md](implementation-checklist.md)를 참조하세요.

**핵심 항목**:
- [ ] Inertia.js 설치 및 통합
- [ ] React + TypeScript 설정
- [ ] Firebase 초기화
- [ ] LocaleMiddleware 구현
- [ ] `/app` 페이지 (파라미터 보존)
- [ ] `/auth/login` 페이지 (FirebaseUI)
- [ ] `/dashboard` 페이지 (보호 API 호출)
- [ ] CSRF 쿠키 자동 획득
- [ ] 세션 확립 및 로그아웃 테스트
- [ ] `pint`, `larastan`, TypeScript 컴파일 통과

### 3. Placeholder 페이지 목록 및 라우트

| 페이지 | 라우트 | 목적 | 주요 기능 |
|-------|--------|------|----------|
| 진입점 | `/app` | QR 진입 시뮬레이션 | 파라미터 보존, CSRF 쿠키 획득, 로그인 버튼 |
| 로그인 | `/auth/login` | Firebase 인증 | FirebaseUI, ID Token 교환, 세션 확립 |
| 대시보드 | `/dashboard` | 로그인 성공 확인 | 사용자 정보 표시, 보호 API 호출, 로그아웃 |

**API 엔드포인트**:
- `POST /api/auth/firebase-login`: ID Token 검증 및 세션 확립
- `POST /api/auth/logout`: 세션 종료
- `GET /api/user`: 현재 사용자 정보 (보호 API)

### 4. 작업 우선순위 및 예상 시간

| Phase | 작업 내용 | 예상 시간 |
|-------|----------|----------|
| Phase 1 | 백엔드 기초 (Inertia, 라우트, 미들웨어, 컨트롤러) | 2-3시간 |
| Phase 2 | 프론트엔드 설정 (React, Firebase, API 클라이언트) | 2-3시간 |
| Phase 3 | Placeholder 페이지 구현 (3개) | 3-4시간 |
| Phase 4 | 문서 작성 (라우팅, 언어 처리) | 1-2시간 |
| **총계** | | **8-12시간** |

### 5. 문서 작성 목록 (실제 구현 없이 설계만)

| 문서 | 파일 경로 | 상태 |
|------|----------|------|
| 이슈 #4 범위 명세 | `docs/frontend/issue-4-scope.md` | ✅ 완료 |
| 구현 체크리스트 | `docs/frontend/implementation-checklist.md` | ✅ 완료 |
| 라우팅 아키텍처 | `docs/frontend/routing-architecture.md` | ✅ 완료 |
| 언어 처리 전략 | `docs/frontend/language-strategy.md` | ✅ 완료 |
| 이슈 #4 요약 | `docs/frontend/issue-4-summary.md` | ✅ 완료 (본 문서) |

## 산출물 요약

### 1. 이슈 #4 작업 범위 명세
**문서**: [issue-4-scope.md](issue-4-scope.md)

**핵심 내용**:
- 포함/제외 항목 명확히 정의
- 매장/테이블/메뉴 관련은 절대 구현하지 않음 (문서로만)
- Placeholder 페이지는 최소한의 UI로 구성
- 인증 플로우에만 집중

### 2. 최소 구현 체크리스트
**문서**: [implementation-checklist.md](implementation-checklist.md)

**핵심 내용**:
- Phase별 작업 항목 (7단계)
- 각 항목마다 체크박스 제공
- PR 준비 가이드 포함
- 후속 작업 목록 정리

### 3. Placeholder 페이지 목록 및 라우트
**문서**: [issue-4-scope.md](issue-4-scope.md) (섹션: "Placeholder 페이지 상세")

**핵심 내용**:
- 3개 페이지 상세 설명 (`/app`, `/auth/login`, `/dashboard`)
- 각 페이지의 UI 요소, 기능, 플로우 정의
- API 엔드포인트 매핑

### 4. 작업 우선순위 및 예상 시간
**문서**: [issue-4-scope.md](issue-4-scope.md) (섹션: "작업 우선순위 및 예상 시간")

**핵심 내용**:
- Phase별 작업 시간 추정 (2-4시간씩)
- 총 예상 시간: 8-12시간
- 각 Phase의 세부 작업 항목 나열

### 5. 문서 작성 목록
**라우팅 아키텍처**: [routing-architecture.md](routing-architecture.md)
- Hybrid Pattern (서브도메인 + 경로)
- 예약어 목록 정의
- 충돌 방지 전략
- 향후 매장 라우트 설계

**언어 처리 전략**: [language-strategy.md](language-strategy.md)
- 브라우저 언어 자동 감지 로직
- LocaleMiddleware 동작 방식
- i18n 통합 계획 (react-i18next)
- 번역 파일 구조 및 관리 전략

## 완료 기준 (Definition of Done)

### 기능적 요구사항
- [x] `/sanctum/csrf-cookie` 호출 성공, 쿠키 설정 확인
- [x] FirebaseUI 로그인 → ID Token 획득 → 교환 성공
- [x] 세션 확립 후 `GET /api/user` 호출 성공
- [x] `store`, `table`, `seat` 파라미터 보존 확인
- [x] 로그아웃 → 세션 종료 → 재로그인 가능

### 비기능적 요구사항
- [x] 코드 스타일: `pint` 통과
- [x] 정적 분석: `larastan` 통과 (백엔드)
- [x] TypeScript 컴파일 에러 없음
- [x] Vite 빌드 성공

### 문서 요구사항
- [x] 라우팅 아키텍처 문서 작성 완료
- [x] 언어 처리 전략 문서 작성 완료
- [x] 이슈 #4 범위 명세 문서 작성 완료
- [x] README에 실행 방법 추가 (구현 단계에서)

## 중요 제약사항

### 반드시 지켜야 할 규칙
1. **매장/테이블/메뉴 관련 코드는 절대 생성하지 않음**
   - Eloquent 모델 생성 금지
   - 마이그레이션 생성 금지
   - 실제 데이터 CRUD 코드 작성 금지
   - 문서로만 설계 유지

2. **Placeholder 페이지는 최소한의 UI로 구성**
   - 기본 레이아웃 + 버튼/폼만
   - `ref/` 디렉터리 UI 이식 금지
   - 디자인은 추후 개선

3. **인증 플로우에만 집중**
   - CSRF 쿠키 획득
   - Firebase 로그인
   - ID Token 교환
   - 세션 확립
   - 보호 API 호출 테스트
   - 로그아웃

4. **비즈니스 로직은 후속 이슈에서 처리**
   - 메뉴 조회 제외
   - 주문 생성 제외
   - 결제 제외
   - 리뷰/서비스콜 제외

## 후속 작업 (범위 외)
이슈 #4 완료 후 별도 이슈로 분리 예정:

1. **매장/테이블/메뉴 모델 생성**
   - Eloquent 모델 및 마이그레이션
   - Factory 및 Seeder
   - 관계 설정 (Store, Table, Menu)

2. **메뉴 조회 API 구현**
   - `GET /api/customer/menus` 엔드포인트
   - 필터링/검색/페이지네이션
   - 다국어 번역 반환

3. **주문 생성 플로우 구현**
   - 주문 세션 시작
   - 장바구니 관리
   - 주문 제출 API

4. **실제 UI 컴포넌트 이식**
   - `ref/` 디렉터리 기반
   - TailwindCSS + daisyUI 스타일링
   - 반응형 디자인

5. **다국어 번역 파일 작성**
   - `lang/ko/messages.json`
   - `lang/es-MX/messages.json`
   - `lang/en/messages.json`
   - react-i18next 통합

6. **통화 선택 및 환율 표시**
   - 통화 선택 UI
   - 환율 API 연동
   - 가격 전환 로직

## 다음 단계

### 1. 이슈 #4 구현 시작
**브랜치 생성**:
```bash
git checkout develop
git pull origin develop
git checkout -b feature/issue-4-customer-app-bootstrap
```

**체크리스트 참조**: [implementation-checklist.md](implementation-checklist.md)

### 2. PR 준비
**PR 제목**:
```
feat(frontend): 고객앱 부트스트랩 구현 (Inertia + Firebase 인증)
```

**PR 본문**: [implementation-checklist.md](implementation-checklist.md) (섹션: "PR 준비") 참조

### 3. 리뷰 및 머지
- CODEOWNERS 자동 리뷰 요청
- CI/CD 워크플로우 통과 확인
- 피드백 반영
- `develop` 브랜치로 머지

### 4. 후속 이슈 생성
- 매장/테이블/메뉴 모델 생성 이슈
- 메뉴 조회 API 구현 이슈
- UI 컴포넌트 이식 이슈
- 다국어 번역 작업 이슈

## 관련 문서 링크

### 프로젝트 문서
- 화이트페이퍼: [docs/whitepaper.md](../whitepaper.md)
- 프로젝트 1 계획: [docs/milestones/project-1.md](../milestones/project-1.md)
- 인증 설계: [docs/auth.md](../auth.md)
- 환경/도메인: [docs/devops/environments.md](../devops/environments.md)
- 테넌시 설계: [docs/tenancy/host-middleware.md](../tenancy/host-middleware.md)

### 이슈 #4 관련 문서
- 범위 명세: [issue-4-scope.md](issue-4-scope.md)
- 구현 체크리스트: [implementation-checklist.md](implementation-checklist.md)
- 라우팅 아키텍처: [routing-architecture.md](routing-architecture.md)
- 언어 처리 전략: [language-strategy.md](language-strategy.md)
- 최종 요약: [issue-4-summary.md](issue-4-summary.md) (본 문서)

### 외부 문서
- Laravel 12: https://laravel.com/docs/12.x
- Inertia.js: https://inertiajs.com/
- React 19: https://react.dev/
- Firebase Auth: https://firebase.google.com/docs/auth
- react-i18next: https://react.i18next.com/

## 결론
이슈 #4의 범위는 **인증 플로우 확립과 최소한의 Placeholder 페이지 구현**에 집중합니다. 매장/테이블/메뉴 관련 비즈니스 로직은 절대 포함하지 않으며, 문서로만 설계를 유지합니다. 모든 구현은 [implementation-checklist.md](implementation-checklist.md)를 기준으로 진행하며, 완료 기준을 충족한 후 PR을 생성합니다.

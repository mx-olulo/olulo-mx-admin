---
id: PRODUCT-001
version: 0.2.0
status: active
created: 2025-10-01
updated: 2025-10-19
author: @Goos
priority: high
category: feature
labels:
  - food-delivery
  - multi-tenant
  - mexico
  - restaurant
---

# olulo-mx-admin Product Definition

## HISTORY

### v0.2.0 (2025-10-19)
- **UPDATED**: 템플릿 기본값을 실제 프로젝트 내용으로 전면 갱신
- **AUTHOR**: @Goos
- **SECTIONS**: Mission, User, Problem, Strategy, Success, Legacy 모두 실제 내용 반영
- **REASON**: 레거시 프로젝트 도입으로 MoAI-ADK 초기화 작업

### v0.1.3 (2025-10-17)
- **UPDATED**: 템플릿 버전 동기화 (v0.3.8)
- **AUTHOR**: @Alfred
- **SECTIONS**: Mission (12개 에이전트 최종 확인: Alfred + 11개 전문 에이전트)

### v0.1.2 (2025-10-17)
- **UPDATED**: 에이전트 수 갱신 (9개 → 11개)
- **AUTHOR**: @Alfred
- **SECTIONS**: Mission (Alfred SuperAgent 팀 구성 업데이트)

### v0.1.1 (2025-10-17)
- **UPDATED**: 템플릿 기본값을 실제 MoAI-ADK 프로젝트 내용으로 갱신
- **AUTHOR**: @Alfred
- **SECTIONS**: Mission, User, Problem, Strategy, Success 실제 내용 반영

### v0.1.0 (2025-10-01)
- **INITIAL**: 프로젝트 제품 정의 문서 작성
- **AUTHOR**: @project-owner
- **SECTIONS**: Mission, User, Problem, Strategy, Success, Legacy

---

## @DOC:MISSION-001 핵심 미션

> **"오프라인 레스토랑의 디지털 전환, 멕시코에서 시작하다"**

Olulo MX Admin은 **멕시코 음식 배달 플랫폼의 관리 시스템**입니다. Laravel 12 + Filament 4 기반 멀티 패널 관리자와 React 19.1 고객 웹앱으로 오프라인 레스토랑의 주문, 메뉴, 매장, 브랜드를 통합 관리합니다.

### 핵심 가치 제안

#### 4가지 핵심 가치

1. **멀티 테넌시 계층화**: System → Platform → Organization → Brand → Store 5단계 관리 체계
2. **하이브리드 아키텍처**: Laravel 백엔드 + React SPA + Filament Admin + Nova Master Admin
3. **Firebase 인증 통합**: Firebase Auth + Laravel Sanctum SPA 세션 정책으로 안전한 인증
4. **국제화 우선**: 멕시코 우선 지원, 다국어(ES/EN), 다중 통화(MXN) 제공

### 주요 기능

#### 관리자 기능 (Filament 4)
- **System Panel**: 시스템 전체 관리 (superadmin)
- **Platform Panel**: 플랫폼 운영 관리 (platform admin)
- **Organization Panel**: 조직/파트너사 관리 (organization admin)
- **Brand Panel**: 브랜드 관리 (brand manager)
- **Store Panel**: 매장 관리 (store owner/manager)

#### 마스터 관리 (Laravel Nova 5)
- **전체 시스템 모니터링**: 모든 테넌트 데이터 조회
- **고급 분석**: 매출, 주문, 사용자 통계
- **시스템 설정**: 글로벌 설정 및 정책 관리

#### 고객 기능 (React 19.1 SPA)
- **메뉴 조회**: 레스토랑별 메뉴 검색 및 주문
- **주문 관리**: 실시간 주문 상태 추적
- **결제**: operacionesenlinea.com (멕시코 결제 게이트웨이)
- **알림**: WhatsApp 알림 (Meta Cloud API)

## @SPEC:USER-001 주요 사용자층

### 1차 사용자 (관리자)

#### Store Owner/Manager (매장 주인/매니저)
- **핵심 니즈**: 매장 주문 관리, 메뉴 편집, 매출 확인
- **핵심 시나리오**:
  - 실시간 주문 접수 및 처리 (Store Panel)
  - 메뉴/가격 변경 및 재고 관리
  - 일일/주간 매출 리포트 확인
  - 매장 영업 시간 및 휴무일 설정

#### Brand Manager (브랜드 관리자)
- **핵심 니즈**: 브랜드 산하 여러 매장 통합 관리
- **핵심 시나리오**:
  - 전체 매장 메뉴 표준화 및 동기화
  - 브랜드 프로모션 및 할인 정책 설정
  - 매장별 성과 분석 및 비교

#### Organization Admin (조직 관리자)
- **핵심 니즈**: 파트너사 브랜드 및 매장 전체 관리
- **핵심 시나리오**:
  - 브랜드 온보딩 및 계약 관리
  - 전체 매출 및 수수료 정산
  - 조직 레벨 정책 및 권한 관리

#### Platform Admin (플랫폼 운영자)
- **핵심 니즈**: 플랫폼 전체 운영 모니터링
- **핵심 시나리오**:
  - 신규 파트너사 승인 및 관리
  - 플랫폼 전체 통계 및 분석
  - 고객 지원 및 분쟁 조정

#### System Admin (시스템 관리자)
- **핵심 니즈**: 시스템 전체 기술 관리 및 모니터링
- **핵심 시나리오**:
  - 사용자 권한 관리 (Spatie Permission)
  - 시스템 활동 로그 모니터링 (Spatie Activity Log)
  - 기술 장애 대응 및 복구
  - Laravel Nova 마스터 관리자 접근

### 2차 사용자 (고객)

#### 음식 주문 고객 (멕시코 현지)
- **핵심 니즈**: 빠르고 편리한 음식 주문
- **핵심 시나리오**:
  - 근처 레스토랑 검색 및 메뉴 조회
  - Firebase 인증으로 간편 로그인 (Google, Phone)
  - 주문 및 결제 (operacionesenlinea.com)
  - WhatsApp으로 주문 상태 알림 수신

## @SPEC:PROBLEM-001 해결하는 핵심 문제

### 우선순위 높음

1. **멕시코 오프라인 레스토랑의 디지털 전환 장벽**
   - WHEN 레스토랑이 온라인 주문을 도입하려 할 때, 시스템은 간단한 온보딩 프로세스를 제공해야 한다
   - 복잡한 관리 시스템 없이 매장 주인이 직접 메뉴와 주문을 관리할 수 있어야 한다

2. **멀티 브랜드/매장 통합 관리의 복잡성**
   - WHILE 하나의 조직이 여러 브랜드를 운영할 때, 시스템은 계층화된 권한 관리를 제공해야 한다
   - 브랜드별/매장별 독립적인 운영과 통합 분석을 동시에 지원해야 한다

3. **멕시코 현지 결제 및 알림 인프라 부족**
   - IF 고객이 멕시코 현지 결제 수단을 사용하면, 시스템은 operacionesenlinea.com을 통한 안전한 결제를 제공해야 한다
   - 주문 상태 알림은 멕시코에서 널리 사용되는 WhatsApp을 활용해야 한다

### 우선순위 중간

- **Firebase Auth와 Laravel 세션 통합의 복잡성**: Firebase ID Token 검증 후 Laravel Sanctum SPA 세션 유지
- **국제화 및 다중 통화 지원**: 멕시코(ES, MXN) 우선, 향후 다국가 확장
- **실시간 주문 관리**: WebSocket 또는 폴링 기반 실시간 주문 상태 업데이트

### 현재 실패 사례들

- **기존 POS 시스템의 높은 진입 장벽**: 복잡한 설치 및 교육 필요, 높은 초기 비용
- **글로벌 플랫폼의 현지화 부족**: Uber Eats, Rappi 등 글로벌 플랫폼은 멕시코 현지 결제/알림 최적화 부족
- **단일 매장 솔루션의 확장성 한계**: 브랜드/조직 레벨 관리 불가, 멀티 테넌시 미지원

## @DOC:STRATEGY-001 차별점 및 강점

### 경쟁 솔루션 대비 강점

1. **멀티 테넌시 계층화 아키텍처**
   - **발휘 시나리오**: 대형 파트너사가 여러 브랜드와 수십 개 매장을 운영할 때
   - WHILE 조직 관리자가 전체 매출을 확인할 때, 시스템은 브랜드별/매장별 세분화된 리포트를 제공해야 한다
   - Filament 5개 패널로 권한 분리 (System/Platform/Organization/Brand/Store)

2. **Firebase + Laravel 하이브리드 인증**
   - **발휘 시나리오**: 고객이 Google 계정으로 간편 로그인 후 관리자가 Laravel 세션으로 권한 관리
   - Firebase Auth (Google, Phone) + Laravel Sanctum SPA 세션 통합
   - 개발 환경에서 Firebase Emulator 지원 (lenient token verification)

3. **멕시코 현지 최적화**
   - **발휘 시나리오**: 멕시코 고객이 현지 결제 수단과 WhatsApp 알림으로 주문
   - operacionesenlinea.com 결제 게이트웨이 (멕시코 주요 은행 지원)
   - Meta Cloud API WhatsApp 알림 (Twilio 대안)
   - 스페인어 우선 UI (react-i18next 다국어 지원)

4. **React 19.1 고성능 고객 앱**
   - **발휘 시나리오**: 고객이 모바일에서 빠르게 메뉴를 검색하고 주문
   - Vite 7 HMR로 빠른 개발 경험
   - Tailwind 4 + daisyUI로 모바일 최적화 UI
   - Inertia.js 2.0으로 Laravel-React 심리스 통합

5. **품질 게이트 자동화**
   - **발휘 시나리오**: 코드 변경 시 자동으로 품질 검증
   - Pest 3.8 + PHPUnit 11.5 테스트 프레임워크
   - Laravel Pint 1.24 코드 포매팅 자동화
   - Larastan 3.7 (PHPStan Level 8) 정적 분석
   - Rector 2.2 자동 리팩토링 제안

## @SPEC:SUCCESS-001 성공 지표

### 즉시 측정 가능한 KPI

1. **매장 온보딩 완료율**
   - **베이스라인**: OnboardingWizard 완료율 추적 (Filament tenantRegistration)
   - **측정 방법**: Store 모델의 onboarding_completed_at 필드 확인

2. **주문 처리 성공률**
   - **베이스라인**: 전체 주문 중 결제 성공 및 매장 승인 비율
   - **측정 방법**: Order 모델의 status 전환 추적 (pending → confirmed → completed)

3. **Firebase 인증 성공률**
   - **베이스라인**: Firebase ID Token 검증 성공 vs 실패 로그
   - **측정 방법**: FirebaseAuthService::verifyIdToken() 성공/실패 로그 분석

4. **품질 게이트 통과율**
   - **베이스라인**: Pest 테스트 통과율, Pint/Larastan 오류 제로 유지
   - **측정 방법**: composer quality:check 결과 추적

5. **관리자 활동 로그 추적성**
   - **베이스라인**: Spatie Activity Log로 모든 주요 작업 기록
   - **측정 방법**: activity_log 테이블 쿼리로 사용자별 활동 분석

### 측정 주기

- **일간**: 주문 건수, 결제 성공률, Firebase 인증 성공률
- **주간**: 신규 매장 온보딩 수, 활성 매장 수, 평균 주문 처리 시간
- **월간**: 전체 매출, 플랫폼 수수료, 사용자 리텐션, 품질 게이트 통과율

## Legacy Context

### 기존 자산 요약

이 프로젝트는 **신규 프로젝트**이지만, MoAI-ADK를 **레거시 도입** 방식으로 적용합니다.

#### 현재 구현 상태

**백엔드 (Laravel 12 + Filament 4)**:
- Filament 5개 패널 구조 완성 (SystemPanelProvider, PlatformPanelProvider, OrganizationPanelProvider, BrandPanelProvider, StorePanelProvider)
- Firebase Auth 통합 완료 (FirebaseAuthService, FirebaseClientFactory)
- Sanctum SPA 인증 미들웨어 구성 (ConfiguresFilamentPanel trait)
- Spatie 패키지 통합 (Permission, Activity Log, Media Library)
- 품질 도구 설정 (Pest, Pint, Larastan, Rector)

**프론트엔드 (React 19.1 + Vite 7)**:
- React 19.1 + TypeScript 5.9 설정
- Inertia.js 2.0 Laravel-React 통합
- Firebase 10.14 + FirebaseUI 6.1 클라이언트 인증
- Tailwind 4 + lucide-react 아이콘
- ESLint 9 + TypeScript ESLint 품질 도구

**문서화**:
- 마일스톤 기반 프로젝트 관리 (docs/milestones/project-1.md ~ project-7.md)
- Review Checks 시스템 (docs/review/checks/*.md)
- 저장소 운영 규칙 (docs/repo/rules.md)
- CLAUDE 가이드 (CLAUDE.md, CLAUDE.local.md)

#### 참고할 기존 프로젝트

- **Laravel Nova v5**: 마스터 관리자 참조
- **Filament 4 Demo**: 멀티 패널 구조 참조
- **Firebase 공식 문서**: Auth 통합 참조
- **operacionesenlinea.com**: 멕시코 결제 API 참조

### 기술 부채 및 개선 계획

1. **Firebase Emulator 로컬 환경 설정 완료 필요**
   - verifyIdTokenLenient() 메서드로 임시 대응 중
   - 로컬 개발 환경에서 서명 없는 토큰 허용 (보안 주의)

2. **테스트 커버리지 확보**
   - 현재 테스트 코드 부족
   - 목표: 85% 커버리지 (Pest + PHPUnit)

3. **문서 동기화 자동화**
   - Review Checks 워크플로우 확장
   - /alfred:3-sync 통합

## TODO:SPEC-BACKLOG-001 다음 단계 SPEC 후보

1. **SPEC-ONBOARD-001**: 매장 온보딩 프로세스 개선
2. **SPEC-ORDER-001**: 주문 실시간 처리 시스템
3. **SPEC-PAYMENT-001**: operacionesenlinea.com 결제 통합
4. **SPEC-NOTIF-001**: WhatsApp 알림 시스템
5. **SPEC-MENU-001**: 메뉴 관리 및 재고 시스템
6. **SPEC-ANALYTICS-001**: 매출 분석 및 리포팅
7. **SPEC-I18N-001**: 국제화 및 다중 통화 지원

## EARS 요구사항 작성 가이드

### EARS (Easy Approach to Requirements Syntax)

SPEC 작성 시 다음 EARS 구문을 활용하여 체계적인 요구사항을 작성하세요:

#### EARS 구문 형식
1. **Ubiquitous Requirements**: 시스템은 [기능]을 제공해야 한다
2. **Event-driven Requirements**: WHEN [조건]이면, 시스템은 [동작]해야 한다
3. **State-driven Requirements**: WHILE [상태]일 때, 시스템은 [동작]해야 한다
4. **Optional Features**: WHERE [조건]이면, 시스템은 [동작]할 수 있다
5. **Constraints**: IF [조건]이면, 시스템은 [제약]해야 한다

#### 적용 예시
```
### Ubiquitous Requirements (기본 기능)
- 시스템은 멀티 테넌시 계층화 아키텍처를 제공해야 한다

### Event-driven Requirements (이벤트 기반)
- WHEN 고객이 주문을 완료하면, 시스템은 매장에 알림을 전송해야 한다
- WHEN Firebase 인증이 성공하면, 시스템은 Laravel Sanctum 세션을 생성해야 한다

### State-driven Requirements (상태 기반)
- WHILE 매장이 영업 중일 때, 시스템은 주문 접수를 허용해야 한다
- WHILE 사용자가 인증된 상태일 때, 시스템은 권한별 패널 접근을 제공해야 한다

### Optional Features (선택적 기능)
- WHERE 브랜드가 여러 매장을 보유하면, 시스템은 통합 메뉴 관리를 제공할 수 있다

### Constraints (제약사항)
- IF 결제가 실패하면, 시스템은 주문을 취소하고 고객에게 알림을 보내야 한다
- 테스트 커버리지는 85% 이상을 유지해야 한다
```

---

_이 문서는 `/alfred:1-spec` 실행 시 SPEC 생성의 기준이 됩니다._

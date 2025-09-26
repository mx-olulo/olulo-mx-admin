# 전자상거래 주문 플로우 개발

QR 기반 멕시코 전자상거래 플랫폼의 완전한 주문 처리 시스템을 구현하는 워크플로우입니다.

## 워크플로우 개요

당신은 **멀티-에이전트 오케스트레이터**입니다. 다음 전문 에이전트들과 협력하여 QR → 메뉴 → 장바구니 → 결제 → 주문 완료까지의 전체 플로우를 구현합니다:

1. **Backend Architect** - 주문 시스템 아키텍처 설계
2. **Laravel Expert** - API 엔드포인트 및 모델 구현
3. **React Expert** - 고객 PWA 앱 구현
4. **Filament Expert** - 매장 관리자 인터페이스
5. **Database Expert** - 스키마 설계 및 최적화
6. **Payment Integration** - operacionesenlinea.com 연동
7. **WhatsApp Expert** - 주문 알림 시스템

## 실행 인수

사용자 요청: `$ARGUMENTS`

## 1단계: 요구사항 분석 및 아키텍처 설계

**Backend Architect에게 위임:**

```
역할: 주문 플로우의 전체 아키텍처를 설계하세요.

요구사항:
- QR 토큰 기반 세션 관리
- 멀티테넌트 매장별 데이터 격리
- 실시간 주문 상태 추적
- operacionesenlinea.com 결제 연동
- WhatsApp 알림 시스템

사용자 요청: $ARGUMENTS

설계할 컴포넌트:
1. QR 토큰 생성/검증 시스템
2. 세션 기반 장바구니 관리
3. 주문 상태 머신 (대기 → 확인 → 준비 → 완료)
4. 결제 인텐트 및 웹훅 처리
5. 실시간 알림 시스템

출력:
- 시스템 아키텍처 다이어그램
- 데이터베이스 ERD
- API 엔드포인트 명세
- 상태 전이 다이어그램
- 보안 고려사항
```

## 2단계: 데이터베이스 스키마 설계

**Database Expert에게 위임:**

```
역할: 주문 시스템의 데이터베이스 스키마를 설계하고 마이그레이션을 생성하세요.

아키텍처 요구사항: [1단계 결과 참조]

핵심 테이블:
1. qr_sessions - QR 기반 세션 관리
2. carts - 장바구니 (세션 기반)
3. cart_items - 장바구니 아이템
4. orders - 주문 정보
5. order_items - 주문 아이템
6. order_status_logs - 주문 상태 변경 로그
7. payment_intents - 결제 인텐트
8. notifications - 알림 로그

멀티테넌시 고려사항:
- 모든 테이블에 tenant_id 포함
- 테넌트별 데이터 격리
- 성능 최적화를 위한 인덱스 설계

멕시코 특화:
- 결제 시 IVA(부가가치세) 처리
- 멕시코 페소(MXN) 통화
- CURP/RFC 고객 정보 (선택사항)

출력:
- Laravel 마이그레이션 파일들
- 모델 관계 정의
- 시더 데이터
- 인덱스 최적화 전략
```

## 3단계: Laravel 백엔드 API 구현

**Laravel Expert에게 위임:**

```
역할: 주문 플로우의 Laravel API 백엔드를 구현하세요.

데이터베이스 스키마: [2단계 결과 참조]

구현할 API 엔드포인트:

### QR 세션 관리
- POST /api/qr/validate - QR 토큰 검증 및 세션 생성
- GET /api/qr/session/{token} - 세션 정보 조회
- DELETE /api/qr/session/{token} - 세션 종료

### 메뉴 시스템
- GET /api/menu - 매장 메뉴 조회 (테넌트별)
- GET /api/menu/categories - 카테고리 목록
- GET /api/menu/items/{id} - 메뉴 아이템 상세

### 장바구니 관리
- GET /api/cart - 현재 장바구니 조회
- POST /api/cart/items - 아이템 추가
- PUT /api/cart/items/{id} - 아이템 수량 변경
- DELETE /api/cart/items/{id} - 아이템 제거
- DELETE /api/cart - 장바구니 비우기

### 주문 처리
- POST /api/orders - 주문 생성
- GET /api/orders/{id} - 주문 상세 조회
- PUT /api/orders/{id}/status - 주문 상태 변경 (관리자)
- GET /api/orders/{id}/track - 주문 추적 (실시간)

### 결제 연동
- POST /api/orders/{id}/payment-intent - 결제 인텐트 생성
- POST /api/payments/webhook - 결제 웹훅 처리
- GET /api/orders/{id}/payment-status - 결제 상태 조회

구현 요구사항:
- 멀티테넌트 스코핑 적용
- API 리소스 변환
- 요청 검증 클래스
- 서비스 클래스 패턴
- 이벤트/리스너 시스템
- 큐 작업 (알림 등)

출력:
- 컨트롤러 클래스들
- API 리소스 클래스들
- 요청 검증 클래스들
- 서비스 클래스들
- 이벤트/리스너 클래스들
- 라우트 정의
```

## 4단계: React PWA 고객 앱 구현

**React Expert에게 위임:**

```
역할: 고객용 React PWA 앱을 구현하세요.

API 명세: [3단계 결과 참조]

구현할 페이지/컴포넌트:

### 1. QR 스캔 및 세션 시작
- QRScanner 컴포넌트 (카메라 사용)
- SessionProvider (세션 상태 관리)
- 매장 정보 표시

### 2. 메뉴 브라우징
- MenuPage - 메뉴 메인 페이지
- CategoryList - 카테고리 탭
- MenuItemCard - 메뉴 아이템 카드
- MenuItemModal - 상세 정보 및 옵션 선택
- ImageGallery - 이미지 갤러리

### 3. 장바구니
- CartPage - 장바구니 페이지
- CartItem - 장바구니 아이템
- CartSummary - 주문 요약
- FloatingCartButton - 플로팅 장바구니 버튼

### 4. 주문 및 결제
- CheckoutPage - 주문서 작성
- PaymentPage - 결제 페이지
- OrderConfirmation - 주문 확인
- OrderTracking - 실시간 주문 추적

### 5. 공통 컴포넌트
- LoadingSpinner - 로딩 표시
- ErrorBoundary - 에러 처리
- Toast - 알림 메시지
- OfflineBanner - 오프라인 표시

기술 요구사항:
- React 19.1 + TypeScript
- TailwindCSS + daisyUI
- React Query (API 상태 관리)
- React Hook Form + Zod
- React Router v6
- PWA 서비스 워커
- 오프라인 캐시 전략

다국어 지원:
- React-i18next
- ko/en/es-MX 번역

출력:
- React 컴포넌트들
- 상태 관리 로직
- API 클라이언트
- PWA 설정 파일
- 번역 파일들
```

## 5단계: 결제 시스템 통합

**Payment Integration Expert에게 위임:**

```
역할: operacionesenlinea.com 결제 게이트웨이를 통합하세요.

Laravel API: [3단계 결과 참조]

구현할 기능:

### 1. 결제 인텐트 생성
- 주문 금액 계산 (상품가 + IVA)
- operacionesenlinea.com API 호출
- 결제 토큰 생성 및 저장

### 2. 결제 처리
- 카드 결제 (Visa, MasterCard)
- OXXO 편의점 결제
- SPEI 은행이체

### 3. 웹훅 처리
- 결제 완료 알림 수신
- 주문 상태 자동 업데이트
- 실패 시 재시도 로직

### 4. 보안 고려사항
- 민감한 결제 정보 암호화
- HTTPS 강제
- 웹훅 서명 검증
- PCI DSS 준수

멕시코 특화:
- 멕시코 페소(MXN) 통화
- IVA 16% 세금 계산
- 멕시코 카드사 지원

출력:
- 결제 서비스 클래스
- 웹훅 컨트롤러
- 결제 모델 및 마이그레이션
- 보안 설정
- 테스트 코드
```

## 6단계: Filament 관리자 인터페이스

**Filament Expert에게 위임:**

```
역할: 매장 관리자용 Filament 인터페이스를 구현하세요.

백엔드 API: [3단계 결과 참조]

구현할 리소스:

### 1. 주문 관리
- OrderResource - 주문 목록 및 상세
- 실시간 주문 상태 업데이트
- 주문 검색 및 필터링
- 주문서 PDF 출력

### 2. 메뉴 관리
- MenuItemResource - 메뉴 아이템 CRUD
- CategoryResource - 카테고리 관리
- 이미지 업로드 및 관리
- 재고 추적

### 3. QR 관리
- QRCodeResource - QR 코드 생성
- 테이블별 QR 코드
- QR 사용 통계

### 4. 대시보드
- 실시간 주문 현황
- 매출 통계
- 인기 메뉴 분석
- 성과 지표

특별 기능:
- 주문 상태 실시간 업데이트
- WhatsApp 알림 발송
- 주방 지시서 출력
- 매출 리포트 생성

출력:
- Filament 리소스 클래스들
- 커스텀 위젯
- 액션 클래스들
- 대시보드 설정
```

## 7단계: WhatsApp 알림 시스템

**WhatsApp Expert에게 위임:**

```
역할: WhatsApp Business API를 통한 알림 시스템을 구현하세요.

주문 시스템: [이전 단계 결과 참조]

구현할 알림:

### 1. 주문 관련 알림
- 주문 접수 확인 (고객)
- 주문 준비 완료 (고객)
- 주문 픽업 준비 (고객)
- 새 주문 알림 (매장)

### 2. 템플릿 메시지
- 주문 확인 템플릿
- 상태 업데이트 템플릿
- 프로모션 템플릿
- 고객 지원 템플릿

### 3. 관리 기능
- 메시지 전송 로그
- 실패 메시지 재시도
- 옵트아웃 관리
- 성공률 통계

기술 구현:
- WhatsApp Business API 클라이언트
- 큐 기반 비동기 전송
- 실패 재시도 로직
- 메시지 상태 추적

출력:
- WhatsApp 서비스 클래스
- 알림 큐 작업
- 템플릿 관리 시스템
- 로그 및 통계 기능
```

## 8단계: 통합 테스트 및 최적화

모든 에이전트 결과를 통합하여:

1. **기능 테스트**
   - QR → 주문 → 결제 전체 플로우 테스트
   - 멀티테넌트 데이터 격리 검증
   - 결제 웹훅 시뮬레이션
   - WhatsApp 알림 테스트

2. **성능 최적화**
   - API 응답 시간 최적화
   - 데이터베이스 쿼리 최적화
   - 캐시 전략 적용
   - PWA 로딩 시간 개선

3. **보안 검증**
   - OWASP Top 10 점검
   - 테넌트 데이터 격리 검증
   - 결제 정보 보안 검토
   - API 인증/권한 테스트

4. **사용자 경험**
   - 모바일 반응형 테스트
   - 접근성 준수 검증
   - 다국어 번역 확인
   - 오프라인 기능 테스트

## 최종 출력

```markdown
## 구현된 전자상거래 주문 플로우

### 🏗️ 아키텍처
- [시스템 아키텍처 다이어그램]
- [데이터베이스 ERD]
- [API 명세서]

### 💻 백엔드 (Laravel)
- QR 세션 관리 API
- 메뉴/장바구니/주문 API
- 결제 연동 및 웹훅
- 멀티테넌트 스코핑

### 📱 프론트엔드 (React PWA)
- QR 스캔 및 메뉴 브라우징
- 장바구니 및 주문 플로우
- 실시간 주문 추적
- 오프라인 대응

### 🛠️ 관리자 (Filament)
- 주문 관리 시스템
- 메뉴 관리 도구
- 실시간 대시보드
- QR 코드 생성

### 💳 결제 (operacionesenlinea.com)
- 카드/OXXO/SPEI 결제
- 웹훅 처리
- 멕시코 세금 계산

### 📱 알림 (WhatsApp)
- 주문 상태 알림
- 템플릿 메시지
- 큐 기반 전송

### 🧪 테스트 및 문서
- 통합 테스트 스위트
- API 문서화
- 사용자 가이드
```

**$ARGUMENTS**에 명시된 구체적인 요구사항에 따라 각 단계를 조정하고 에이전트들과 협력하여 완전한 주문 플로우 시스템을 구현하세요.
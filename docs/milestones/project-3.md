# 프로젝트 3 — 결제/알림/환율/PoS (1차)

## 목적
- 온라인 결제 플로우 도입(결제 시점 로그인 필수)
- 주문 상태 변경 알림(웹/WhatsApp 1차)
- 환율(기본 API 연동) + 통화 선택 노출
- PoS 출력(초기: 키친 프린터 또는 미들웨어 API)

## 범위(MVF)
- 결제: 결제 의도 생성 → 게이트웨이 결제 → 웹훅 처리 → 주문 상태 업데이트
  - 게이트웨이: operacionesenlinea.com 사용 (멕시코 현지 우선)
- 알림: 주문 접수/상태 변경 → WhatsApp 템플릿 메시지 발송(기초)
- 환율: 스케줄러로 환율 동기화, 통화 선택 시 금액 변환 노출(메뉴/카트)
- PoS: 주문 생성 시 간단 영수증/주방지시서 출력 트리거

## 의존 라이브러리(추천)
- 결제 게이트웨이
  - operacionesenlinea.com 공식 PHP/REST 클라이언트(제공 여부에 따라 Guzzle 기반 커스텀 SDK 작성)
  - 결제 검증/서명: 서버-서명 비밀키 관리, 웹훅 시그니처 검증 필수
- 알림/WhatsApp
  - Meta WhatsApp Cloud API 또는 Twilio(차후 선택). 권장: Meta Cloud API (공식/비용 효율, 템플릿 승인 필수)
- 환율
  - openexchangerates/fixer API 클라이언트(간단한 자체 호출도 가능)
- 큐/스케줄러
  - laravel/horizon

## 데이터/API
- 스키마: `payments`, `exchange_rates`, `notifications`
- API 요약
  - POST `/api/v1/customer/payments/intent`
  - POST `/webhooks/payment`
  - POST `/webhooks/whatsapp`

### 결제 게이트웨이(operacionesenlinea.com) 통합 메모
- 결제 의도 생성 시 서버에서 금액/통화/주문ID/서명 생성 → 게이트웨이 세션/링크 발급
- 결제 완료/실패 웹훅: 서명 검증 후 `payments`/`orders` 상태 전환
- 테스트 환경/샌드박스 자격 증명 분리, 재현 시나리오 문서화

## 화면/기능(Step-by-step)
1) 고객 React
- 카트→주문 생성 후 결제 선택 → 로그인 요구 → 결제 창
- 결제 완료 시 주문 상태 실시간 반영(폴링 또는 푸시)

2) Admin
- 주문 상태판에서 상태 변경/보기
- 환율 설정 페이지(읽기 전용 스냅샷 + API 동기화 버튼)
 - 결제 설정 페이지(operacionesenlinea 자격증명/웹훅 URL 표기, 서명 키 회전)

## 산출물/검증 포인트
- 결제 성공 케이스 e2e 시연(테스트 카드)
- WhatsApp 메시지 샘플 전송 성공 로그
- 통화 전환에 따른 금액 갱신 스크린샷
- PoS 인쇄 성공(샘플 주문서)

## TODO(보강 문서)
- TODO: `docs/payments/gateway.md` — operacionesenlinea.com 설정/서명/보안/샌드박스
- TODO: `docs/notifications/whatsapp.md` — 템플릿/동의/재시도 정책
- TODO: `docs/fx/strategy.md` — 환율 API/수동/메뉴 커스텀 가격 우선순위
- TODO: `docs/pos/integration.md` — 인쇄/미들웨어/확인 응답 프로토콜

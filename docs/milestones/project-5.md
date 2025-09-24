# 프로젝트 5 — KDS/주방 모니터·테이블 세션/정산 고도화

## 목적
- KDS(주방 디스플레이) 및 프린트 워크플로우 고도화
- 테이블 주문 세션 병합/분할/부분정산, 팁/서비스차지 처리
- 테이블 현황 보드(실시간)

## 범위(MVF)
- KDS: 준비/서빙 상태 전환, 항목별 타이머, 우선순위 표시
- 정산: 세션 내 주문 묶음 일괄/부분 정산, 멕시코 팁/세금 규칙 기본 지원
- 실시간 현황: 테이블별 주문/세션 상태 보드, 새 주문 알림

## 의존 라이브러리(추천)
- 실시간
  - laravel-websockets/pusher 호환 또는 SSE
- 인쇄/PoS
  - escpos-php(프린터), 미들웨어 HTTP API
- 금액/세금
  - brick/money, moneyphp/money (정밀도/라운딩)

## 데이터/API
- 스키마 보강: `order_sessions`(merge/split 플래그, 정산 로그), `payments`(팁/서비스 분리)
- API: KDS 상태 변경, 세션 정산/분할, 현황 피드

## 산출물/검증 포인트
- KDS 화면에서 상태 전환/타이머 확인
- 세션 부분정산 시나리오(여러 주문 합산/분할) 시연
- 테이블 보드 실시간 갱신 확인

## TODO(보강 문서)
- TODO: `docs/kds/ui.md` — KDS UI/흐름/권한
- TODO: `docs/settlement/flows.md` — 병합/분할/팁/세금 처리
- TODO: `docs/realtime/infra.md` — WS/SSE 선택 및 스케일링

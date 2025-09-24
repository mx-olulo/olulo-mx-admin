# 프로젝트 6 — 예약/배달/픽업 확장 및 서비스콜 고도화

## 목적
- 예약/배달/픽업 주문 플로우 확장
- 서비스콜(웨이터 호출 등) 규칙/라우팅/우선순위 고도화

## 범위(MVF)
- 예약: 시간대/인원/테이블 선점, 노쇼/패널티 정책(기초)
- 배달/픽업: 주소/스케줄 입력, 상태 추적(배차 연동은 이후 단계)
- 서비스콜: 유형/우선순위/자동 라우팅(담당자), SLA 타이머

## 의존 라이브러리(추천)
- 달력/예약
  - FullCalendar(관리), date-fns
- 주소/지도(선택)
  - Google Maps/Mapbox SDK
- 알림
  - 큐/재시도 정책(Horizon), WhatsApp/웹 알림 재사용

## 데이터/API
- 스키마 보강: `orders`(reservation/delivery), `service_calls`(SLA, 우선순위, 라우팅 로그)
- API: 예약 생성/변경/취소, 픽업/배달 생성, 서비스콜 라우팅/완료

## 산출물/검증 포인트
- 예약 생성/변경/취소 e2e 시연
- 픽업/배달 주문 흐름 완료까지 시연
- 서비스콜 SLA 초과 알림/담당자 재할당 확인

## TODO(보강 문서)
- TODO: `docs/reservations/policy.md` — 예약 규칙/노쇼/패널티
- TODO: `docs/delivery/flow.md` — 배달 상태/연동 범위
- TODO: `docs/service-calls/routing.md` — 라우팅/우선순위/SLA

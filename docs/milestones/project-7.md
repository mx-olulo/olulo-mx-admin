# 프로젝트 7 — 고급 리포트/BI·Nova 마스터 고도화·청구

## 목적
- 고급 리포팅/BI, Nova 마스터 어드민 고도화, 멀티테넌트 청구/정산

## 범위(MVF)
- 리포트: 매출/메뉴/시간대/매장 비교 대시보드, CSV/엑셀 내보내기
- Nova: 마스터 정책/감사 로그/시스템 설정 관리 강화
- 청구: 테넌트별 과금(사용량/매장수/주문수), 인보이스 발행(기초)

## 의존 라이브러리(추천)
- 리포트/차트
  - maatwebsite/excel, laravel-nova charts, chart.js/apexcharts
- 감사/로그
  - spatie/laravel-activitylog
- 청구/인보이스
  - Laravel Cashier(참고) 또는 커스텀 인보이스(maatwebsite/excel)

## 데이터/API
- 스키마 보강: `invoices`, `invoice_items`, `activity_logs`
- API: 보고서 질의, 인보이스 생성/다운로드

## 산출물/검증 포인트
- 대시보드 지표/필터 동작 스크린샷
- 인보이스 생성/다운로드 시연
- Nova에서 정책/설정 변경 로그 확인

## TODO(보강 문서)
- TODO: `docs/reports/queries.md` — 핵심 지표/쿼리/인덱스
- TODO: `docs/billing/model.md` — 청구 단위/주기/요율 설계
- TODO: `docs/audit/policy.md` — 감사 로그/보존/내보내기

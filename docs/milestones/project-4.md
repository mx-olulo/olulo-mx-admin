# 프로젝트 4 — i18n/리뷰/포인트/AI/리포트 (2차)

## 목적
- 다국어(i18n) 전면 도입(ko/en/es-MX), 고객 전환 버튼 + 브라우저 감지
- 리뷰/후기(사진/영상/별점), 승인/공개 정책
- 포인트 적립/사용(그룹/매장 단위), 결제 시 우선 사용
- AI 이미지 생성/AI 번역(운영자 승인 플로우)
- 리포트/대시보드 고도화

## 범위(MVF)
- i18n: 메뉴/옵션 번역 캐시/저장, 고객 앱 전환 기능
- 리뷰: 고객 주문 기반 리뷰 생성, 관리자 승인/숨김
- 포인트: 계정/거래/정책, 결제 시 사용
- AI: 이미지/번역 초안 생성 후 승인 반영
- 리포트: 기본 판매/주문/메뉴 통계 위젯

## 의존 라이브러리(추천)
- 번역/i18n
  - react-i18next, laravel-lang/lang, symfony/translation
- 미디어/이미지
  - spatie/laravel-medialibrary, spatie/image
- 데이터 시각화
  - apexcharts 또는 chart.js (Filament/React 위젯)
- AI
  - OpenAI/Bedrock/Vertex(선택) — 서버 측 프록시를 통한 호출 권장

## 데이터/API
- 스키마: `menu_translations`, `option_translations`, `reviews`, `points_accounts`, `point_transactions`
- API 요약: 리뷰/포인트 CRUD, 번역 동기화 엔드포인트

## 산출물/검증 포인트
- 언어 전환에 따른 UI/콘텐츠 변경 스크린샷
- 리뷰 제출/승인 흐름 시연
- 포인트 적립/사용 후 총액 변화 검증
- AI 생성 자산 초안→승인 반영 사례

## TODO(보강 문서)
- TODO: `docs/i18n/guide.md` — 번역 저장/캐시/동적 생성 전략
- TODO: `docs/reviews/policy.md` — 리뷰 승인/신고/숨김 정책
- TODO: `docs/points/rules.md` — 적립/사용/만료 규칙
- TODO: `docs/ai/workflows.md` — 이미지/번역 승인 워크플로우
- TODO: `docs/reports/metrics.md` — 핵심 지표 정의

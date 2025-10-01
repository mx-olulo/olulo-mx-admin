# 데이터 모델 인덱스 (Models)

본 폴더는 데이터 모델 문서를 모아 관리합니다. 변경이 잦은 스키마/인덱스/관계 정의를 화이트페이퍼와 분리하여 유지보수성을 높입니다.

## 구성
- `core-tables.md` — 핵심 도메인 테이블(매장/테이블/좌석/메뉴/옵션/세트/주문/결제/세션/알림/리뷰/포인트 등)
- `pricing-fx.md` — 가격/환율 전략 테이블 상세(`menu_prices`, `exchange_rates`, 통화/라운딩 정책)
- `i18n.md` — 다국어 텍스트/번역 테이블
- `notifications.md` — 알림/WhatsApp/PoS 관련 테이블

### 테이블별 문서(`tables/`)
- `tables/store_group.md`
- `tables/stores.md`
- `tables/users.md`
- `tables/rbac.md`
- `tables/store_user.md`
- `tables/store_tables.md`
- `tables/store_table_seats.md`
- `tables/qr_tokens.md`
- `tables/menu_categories.md`
- `tables/menus.md`
- `tables/menu_translations.md`
- `tables/menu_prices.md`
- `tables/option_groups.md`
- `tables/option_items.md`
- `tables/option_translations.md`
- `tables/option_item_prices.md`
- `tables/set_menu_items.md`
- `tables/orders.md`
- `tables/order_sessions.md`
- `tables/order_items.md`
- `tables/order_item_options.md`
- `tables/payments.md`
- `tables/currencies.md`
- `tables/exchange_rates.md`
- `tables/locale_texts.md`
- `tables/notifications.md`
- `tables/service_calls.md`
- `tables/reviews.md`
- `tables/points_accounts.md`
- `tables/point_transactions.md`
- `tables/imports.md`

필요 시 문서를 세분화해 추가하세요.

## 원칙
- 변경은 반드시 PR 경유, 마이그레이션 영향도/롤백 전략을 함께 기술
- 인덱스/제약/파티셔닝 등 성능 고려를 표기하고 근거를 링크
- 컬럼/열거(enum)/상태 전이 규칙을 명확히 기재
- 파일명/섹션명은 영문 소문자-하이픈(`kebab-case`) 사용

## PostgreSQL 권장 옵션/가이드
- 타입
  - `uuid` 기본 키 또는 보조 식별자에 권장 (확장: `uuid-ossp` 또는 애플리케이션 생성)
  - `citext`로 대소문자 구분 없는 고유 제약(예: `users.email`, `stores.code`) 권장 (확장: `citext`)
  - `jsonb`를 구조적 JSON 저장에 사용 (`menus.attributes` 등)
  - 시각: `timestamptz` 표준화(UTC 저장)
- 인덱스/제약
  - 기본은 BTREE, `jsonb` 키/경로 검색은 GIN 고려
  - 대용량 시간 축 테이블은 `BRIN`(예: `exchange_rates.effective_at`) 검토
  - 복합 고유키는 비즈니스 규칙에 맞게 선언(예: `(menu_id, currency, effective_at)`)
- 파티셔닝/아카이빙
  - 장기적으로 대용량 테이블(`orders`, `payments`, `notifications`)은 범위 파티셔닝 고려
- UPSERT/동시성
  - 중복 가능성 있는 입력은 `INSERT ... ON CONFLICT ... DO UPDATE` 전략 문서화
- 확장(Extensions)
  - `uuid-ossp`, `pgcrypto`, `citext` 필요 시 활성화 (인프라/마이그레이션에서 관리)
- 마이그레이션 팁
  - 컬럼/인덱스 추가는 트랜잭션 내에서 단계적 적용, 롤백 경로 명시
  - 중단 없는 변경 위해 새로운 컬럼 도입→이중 쓰기→마이그레이션→스위치→정리 절차 권장

## 빠른 링크
- 화이트페이퍼: `../whitepaper.md`
- 인증: `../auth.md`
- 환경 설정: `../devops/environments.md`

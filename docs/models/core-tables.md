# 핵심 데이터 모델 (Core Tables)

본 문서는 Whitepaper의 "6. 데이터 모델(핵심 스키마)"에서 분리된 개요 문서입니다. 중복을 방지하기 위해 상세 정의는 테이블별 문서로 분리했고, 본 문서는 링크 인덱스만 제공합니다.

- DB: PostgreSQL 15+
- 공통 컬럼: `id(BIGINT PK)`, `created_at`, `updated_at`, `deleted_at(SoftDelete)`, 필요 시 `uuid`
- 인덱스/제약/파티셔닝: 각 테이블 문서에 근거를 병기

## 테이블별 문서
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

자세한 변경 절차/마이그레이션 가이드는 각 테이블 문서 하단을 참조하세요.

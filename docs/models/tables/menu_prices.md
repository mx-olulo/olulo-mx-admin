# menu_prices

- 목적: 메뉴 가격/환율 전략
- PK: `id (BIGINT)`

## 필드
- `menu_id` bigint FK
- `currency` string(ISO-4217)
- `strategy` enum(fx_api, fx_manual, custom)
- `base_price` decimal(12,2)
- `fx_rate` decimal(12,6) nullable
- `custom_price` decimal(12,2) nullable
- `effective_at` datetime
- `expires_at` datetime

## 인덱스/제약
- (menu_id, currency, effective_at)
- (strategy)

## 관계
- N:1 → menus

## 마이그레이션 가이드
- 유효기간 교차 검증, 통화별 단위/반올림 정책

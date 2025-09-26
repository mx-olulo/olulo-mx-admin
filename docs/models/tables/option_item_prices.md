# option_item_prices

- 목적: 옵션 항목의 통화별 가격 증감
- PK: `id (BIGINT)`

## 필드
- `option_item_id` bigint FK
- `currency` string
- `delta_price` decimal(12,2)

## 인덱스/제약
- (option_item_id, currency)

## 관계
- N:1 → option_items

# order_item_options

- 목적: 주문 품목의 옵션 라인
- PK: `id (BIGINT)`

## 필드
- `order_item_id` bigint FK
- `option_item_id` bigint FK nullable
- `name_snapshot` string
- `quantity` int
- `delta_price` decimal
- `line_total` decimal

## 인덱스/제약
- (order_item_id)

## 관계
- N:1 → order_items
- N:1 → option_items

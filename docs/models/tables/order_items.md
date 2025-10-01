# order_items

- 목적: 주문 품목 라인
- PK: `id (BIGINT)`

## 필드
- `order_id` bigint FK
- `menu_id` bigint FK
- `name_snapshot` string
- `quantity` int
- `unit_price` decimal
- `line_total` decimal
- `attributes` json

## 인덱스/제약
- (order_id)

## 관계
- N:1 → orders, menus
- 1:N → order_item_options

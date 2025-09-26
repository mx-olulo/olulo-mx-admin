# orders

- 목적: 주문 헤더
- PK: `id (BIGINT)`

## 필드
- `store_id` bigint FK
- `order_session_id` bigint FK nullable
- `order_type` enum(table, pickup, reservation, delivery)
- `status` enum(pending, accepted, preparing, served, completed, canceled, refunded)
- `customer_uid` string nullable(Firebase UID)
- `customer_name` string
- `customer_phone` string
- `currency` string
- `subtotal` decimal
- `tax` decimal
- `discount` decimal
- `service_charge` decimal
- `total` decimal
- `notes` text

## 인덱스/제약
- (store_id, status)
- (customer_uid)
- (order_session_id)

## 관계
- 1:N → order_items, payments
- N:1 → order_sessions

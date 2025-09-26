# service_calls

- 목적: 서비스콜(웨이터/물/물티슈 등)
- PK: `id (BIGINT)`

## 필드
- `store_id` bigint FK
- `table_id` bigint FK
- `seat_id` bigint FK nullable
- `call_type` enum(waiter, tissue, water, custom)
- `message` string
- `status` enum(open, ack, done)
- `created_by_customer_uid` string nullable

## 인덱스/제약
- (store_id, table_id, status)

## 관계
- N:1 → store, table, seat

# order_sessions

- 목적: 테이블 주문 세션 수명주기(Open→Settling→Closed)
- PK: `id (BIGINT)`

## 필드
- `store_id` bigint FK
- `table_id` bigint FK
- `seat_id` bigint FK nullable
- `opened_by_user_id` bigint FK nullable
- `opened_at` datetime
- `closed_at` datetime nullable
- `status` enum(open, settling, closed)

## 인덱스/제약
- (store_id, table_id, status)

## 관계
- 1:N → orders

# points_accounts

- 목적: 포인트 계정(그룹/매장/고객)
- PK: `id (BIGINT)`

## 필드
- `owner_type` enum(group, store, customer)
- `owner_id` bigint
- `balance` decimal
- `currency` string

## 인덱스/제약
- (owner_type, owner_id)

## 관계
- 1:N → point_transactions

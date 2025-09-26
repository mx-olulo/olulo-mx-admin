# point_transactions

- 목적: 포인트 트랜잭션
- PK: `id (BIGINT)`

## 필드
- `account_id` bigint FK
- `type` enum(earn, redeem, expire, adjust)
- `amount` decimal
- `reference_type` string
- `reference_id` bigint
- `metadata` json

## 인덱스/제약
- (account_id)
- (type)

## 관계
- N:1 → points_accounts

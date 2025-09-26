# payments

- 목적: 결제 기록
- PK: `id (BIGINT)`

## 필드
- `order_id` bigint FK
- `method` enum(card, cash, transfer, wallet)
- `provider` string
- `provider_txn_id` string
- `amount` decimal
- `currency` string
- `status` enum(pending, paid, failed, refunded)
- `paid_at` datetime nullable
- `metadata` json

## 인덱스/제약
- (order_id)
- (provider, provider_txn_id)

## 관계
- N:1 → orders

## 마이그레이션 가이드
- 결제 식별자 복합 unique 고려

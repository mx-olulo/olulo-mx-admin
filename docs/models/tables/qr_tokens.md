# qr_tokens

- 목적: QR 인증 토큰(매장/테이블/좌석 컨텍스트)
- PK: `id (BIGINT)`

## 필드
- `store_id` bigint FK
- `table_id` bigint FK nullable
- `seat_id` bigint FK nullable
- `token` string hash unique
- `expires_at` datetime
- `active` bool

## 인덱스/제약
- (token unique)
- (store_id, table_id, seat_id)

## 관계
- N:1 → store, table, seat

## 보안
- 토큰 길이/랜덤성 보장, 만료/비활성 처리

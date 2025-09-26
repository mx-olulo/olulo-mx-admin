# reviews

- 목적: 리뷰/평점/미디어
- PK: `id (BIGINT)`

## 필드
- `store_id` bigint FK
- `order_id` bigint FK nullable
- `customer_uid` string nullable
- `target_type` enum(menu, store)
- `target_id` bigint
- `rating` int(1-5)
- `comment` text
- `photos` json
- `videos` json
- `status` enum(pending, published, hidden)

## 인덱스/제약
- (store_id, target_type, target_id)
- (rating)

## 관계
- N:1 → store, order(optional)
- target: menus 또는 stores

# notifications

- 목적: 알림 기록(웹/WhatsApp/PoS)
- PK: `id (BIGINT)`

## 필드
- `store_id` bigint FK
- `type` enum(order_update, service_call, review, settlement)
- `channel` enum(web, whatsapp, pos)
- `to_user_id` bigint FK nullable
- `to_phone` string nullable
- `payload` json
- `status` enum(pending, sent, failed)
- `sent_at` datetime nullable

## 인덱스/제약
- (store_id, type, channel, status)

## 관계
- N:1 → store, user(선택)

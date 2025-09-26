# store_user

- 목적: 매장-사용자 소속 및 역할(테넌시 스코프)
- PK: `id (BIGINT)`

## 필드
- `store_id` bigint FK
- `user_id` bigint FK
- `role_id` bigint FK (RBAC)

## 인덱스/제약
- (store_id, user_id) composite unique
- (role_id)

## 관계
- N:1 → store, user, role

## 마이그레이션 가이드
- 매장 탈퇴/삭제 정책에 따른 ON DELETE

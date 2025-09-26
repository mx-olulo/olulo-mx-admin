# imports

- 목적: Excel 업로드 이력 및 리포트
- PK: `id (BIGINT)`

## 필드
- `store_id` bigint FK
- `type` enum(menu, price, option, table, user)
- `status` enum(pending, processing, completed, failed)
- `file_path` string
- `report` json

## 인덱스/제약
- (store_id)
- (type)

## 관계
- N:1 → store

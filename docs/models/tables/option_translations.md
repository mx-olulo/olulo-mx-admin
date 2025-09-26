# option_translations

- 목적: 옵션 다국어 텍스트
- PK: `id (BIGINT)`

## 필드
- `option_item_id` bigint FK
- `locale` string
- `name` string

## 인덱스/제약
- (option_item_id, locale)

## 관계
- N:1 → option_items

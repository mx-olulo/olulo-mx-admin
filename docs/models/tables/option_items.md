# option_items

- 목적: 옵션 항목(토핑 등)
- PK: `id (BIGINT)`

## 필드
- `option_group_id` bigint FK
- `code` string
- `position` int
- `status` enum

## 인덱스/제약
- (option_group_id)
- (code)

## 관계
- N:1 → option_groups
- 1:N → option_translations, option_item_prices

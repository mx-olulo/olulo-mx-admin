# option_groups

- 목적: 옵션 그룹 정의(최소/최대 선택, 수량 허용 등)
- PK: `id (BIGINT)`

## 필드
- `store_id` bigint FK
- `name_key` string
- `min_select` int
- `max_select` int
- `allow_quantity` bool
- `multi_select` bool
- `required` bool

## 인덱스/제약
- (store_id)

## 관계
- 1:N → option_items

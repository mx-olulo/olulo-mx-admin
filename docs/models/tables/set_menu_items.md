# set_menu_items

- 목적: 세트 메뉴 구성(자식 메뉴/옵션 그룹 매핑)
- PK: `id (BIGINT)`

## 필드
- `menu_id` bigint FK(set)
- `child_menu_id` bigint FK
- `option_group_id` bigint FK nullable
- `quantity_default` int
- `required` bool

## 인덱스/제약
- (menu_id)
- (child_menu_id)

## 관계
- N:1 → menus(parent), menus(child)
- N:1 → option_groups

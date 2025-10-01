# menu_categories

- 목적: 메뉴 카테고리 계층
- PK: `id (BIGINT)`

## 필드
- `store_id` bigint FK
- `parent_id` bigint FK nullable (self)
- `position` int
- `status` enum
- `is_virtual` bool

## 인덱스/제약
- (store_id)
- (parent_id)
- (is_virtual)

## 관계
- 1:N self children, 1:N menus

## 마이그레이션 가이드
- 계층 패턴(Nested Set/Adjacency) 고려

# menus

- 목적: 메뉴 마스터
- PK: `id (BIGINT)`

## 필드
- `store_id` bigint FK
- `category_id` bigint FK nullable
- `code` string
- `status` enum
- `is_set_menu` bool
- `image_url` string
- `attributes` json

## 인덱스/제약
- (store_id, code)
- (category_id)

## 관계
- 1:N → menu_translations, menu_prices
- 1:N(set) → set_menu_items

## 마이그레이션 가이드
- code 중복 방지, 이미지 스토리지 정책 명시

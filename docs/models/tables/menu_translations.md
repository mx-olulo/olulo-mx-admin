# menu_translations

- 목적: 메뉴 다국어 텍스트
- PK: `id (BIGINT)`

## 필드
- `menu_id` bigint FK
- `locale` string
- `name` string
- `description` text nullable
- `keywords` text nullable

## 인덱스/제약
- (menu_id, locale unique)

## 관계
- N:1 → menus

## 마이그레이션 가이드
- locale 표준 준수(BCP47), 중복 방지

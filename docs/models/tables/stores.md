# stores

- 목적: 매장 정보 및 국제화/통화/환경 설정
- PK: `id (BIGINT)`

## 필드
- `store_group_id` bigint nullable FK -> store_group.id
- `name` string
- `code` string unique
- `country_code` string
- `timezone` string
- `default_locale` string
- `supported_locales` json
- `supported_currencies` json
- `settings` json

## 인덱스/제약
- (store_group_id)
- (code unique)
- (country_code)

## 관계
- N:1 → `store_group`
- 1:N → tables, menus, orders, etc.

## 마이그레이션 가이드
- code는 변경 드뭄: unique+case-insensitive 인덱스 고려

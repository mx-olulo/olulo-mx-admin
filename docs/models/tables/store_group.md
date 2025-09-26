# store_group

- 목적: 매장 그룹(프랜차이즈 등) 메타 정보 및 기본 설정 보관
- PK: `id (BIGINT)`
- 공통: `created_at`, `updated_at`, `deleted_at?`

## 필드
- `name` string
- `country_code` string(ISO-3166-1 alpha-2)
- `timezone` string(IANA)
- `currency_default` string(ISO-4217)
- `settings` json

## 인덱스/제약
- (name)
- (country_code)

## 관계
- 1:N → `stores.store_group_id`

## 마이그레이션 가이드
- 그룹 삭제 제한: ON DELETE RESTRICT 권장
- 설정 스키마 변경 시 JSON Schema 버전 관리

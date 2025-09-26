# locale_texts (optional)

- 목적: 동적 번역/텍스트 캐시
- PK: `id (BIGINT)`

## 필드
- `store_id` bigint FK nullable
- `entity_type` string
- `entity_id` bigint
- `locale` string
- `key` string
- `text` text

## 인덱스/제약
- (store_id, entity_type, entity_id, locale)

## 관계
- 다양한 엔티티에 대한 텍스트 캐시(논리적)

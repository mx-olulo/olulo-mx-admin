# store_tables

- 목적: 매장의 테이블(좌석군) 정의
- PK: `id (BIGINT)`

## 필드
- `store_id` bigint FK
- `code` string unique per store
- `name` string
- `capacity` int
- `area` string nullable
- `status` enum(active,inactive)

## 인덱스/제약
- (store_id, code unique)

## 관계
- 1:N → store_table_seats

## 마이그레이션 가이드
- 코드 변경 최소화, QR과 연결 고려

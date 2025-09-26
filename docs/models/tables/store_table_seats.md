# store_table_seats

- 목적: 테이블 내 좌석 정의
- PK: `id (BIGINT)`

## 필드
- `table_id` bigint FK
- `seat_no` int
- `label` string

## 인덱스/제약
- (table_id, seat_no)

## 관계
- N:1 → store_tables

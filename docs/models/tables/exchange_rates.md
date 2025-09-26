# exchange_rates

- 목적: 환율 스냅샷 저장
- PK: `id (BIGINT)`

## 필드
- `base_currency` string
- `quote_currency` string
- `rate` decimal(18,8)
- `source` enum(api, manual)
- `effective_at` datetime

## 인덱스/제약
- (base_currency, quote_currency, effective_at desc)

## 관계
- N:1 → currencies (논리적)

## 마이그레이션 가이드
- 시간 축 인덱스 최적화, 최신 N 조회

# currencies

- 목적: 통화 마스터
- PK: `code (PK)`

## 필드
- `symbol` string
- `name` string

## 관계
- 1:N → menu_prices, option_item_prices, payments

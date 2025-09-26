# users

- 목적: 플랫폼 사용자(매장/관리자/고객 계정 일부 식별)
- PK: `id (BIGINT)`

## 필드
- `firebase_uid` string unique
- `email` string nullable
- `phone` string nullable
- `name` string
- `last_login_at` datetime nullable

## 인덱스/제약
- (firebase_uid unique)
- (email)
- (phone)

## 관계
- store_user로 매장 소속 매핑

## 마이그레이션 가이드
- 이메일/전화는 중복 허용(다중 계정 시나리오 고려)

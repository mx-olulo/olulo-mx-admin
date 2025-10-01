# 환경변수 설정 TODO

## 현재 상태
- ✅ Firebase 설정 완료 (2025-09-27)
- ✅ 기본 Laravel/멀티테넌시 설정 완료

## 🔒 필수 설정 필요 (보안/결제)

### WhatsApp Business API
```env
WHATSAPP_TOKEN=your_whatsapp_business_token
WHATSAPP_PHONE_NUMBER_ID=your_phone_number_id
WHATSAPP_VERIFY_TOKEN=your_verify_token
```
**우선순위**: 높음
**담당**: 멕시코 현지 WhatsApp Business 계정 설정 필요

### 결제 게이트웨이 (operacionesenlinea.com)
```env
PAYMENT_GATEWAY_URL=https://api.operacionesenlinea.com
PAYMENT_MERCHANT_ID=your_merchant_id
PAYMENT_API_KEY=your_payment_api_key
```
**우선순위**: 높음
**담당**: 멕시코 결제 업체와 계약 후 설정

## ☁️ 선택적 설정 (운영 최적화)

### AWS S3 (파일 저장소)
```env
AWS_ACCESS_KEY_ID=your_aws_access_key
AWS_SECRET_ACCESS_KEY=your_aws_secret_key
AWS_BUCKET=olulo-mx-storage
```
**우선순위**: 중간
**용도**: 메뉴 이미지, 영수증 파일 저장

### 메일 설정
```env
MAIL_FROM_ADDRESS=noreply@olulo.com.mx
```
**우선순위**: 중간
**현재값**: hello@example.com (변경 필요)

## 📋 설정 체크리스트

### Phase2 (현재 - 인증 기반) ✅
- [x] Firebase 프로젝트 설정
- [x] Firebase 서비스 계정 키
- [x] Firebase Web API 키
- [x] Sanctum 도메인 설정

### Phase3 (인증 구현)
- [ ] WhatsApp Business API 설정
- [ ] 결제 게이트웨이 테스트 계정

### Phase4 (운영 준비)
- [ ] AWS S3 설정
- [ ] 프로덕션 메일 설정
- [ ] 모니터링/로깅 설정

## 🔐 보안 가이드

### 민감 정보 관리
1. `.env` 파일은 절대 커밋하지 않음
2. 프로덕션 키는 별도 보안 저장소 관리
3. 개발/스테이징/프로덕션 환경 분리

### Firebase 보안
- 서비스 계정 키 권한 최소화
- 웹 API 키 도메인 제한 설정
- 실시간 데이터베이스 규칙 적용

## 📝 설정 이력

| 날짜 | 항목 | 상태 | 비고 |
|------|------|------|------|
| 2025-09-27 | Firebase 완전 설정 | ✅ | 서버/클라이언트 모두 |
| 2025-09-27 | Sanctum 도메인 설정 | ✅ | 서브도메인 지원 |
| - | WhatsApp API | ❌ | 멕시코 비즈니스 계정 필요 |
| - | 결제 게이트웨이 | ❌ | 업체 계약 후 설정 |

## 다음 액션

1. **즉시**: 로컬 개발 환경 테스트
2. **1주일 내**: WhatsApp Business 계정 신청
3. **2주일 내**: 결제 업체 계약 및 테스트 계정
4. **3주일 내**: AWS 설정 및 파일 저장소 구성
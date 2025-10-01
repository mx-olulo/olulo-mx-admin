# 멕시코 현지화 작업 워크플로우

멕시코 전자상거래 플랫폼을 위한 완전한 현지화 시스템을 구현하는 워크플로우입니다.

## 워크플로우 개요

당신은 **멕시코 현지화 전문 오케스트레이터**입니다. 다음 전문 에이전트들과 협력하여 CURP/RFC 검증, 세금 처리, 현지 규정 준수, 문화적 적응을 포함한 완전한 현지화 시스템을 구현합니다:

1. **Legal Advisor** - 멕시코 법적 요구사항 분석
2. **Database Expert** - 현지화 데이터 스키마 설계
3. **Laravel Expert** - 백엔드 현지화 서비스 구현
4. **React Expert** - 프론트엔드 다국어 및 현지화 UI
5. **Payment Integration** - 멕시코 결제 및 세금 시스템
6. **Security Auditor** - 개인정보보호법 준수
7. **API Documenter** - 현지화 API 문서화

## 실행 인수

사용자 요청: `$ARGUMENTS`

## 1단계: 멕시코 법적 요구사항 분석

**Legal Advisor에게 위임:**

```
역할: 멕시코 전자상거래 플랫폼의 법적 요구사항을 분석하고 컴플라이언스 가이드를 제공하세요.

분석 영역:

### 1. 개인정보보호법 (LFPDPPP)
- 개인정보 수집 동의 절차
- 데이터 처리 목적 명시
- 정보주체 권리 (열람, 정정, 취소, 반대)
- 개인정보보호 고지

### 2. 전자상거래 관련 법규
- NOM-151-SCFI-2016 (전자상거래 표준)
- 소비자보호법 (PROFECO)
- 전자송장 시스템 (CFDI)

### 3. 세무 관련 규정
- SAT (세무청) 규정
- IVA (부가가치세) 16%
- RFC 세무식별번호 요구사항
- 전자영수증 발급 의무

### 4. 결제 및 금융 규정
- CNBV (은행증권위원회) 규정
- PCI DSS 준수
- 자금세탁방지법 (AML)

### 5. 노동법 (매장 운영 관련)
- 근로시간 및 휴가 규정
- 최저임금 및 급여 관련
- 산업안전보건법

출력:
- 법적 요구사항 체크리스트
- 컴플라이언스 구현 가이드
- 필수 문서 템플릿 (동의서, 고지서 등)
- 위험도 평가 및 완화 방안
- 정기 검토 일정
```

## 2단계: 현지화 데이터베이스 스키마 설계

**Database Expert에게 위임:**

```
역할: 멕시코 현지화 요구사항을 지원하는 데이터베이스 스키마를 설계하세요.

법적 요구사항: [1단계 결과 참조]

설계할 테이블:

### 1. 개인정보 및 식별
- mexico_personal_info - CURP/RFC 정보
- consent_records - 개인정보 동의 기록
- data_subject_requests - 정보주체 권리 요청

### 2. 세무 및 회계
- tax_calculations - 세금 계산 내역
- cfdi_invoices - 전자송장 (CFDI)
- sat_catalogs - SAT 카탈로그 데이터

### 3. 지역 정보
- mexico_states - 멕시코 주 정보
- mexico_municipalities - 시/군 정보
- postal_codes - 우편번호
- bank_codes - 은행 코드 (CLABE)

### 4. 법정 요구사항
- legal_documents - 법적 문서 (T&C, Privacy Policy)
- compliance_logs - 컴플라이언스 로그
- audit_trails - 감사 추적

### 5. 현지화 설정
- localization_settings - 테넌트별 현지화 설정
- currency_rates - 환율 정보
- business_hours - 영업시간 (멕시코 휴일 고려)

스키마 특징:
- GDPR 스타일 개인정보 관리
- 감사 추적 (audit trail) 모든 테이블
- 암호화 필드 (민감 정보)
- 테넌트별 현지화 설정

출력:
- Laravel 마이그레이션 파일들
- 개인정보 암호화 전략
- 감사 로그 시스템
- 데이터 보존 정책
- 인덱스 최적화 방안
```

## 3단계: Laravel 현지화 서비스 구현

**Laravel Expert에게 위임:**

```
역할: 멕시코 현지화를 위한 Laravel 백엔드 서비스를 구현하세요.

데이터베이스 스키마: [2단계 결과 참조]
법적 요구사항: [1단계 결과 참조]

구현할 서비스:

### 1. 신원 검증 서비스
```php
// app/Services/Mexico/IdentityValidator.php
class IdentityValidator
{
    public function validateCURP(string $curp): ValidationResult;
    public function validateRFC(string $rfc): ValidationResult;
    public function validateCLABE(string $clabe): ValidationResult;
    public function extractPersonalInfo(string $curp): PersonalInfo;
}
```

### 2. 세금 계산 서비스
```php
// app/Services/Mexico/TaxCalculator.php
class TaxCalculator
{
    public function calculateIVA(float $amount): float;
    public function calculateTotalWithTax(float $subtotal): float;
    public function generateTaxBreakdown(array $items): array;
    public function isIVAExempt(string $productCode): bool;
}
```

### 3. 전자송장 (CFDI) 서비스
```php
// app/Services/Mexico/CFDIService.php
class CFDIService
{
    public function generateCFDI(Order $order): CFDI;
    public function submitToSAT(CFDI $cfdi): SATResponse;
    public function cancelCFDI(string $uuid): SATResponse;
    public function validateCFDI(string $xml): ValidationResult;
}
```

### 4. 개인정보보호 서비스
```php
// app/Services/Mexico/PrivacyService.php
class PrivacyService
{
    public function recordConsent(User $user, string $type): ConsentRecord;
    public function processDataSubjectRequest(DataSubjectRequest $request): void;
    public function anonymizeUserData(User $user): void;
    public function generatePrivacyReport(): PrivacyReport;
}
```

### 5. 지역화 서비스
```php
// app/Services/Mexico/LocalizationService.php
class LocalizationService
{
    public function formatCurrency(float $amount): string;
    public function formatDate(DateTime $date): string;
    public function formatAddress(Address $address): string;
    public function isBusinessHour(Tenant $tenant): bool;
    public function getStateInfo(string $stateCode): StateInfo;
}
```

### 6. API 엔드포인트
- POST /api/mexico/validate-curp - CURP 검증
- POST /api/mexico/validate-rfc - RFC 검증
- GET /api/mexico/states - 주 목록
- GET /api/mexico/municipalities/{state} - 시/군 목록
- POST /api/mexico/calculate-tax - 세금 계산
- POST /api/privacy/consent - 개인정보 동의
- POST /api/privacy/request - 정보주체 권리 요청

구현 요구사항:
- 모든 민감 정보 암호화
- 감사 로그 자동 기록
- 테넌트별 설정 지원
- 에러 처리 및 복구
- 성능 최적화 (캐싱)

출력:
- 서비스 클래스들
- API 컨트롤러들
- 검증 규칙들
- 미들웨어 (개인정보보호)
- 큐 작업들 (CFDI 처리 등)
```

## 4단계: React 다국어 및 현지화 UI

**React Expert에게 위임:**

```
역할: React PWA에서 멕시코 현지화 UI와 다국어 시스템을 구현하세요.

백엔드 API: [3단계 결과 참조]

구현할 컴포넌트:

### 1. 다국어 시스템
```typescript
// 지원 언어: ko (한국어), en (영어), es-MX (멕시코 스페인어)
// React-i18next 설정
const i18nConfig = {
  fallbackLng: 'es-MX',
  supportedLngs: ['ko', 'en', 'es-MX'],
  defaultNS: 'common',
  namespaces: ['common', 'forms', 'errors', 'legal']
};
```

### 2. 멕시코 특화 폼 컴포넌트
- CURPInput - CURP 입력 및 검증
- RFCInput - RFC 입력 및 검증
- MexicanAddressForm - 멕시코 주소 입력
- PhoneNumberInput - 멕시코 전화번호 형식
- CLABEInput - 은행계좌번호 (CLABE) 입력

### 3. 개인정보보호 컴포넌트
- ConsentModal - 개인정보 동의
- PrivacyNotice - 개인정보보호 고지
- DataSubjectRights - 정보주체 권리 설명
- ConsentWithdrawal - 동의 철회

### 4. 세금 및 가격 표시
- PriceDisplay - 멕시코 페소 표시
- TaxBreakdown - 세금 내역 표시
- InvoicePreview - 전자송장 미리보기
- CFDIDownload - CFDI 다운로드

### 5. 지역화 유틸리티
- DateFormatter - 멕시코 날짜 형식
- AddressFormatter - 멕시코 주소 형식
- BusinessHourIndicator - 영업시간 표시
- HolidayCalendar - 멕시코 휴일 달력

### 6. 언어 전환
- LanguageSwitcher - 언어 선택 드롭다운
- RTL 지원 준비 (미래 아랍어 지원)
- 폰트 로딩 최적화 (Google Fonts)

기술 구현:
- React-i18next 통합
- 날짜/숫자 현지화 (Intl API)
- 동적 번역 로딩
- 번역 캐시 전략
- 접근성 (스크린 리더 지원)

출력:
- 다국어 컴포넌트들
- 번역 파일들 (ko.json, en.json, es-MX.json)
- 현지화 유틸리티 함수들
- 타입 정의 (i18n)
- 스토리북 스토리들
```

## 5단계: 멕시코 결제 및 세금 시스템

**Payment Integration Expert에게 위임:**

```
역할: 멕시코 결제 시스템과 세금 처리를 구현하세요.

현지화 서비스: [3단계 결과 참조]

구현할 기능:

### 1. 멕시코 결제 방식
- **카드 결제**: Visa, MasterCard, American Express
- **OXXO 결제**: 편의점 현금 결제
- **SPEI 결제**: 실시간 은행이체
- **Mercado Pago**: 라틴아메리카 결제 플랫폼

### 2. 세금 계산 엔진
```php
// IVA (부가가치세) 16% 표준
// 특정 품목 면세 또는 0% (예: 기본 식품)
// 지역별 추가 세금 고려

class MexicoTaxEngine
{
    public function calculateOrderTax(Order $order): TaxCalculation;
    public function applyIVA(float $amount, string $productType): float;
    public function getIVARate(string $productCode): float;
    public function generateTaxInvoice(Order $order): CFDIInvoice;
}
```

### 3. CFDI (전자송장) 통합
- SAT API 연동
- XML 서명 및 타임스탬프
- UUID 생성 및 관리
- 취소/환불 처리

### 4. 통화 및 환율
- 멕시코 페소 (MXN) 기본 통화
- Banco de México API 환율 연동
- 실시간 환율 업데이트
- 환율 히스토리 관리

### 5. 은행 통합
- CLABE 계좌번호 검증
- SPEI 이체 시스템
- 은행 수수료 계산
- 거래 확인 및 추적

출력:
- 멕시코 결제 게이트웨이 클라이언트
- 세금 계산 서비스
- CFDI 생성/관리 시스템
- 환율 관리 시스템
- 은행 연동 서비스
```

## 6단계: 개인정보보호법 준수 시스템

**Security Auditor에게 위임:**

```
역할: 멕시코 개인정보보호법(LFPDPPP) 준수 시스템을 구현하세요.

법적 요구사항: [1단계 결과 참조]
현지화 서비스: [3단계 결과 참조]

구현할 보안 기능:

### 1. 개인정보 수집 동의
- 명시적 동의 수집
- 목적별 동의 분리
- 동의 철회 기능
- 동의 기록 보관

### 2. 정보주체 권리 구현
- **열람권**: 개인정보 처리 현황 제공
- **정정권**: 잘못된 정보 수정
- **취소권**: 개인정보 삭제
- **반대권**: 처리 거부

### 3. 데이터 보호 조치
- 개인정보 암호화 (AES-256)
- 접근 권한 관리
- 로그 모니터링
- 침해 대응 절차

### 4. 개인정보보호 고지
- 개인정보보호정책 자동 생성
- 처리 목적 및 법적 근거
- 보유 기간 및 파기 절차
- 제3자 제공 현황

### 5. 감사 및 모니터링
- 개인정보 처리 로그
- 접근 기록 추적
- 정기 보안 점검
- 컴플라이언스 리포트

출력:
- 개인정보보호 미들웨어
- 동의 관리 시스템
- 정보주체 권리 처리 시스템
- 감사 로그 시스템
- 보안 모니터링 도구
```

## 7단계: API 문서화 및 통합

**API Documenter에게 위임:**

```
역할: 멕시코 현지화 API의 완전한 문서화를 제공하세요.

구현된 API: [이전 단계 결과 참조]

문서화할 API:

### 1. 신원 검증 API
- CURP 검증 엔드포인트
- RFC 검증 엔드포인트
- CLABE 검증 엔드포인트
- 개인정보 추출 API

### 2. 지역화 API
- 주/시 정보 API
- 우편번호 조회 API
- 은행 코드 API
- 휴일/영업시간 API

### 3. 세금 계산 API
- IVA 계산 API
- 세금 내역 API
- CFDI 생성 API
- 전자송장 관리 API

### 4. 개인정보보호 API
- 동의 기록 API
- 정보주체 권리 API
- 개인정보 삭제 API
- 컴플라이언스 API

문서 형식:
- OpenAPI 3.0 스펙
- Postman 컬렉션
- 코드 예제 (PHP, JavaScript)
- 에러 코드 가이드
- 인증 가이드

출력:
- OpenAPI 스펙 파일
- Postman 컬렉션
- API 문서 웹사이트
- SDK/클라이언트 라이브러리
- 개발자 가이드
```

## 8단계: 통합 테스트 및 컴플라이언스 검증

최종 통합 및 검증:

1. **법적 컴플라이언스 테스트**
   - LFPDPPP 준수 검증
   - SAT 규정 준수 확인
   - PROFECO 요구사항 점검
   - PCI DSS 보안 검증

2. **기능 테스트**
   - CURP/RFC 검증 정확도
   - 세금 계산 정확성
   - CFDI 생성/처리
   - 개인정보 권리 행사

3. **사용자 경험 테스트**
   - 다국어 번역 품질
   - 문화적 적절성
   - 접근성 준수
   - 모바일 사용성

4. **성능 및 보안**
   - 개인정보 암호화 검증
   - API 성능 테스트
   - 감사 로그 정확성
   - 침해 대응 절차

## 최종 출력

```markdown
## 구현된 멕시코 현지화 시스템

### 🏛️ 법적 컴플라이언스
- ✅ LFPDPPP (개인정보보호법) 준수
- ✅ SAT 전자송장 시스템 연동
- ✅ PROFECO 소비자보호 준수
- ✅ PCI DSS 결제 보안 인증

### 🛡️ 개인정보보호
- 명시적 동의 수집 시스템
- 정보주체 권리 행사 포털
- 개인정보 암호화 및 보호
- 감사 추적 및 모니터링

### 💳 멕시코 결제 시스템
- 카드/OXXO/SPEI 결제 지원
- IVA 16% 자동 계산
- CFDI 전자송장 자동 발급
- 멕시코 페소 현지화

### 🌍 다국어 및 현지화
- 한국어/영어/멕시코 스페인어
- 멕시코 문화 적응 UI
- 현지 날짜/주소 형식
- 접근성 및 사용성 최적화

### 🔍 신원 검증
- CURP 개인식별번호 검증
- RFC 세무식별번호 검증
- CLABE 은행계좌번호 검증
- 실시간 검증 및 파싱

### 📊 관리 및 모니터링
- 컴플라이언스 대시보드
- 개인정보 처리 현황
- 세금 계산 내역
- 법적 문서 관리

### 📖 문서화
- 완전한 API 문서
- 개발자 가이드
- 컴플라이언스 체크리스트
- 운영 매뉴얼
```

**$ARGUMENTS**에 명시된 구체적인 현지화 요구사항에 따라 각 단계를 조정하고 에이전트들과 협력하여 완전한 멕시코 현지화 시스템을 구현하세요.
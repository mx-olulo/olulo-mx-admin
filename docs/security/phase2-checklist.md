# Phase 2 보안 체크리스트 ✅ READ

작성일: 2025-10-01
작성자: Documentation Reviewer (Claude Agent)
상태: Phase 2 완료 전 필수 검증

## 목적

Phase 2 인증 시스템 구현 완료 후 프로덕션 배포 전 필수적으로 검증해야 할 보안 항목을 정의합니다.

## 1. Firebase Admin SDK 보안 검증

### 1.1 서비스 계정 키 관리 ⚠️ 크리티컬

- [ ] **환경변수 저장**: Firebase 서비스 계정 키가 `.env` 파일에만 존재하고 Git에 커밋되지 않았는지 확인
- [ ] **키 권한 검토**: Firebase Console에서 서비스 계정의 IAM 권한이 최소 권한 원칙을 따르는지 확인
  - 필수 권한: `Firebase Authentication Admin`, `Firebase Realtime Database Admin`, `Cloud Messaging Admin`
  - 불필요한 권한(Owner, Editor 등) 제거
- [ ] **키 로테이션 계획**: 서비스 계정 키의 로테이션 주기(권장: 90일) 및 절차 문서화
- [ ] **키 암호화**: 프로덕션 환경에서는 AWS Secrets Manager, HashiCorp Vault 등을 사용한 키 암호화 고려

### 1.2 Firebase 프로젝트 설정

- [ ] **프로젝트 ID 검증**: 환경별로 올바른 Firebase 프로젝트 ID 사용 확인
  - 개발: `olulo-mx-admin-dev` (또는 개발 전용 프로젝트)
  - 스테이징: `olulo-mx-admin-staging`
  - 프로덕션: `olulo-mx-admin`
- [ ] **인증 제공업체 설정**: Firebase Console에서 활성화된 인증 제공업체 확인
  - Google, Email/Password, Phone 등
- [ ] **승인된 도메인 설정**: Firebase Console의 "Authentication → Settings → Authorized domains"에 환경별 도메인 추가
  - 개발: `admin.dev.olulo.com.mx`, `menu.dev.olulo.com.mx`, `localhost`
  - 스테이징: `admin.demo.olulo.com.mx`, `menu.demo.olulo.com.mx`
  - 프로덕션: `admin.olulo.com.mx`, `menu.olulo.com.mx`

### 1.3 토큰 검증 설정

- [ ] **토큰 만료 검증**: `FIREBASE_CHECK_REVOKED=true` 설정으로 폐기된 토큰 검증 활성화
- [ ] **세션 수명 설정**: `FIREBASE_SESSION_LIFETIME` 적절한 값 설정 (권장: 432000초 = 5일)
- [ ] **에뮬레이터 비활성화**: 프로덕션 환경에서 `FIREBASE_USE_EMULATOR=false` 확인

## 2. Sanctum SPA 세션 보안

### 2.1 세션 설정 검증

- [ ] **세션 드라이버**: `SESSION_DRIVER=redis` 설정으로 확장 가능한 세션 저장소 사용
- [ ] **세션 도메인**: 환경별 올바른 상위 도메인 설정
  - 개발: `SESSION_DOMAIN=.dev.olulo.com.mx`
  - 스테이징: `SESSION_DOMAIN=.demo.olulo.com.mx`
  - 프로덕션: `SESSION_DOMAIN=.olulo.com.mx`
- [ ] **세션 수명**: `SESSION_LIFETIME=120` (분) 적절한 값으로 설정
- [ ] **세션 암호화**: `SESSION_ENCRYPT=false` 유지 (쿠키 암호화는 Laravel이 자동 처리)

### 2.2 Stateful Domains 설정

- [ ] **SANCTUM_STATEFUL_DOMAINS**: 환경별 모든 프론트엔드 도메인 포함
  - 개발: `localhost,admin.dev.olulo.com.mx,menu.dev.olulo.com.mx,mx-olulo.firebaseapp.com,mx-olulo.web.app`
  - 스테이징: `admin.demo.olulo.com.mx,menu.demo.olulo.com.mx,mx-olulo.firebaseapp.com,mx-olulo.web.app`
  - 프로덕션: `admin.olulo.com.mx,menu.olulo.com.mx,mx-olulo.firebaseapp.com,mx-olulo.web.app`
- [ ] **포트 번호 제거**: 프로덕션에서는 포트 번호(`:3000`) 제외

### 2.3 쿠키 보안 설정

- [ ] **Secure 쿠키**: `config/session.php`에서 `'secure' => env('SESSION_SECURE_COOKIE', true)` 설정
- [ ] **HttpOnly 쿠키**: 기본값 `true` 유지 (XSS 공격 방지)
- [ ] **SameSite 설정**: `'same_site' => 'lax'` 설정으로 CSRF 공격 방지

## 3. CORS/CSRF 보안 정책

### 3.1 CORS 설정 검증

- [ ] **허용 오리진**: `config/cors.php`에서 환경별 정확한 도메인 설정
  - 와일드카드(`*`) 사용 금지
  - HTTPS 프로토콜 강제 (프로덕션)
- [ ] **자격증명 허용**: `'supports_credentials' => true` 설정 (Sanctum SPA 필수)
- [ ] **허용 헤더**: 다음 헤더 포함 확인
  - `Content-Type`, `X-Requested-With`, `X-XSRF-TOKEN`, `Authorization`, `Accept`, `Accept-Language`, `X-Firebase-Token`
- [ ] **노출 헤더**: `X-CSRF-TOKEN` 헤더 노출 설정
- [ ] **Preflight 캐시**: `'max_age' => 3600` 설정으로 OPTIONS 요청 캐시

### 3.2 CSRF 보호

- [ ] **CSRF 토큰 검증**: `X-XSRF-TOKEN` 헤더를 통한 토큰 검증 활성화
- [ ] **CSRF 쿠키 엔드포인트**: `/sanctum/csrf-cookie` 엔드포인트 정상 동작 확인
- [ ] **CSRF 예외 최소화**: `bootstrap/app.php`에서 CSRF 예외 처리 최소화
  - 개발 환경에서만 `/auth/firebase/callback` 예외 허용
  - 프로덕션에서는 예외 제거 권장

## 4. 환경변수 관리

### 4.1 민감 정보 보호

- [ ] **`.env` 파일 보호**: `.gitignore`에 `.env` 포함 확인
- [ ] **`.env.example` 업데이트**: 모든 필수 환경변수가 예시 파일에 포함되어 있지만 실제 값은 제외
- [ ] **프로덕션 환경변수**: 프로덕션 서버에서 환경변수가 파일이 아닌 환경 변수로 주입되는지 확인
- [ ] **설정 캐시**: 프로덕션에서 `php artisan config:cache` 실행 후 `env()` 함수 대신 `config()` 함수 사용 확인

### 4.2 환경변수 검증

- [ ] **APP_KEY**: Laravel 애플리케이션 키가 설정되었는지 확인 (`php artisan key:generate`)
- [ ] **APP_ENV**: 환경별 올바른 값 설정 (`local`, `staging`, `production`)
- [ ] **APP_DEBUG**: 프로덕션에서 `false` 설정
- [ ] **APP_URL**: 환경별 올바른 URL 설정

## 5. 토큰 검증 및 세션 관리

### 5.1 Firebase ID 토큰 검증

- [ ] **서버 시간 동기화**: Firebase 토큰 만료 검증을 위해 서버 시간이 NTP와 동기화되어 있는지 확인
- [ ] **토큰 만료 처리**: 프론트엔드에서 만료된 토큰 재발급 로직 구현
- [ ] **폐기된 토큰 검증**: `FIREBASE_CHECK_REVOKED=true` 설정으로 폐기된 토큰 차단

### 5.2 세션 하이재킹 방지

- [ ] **세션 재생성**: 로그인 성공 시 `$request->session()->regenerate()` 호출 확인
- [ ] **세션 무효화**: 로그아웃 시 `$request->session()->invalidate()` 호출 확인
- [ ] **CSRF 토큰 재생성**: 로그아웃 시 `$request->session()->regenerateToken()` 호출 확인

### 5.3 토큰 블랙리스트 (Phase 2.10 - 선택)

- [ ] Redis 기반 토큰 블랙리스트 구현 고려
- [ ] 로그아웃 시 Firebase UID + 세션 ID를 블랙리스트에 추가
- [ ] 미들웨어에서 블랙리스트 검증

## 6. Rate Limiting 권장사항

### 6.1 인증 엔드포인트 Rate Limiting

- [ ] **로그인 엔드포인트**: `/api/auth/firebase-login`에 Rate Limiting 적용 (권장: 5회/분)
- [ ] **CSRF 쿠키 엔드포인트**: `/sanctum/csrf-cookie`에 Rate Limiting 적용 (권장: 10회/분)
- [ ] **로그아웃 엔드포인트**: `/api/auth/logout`에 Rate Limiting 적용 (권장: 5회/분)

### 6.2 Rate Limiting 구현 방법

- [ ] Laravel의 내장 Rate Limiting 사용: `RateLimiter` 파사드
- [ ] `bootstrap/app.php`에서 API Rate Limiting 설정
- [ ] Redis 기반 Rate Limiting 활용

예시:

```php
use Illuminate\Support\Facades\RateLimiter;

RateLimiter::for('auth', function (Request $request) {
    return Limit::perMinute(5)->by($request->ip());
});
```

미들웨어 적용:

```php
Route::post('/firebase-login', [AuthController::class, 'apiFirebaseLogin'])
    ->middleware('throttle:auth');
```

## 7. 프로덕션 배포 전 체크리스트

### 7.1 필수 사항

- [ ] **HTTPS 강제**: 모든 도메인에서 HTTPS 사용, HTTP는 HTTPS로 리다이렉트
- [ ] **SSL 인증서 검증**: 유효한 SSL 인증서 설치 및 만료일 확인
- [ ] **보안 헤더 설정**: 다음 헤더 추가 고려
  - `X-Frame-Options: SAMEORIGIN`
  - `X-Content-Type-Options: nosniff`
  - `Referrer-Policy: strict-origin-when-cross-origin`
  - `Permissions-Policy: geolocation=(self)`
- [ ] **로그 보안**: 민감한 정보(토큰, 비밀번호)를 로그에 기록하지 않도록 설정
- [ ] **에러 메시지**: 프로덕션에서 상세한 에러 메시지 노출 금지 (`APP_DEBUG=false`)

### 7.2 테스트

- [ ] **통합 테스트**: 인증 플로우 전체 테스트 (로그인 → 보호된 엔드포인트 접근 → 로그아웃)
- [ ] **CORS 테스트**: 프론트엔드에서 CORS 정책이 올바르게 적용되는지 테스트
- [ ] **CSRF 테스트**: CSRF 토큰 없이 POST 요청 시 403 에러 발생 확인
- [ ] **세션 테스트**: 서로 다른 서브도메인 간 세션 공유 테스트
- [ ] **토큰 만료 테스트**: 만료된 Firebase 토큰으로 로그인 시도 시 에러 발생 확인

### 7.3 모니터링

- [ ] **로그 모니터링**: 인증 실패, 토큰 검증 실패 로그 모니터링 설정
- [ ] **알림 설정**: 비정상적인 로그인 시도 패턴 감지 시 알림
- [ ] **성능 모니터링**: Redis 세션 저장소 성능 모니터링

## 8. XSS/Injection 공격 방지

### 8.1 XSS (Cross-Site Scripting) 방지

- [ ] **입력 검증**: 모든 사용자 입력에 대한 검증 및 필터링
- [ ] **출력 이스케이핑**: Blade 템플릿에서 `{{ }}` 사용 (자동 이스케이핑)
- [ ] **Content-Security-Policy 헤더**: CSP 헤더 설정 고려

### 8.2 SQL Injection 방지

- [ ] **파라미터 바인딩**: Eloquent ORM 또는 Query Builder 사용 (자동 방지)
- [ ] **Raw 쿼리 검증**: `DB::raw()` 사용 시 사용자 입력 필터링

### 8.3 LDAP/NoSQL Injection 방지

- [ ] **Firebase SDK 사용**: Firebase Admin SDK의 메서드 사용으로 Injection 방지
- [ ] **입력 검증**: Firebase UID, 이메일 등 입력 형식 검증

## 9. 권한 및 인가 (Phase 3와 연계)

### 9.1 역할 기반 접근 제어 (RBAC)

- [ ] **Spatie Permission**: `spatie/laravel-permission` 패키지 사용
- [ ] **역할 정의**: 관리자, 매장 관리자, 고객 역할 정의
- [ ] **권한 정의**: 메뉴 관리, 주문 관리, 결제 관리 등 권한 정의

### 9.2 정책(Policy) 구현

- [ ] **Eloquent Policy**: 각 모델에 대한 Policy 클래스 생성
- [ ] **게이트(Gate)**: 복잡한 권한 로직은 Gate로 구현

## 10. 보안 감사 및 리뷰

### 10.1 코드 리뷰

- [ ] **PHPStan 통과**: 정적 분석 도구로 타입 안전성 검증
- [ ] **Pint 통과**: 코드 스타일 통일
- [ ] **보안 코드 리뷰**: 보안 전문가 또는 시니어 개발자의 코드 리뷰

### 10.2 의존성 보안

- [ ] **Composer Audit**: `composer audit` 실행으로 알려진 취약점 확인
- [ ] **의존성 업데이트**: 정기적인 의존성 업데이트 계획

### 10.3 침투 테스트 (선택)

- [ ] 외부 보안 전문가의 침투 테스트 수행
- [ ] OWASP Top 10 취약점 검증

## 11. 규정 준수 (멕시코 환경)

### 11.1 데이터 보호

- [ ] **개인정보 보호**: 멕시코 개인정보 보호법(LFPDPPP) 준수
- [ ] **데이터 암호화**: 저장 시 암호화(Encryption at Rest) 고려
- [ ] **데이터 전송 암호화**: TLS 1.2+ 사용

### 11.2 결제 보안 (Phase 3)

- [ ] **PCI DSS 준수**: 결제 정보 처리 시 PCI DSS 준수
- [ ] **토큰화**: 신용카드 정보는 직접 저장하지 않고 토큰화

## 체크리스트 사용 방법

1. **개발 완료 시**: Phase 2 구현 완료 후 이 체크리스트를 검토
2. **스테이징 배포 전**: 스테이징 환경 배포 전 모든 항목 검증
3. **프로덕션 배포 전**: 프로덕션 배포 전 필수 항목(⚠️ 크리티컬) 100% 완료
4. **정기 감사**: 분기별로 체크리스트 재검토 및 업데이트

## 우선순위 가이드

- 🔴 **크리티컬(P0)**: 즉시 수정 필요 (서비스 계정 키, HTTPS, CSRF 등)
- 🟡 **중요(P1)**: 배포 전 수정 필요 (Rate Limiting, 로그 보안 등)
- 🟢 **권장(P2)**: 장기적으로 개선 필요 (침투 테스트, CSP 헤더 등)

## 관련 문서

- [인증 설계](../auth.md)
- [환경 구성](../devops/environments.md)
- [Phase 2 완료도 평가 보고서](../milestones/phase2-completion-report.md)
- [Phase 2 구현 문서](../milestones/phase2.md)
- [QA 체크리스트](../qa/checklist.md)

## 버전 이력

| 버전 | 날짜 | 작성자 | 변경 내역 |
|------|------|--------|----------|
| 1.0 | 2025-10-01 | Documentation Reviewer | 초기 작성 |

## 문의 및 피드백

보안 이슈 발견 시 즉시 프로젝트 리드에게 보고하고, 이 체크리스트를 업데이트하세요.

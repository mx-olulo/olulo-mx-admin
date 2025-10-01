# Rate Limiting 설계 문서

작성일: 2025-10-01
작성자: Laravel Expert (Claude Agent)
상태: Phase 2 완료

## 개요

본 문서는 애플리케이션의 Rate Limiting 정책과 구현 방법을 정의합니다. Rate Limiting은 브루트 포스 공격, DDoS 공격, API 남용을 방지하기 위해 필수적인 보안 메커니즘입니다.

## 아키텍처

### 설계 원칙

1. **중앙 집중식 관리**: 모든 Rate Limit 설정을 `app/Constants/RateLimit.php`에서 관리
2. **계층별 차등 적용**: 엔드포인트의 민감도에 따라 다른 제한 적용
3. **유연한 확장**: 새로운 Rate Limit 정책 추가가 용이한 구조
4. **명확한 문서화**: PHPDoc을 통한 각 상수의 목적과 사용처 명시

### Rate Limit 계층

#### 1. 인증 엔드포인트 (가장 엄격)

- **제한**: 5회/분
- **적용 대상**: 로그인, 회원가입, 비밀번호 재설정
- **목적**: 브루트 포스 공격 방지
- **미들웨어**: `throttle.auth`

#### 2. 민감한 작업 (엄격)

- **제한**: 10회/분
- **적용 대상**: 결제 처리, 주문 생성, 중요 데이터 수정
- **목적**: 악의적인 반복 요청 방지
- **미들웨어**: `throttle.sensitive` (예정)

#### 3. 일반 API (표준)

- **제한**: 60회/분
- **적용 대상**: 조회, 검색, 일반 CRUD 작업
- **목적**: 서버 리소스 보호 및 공정한 사용
- **미들웨어**: `throttle:api` (Laravel 기본)

## 구현

### 1. 상수 클래스

상수 클래스는 `readonly final` 키워드를 사용하여 불변성과 상속 불가를 보장합니다.

**파일**: `app/Constants/RateLimit.php`

주요 상수:

- `AUTH_MAX_ATTEMPTS`: 5
- `AUTH_DECAY_MINUTES`: 1
- `API_MAX_REQUESTS`: 60
- `API_DECAY_MINUTES`: 1
- `SENSITIVE_MAX_REQUESTS`: 10
- `SENSITIVE_DECAY_MINUTES`: 1

정적 메서드:

- `authThrottle()`: "5,1" 반환
- `apiThrottle()`: "60,1" 반환
- `sensitiveThrottle()`: "10,1" 반환

### 2. 미들웨어 등록

**파일**: `bootstrap/app.php`

```php
use App\Constants\RateLimit;

$middleware->alias([
    'throttle.auth' => \Illuminate\Routing\Middleware\ThrottleRequests::class . ':' . RateLimit::authThrottle(),
]);
```

### 3. 라우트 적용

#### 인증 라우트

```php
Route::middleware(['throttle.auth'])->group(function () {
    Route::post('/auth/firebase-login', [AuthController::class, 'firebaseLogin']);
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/password-reset', [AuthController::class, 'resetPassword']);
});
```

#### 민감한 작업 라우트 (예정)

```php
Route::middleware(['auth:sanctum', 'throttle.sensitive'])->group(function () {
    Route::post('/orders', [OrderController::class, 'store']);
    Route::post('/payments', [PaymentController::class, 'process']);
});
```

#### 일반 API 라우트

```php
Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/restaurants', [RestaurantController::class, 'index']);
});
```

## 사용자 경험

### 429 에러 응답

Rate Limit 초과 시 클라이언트는 다음과 같은 응답을 받습니다:

```json
{
  "message": "Too Many Attempts.",
  "retry_after": 60
}
```

응답 헤더:

- `X-RateLimit-Limit`: 허용된 최대 요청 수
- `X-RateLimit-Remaining`: 남은 요청 수
- `Retry-After`: 재시도 가능 시간(초)

### 프론트엔드 처리

#### Axios 인터셉터 예제

```javascript
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 429) {
      const retryAfter = error.response.headers['retry-after'] || 60;
      alert(`너무 많은 요청을 보냈습니다. ${retryAfter}초 후 다시 시도해주세요.`);
    }
    return Promise.reject(error);
  }
);
```

## 모니터링 및 분석

### 로깅

Rate Limit 초과 이벤트는 다음과 같이 로깅됩니다:

```php
Log::warning('Rate limit exceeded', [
    'ip' => $request->ip(),
    'user_id' => $request->user()?->id,
    'endpoint' => $request->path(),
    'limit' => RateLimit::AUTH_MAX_ATTEMPTS,
]);
```

### 메트릭

모니터링해야 할 주요 메트릭:

1. **Rate Limit 초과 횟수**: 429 응답 카운트
2. **IP별 패턴 분석**: 특정 IP의 반복적인 초과
3. **엔드포인트별 분석**: 어떤 엔드포인트가 가장 자주 제한되는지
4. **시간대별 분석**: 공격 시도 패턴 파악

## 성능 고려사항

### Redis 사용

Laravel의 Rate Limiting은 기본적으로 캐시 드라이버를 사용합니다. 프로덕션 환경에서는 Redis를 사용하여 성능을 최적화합니다.

**설정**: `config/cache.php`

```php
'default' => env('CACHE_STORE', 'redis'),
```

### 키 생성 전략

Laravel은 기본적으로 다음과 같이 Rate Limit 키를 생성합니다:

```
throttle:{middleware}:{ip}:{user_id}
```

이는 IP 주소와 사용자 ID를 조합하여 각 사용자별로 독립적인 제한을 적용합니다.

## 보안 고려사항

### 1. IP 우회 방지

프록시나 VPN을 통한 우회 방지를 위해 다음을 고려합니다:

- `X-Forwarded-For` 헤더 검증
- 사용자 인증 정보 기반 Rate Limiting 병행
- Cloudflare 등 CDN의 Rate Limiting 기능 활용

### 2. 분산 공격 대응

여러 IP에서 동시 공격이 발생할 경우:

- 전역 Rate Limit 추가 (예: API 전체 요청 수 제한)
- 자동 IP 차단 메커니즘
- WAF(Web Application Firewall) 연동

### 3. 정당한 사용자 보호

Rate Limit으로 인한 정당한 사용자 피해를 최소화:

- 인증된 사용자에게 더 높은 제한 적용
- 화이트리스트 기능 (신뢰할 수 있는 IP/사용자)
- 명확한 에러 메시지와 재시도 시간 안내

## 테스트

### 단위 테스트

```php
public function test_rate_limit_constants_are_valid(): void
{
    $this->assertIsInt(RateLimit::AUTH_MAX_ATTEMPTS);
    $this->assertIsInt(RateLimit::AUTH_DECAY_MINUTES);
    $this->assertGreaterThan(0, RateLimit::AUTH_MAX_ATTEMPTS);
    $this->assertGreaterThan(0, RateLimit::AUTH_DECAY_MINUTES);
}

public function test_auth_throttle_returns_correct_format(): void
{
    $throttle = RateLimit::authThrottle();
    $this->assertEquals('5,1', $throttle);
    $this->assertMatchesRegularExpression('/^\d+,\d+$/', $throttle);
}
```

### 기능 테스트

```php
public function test_auth_endpoint_is_rate_limited(): void
{
    // 허용된 횟수만큼 요청
    for ($i = 0; $i < RateLimit::AUTH_MAX_ATTEMPTS; $i++) {
        $response = $this->postJson('/api/auth/firebase-login', [
            'idToken' => 'invalid-token',
        ]);
        $response->assertStatus(422); // 유효성 검증 실패
    }

    // 초과 요청
    $response = $this->postJson('/api/auth/firebase-login', [
        'idToken' => 'invalid-token',
    ]);
    $response->assertStatus(429); // Rate Limit 초과
}
```

## 운영 가이드

### Rate Limit 조정

운영 중 Rate Limit 조정이 필요한 경우:

1. `app/Constants/RateLimit.php` 수정
2. Pint 및 PHPStan 검사 실행
3. 테스트 실행으로 영향 확인
4. 배포 후 모니터링 강화

### 긴급 상황 대응

공격이 감지된 경우 즉시 조치:

1. 해당 IP 차단 (방화벽 또는 WAF)
2. Rate Limit 임시 강화
3. 로그 분석으로 공격 패턴 파악
4. 필요시 서비스 일시 중단

## 향후 개선 계획

### Phase 3 (예정)

1. **동적 Rate Limiting**: 사용자 등급별 차등 적용
2. **지역별 제한**: 국가/지역별로 다른 정책 적용
3. **시간대별 조정**: 피크 시간대 제한 강화
4. **머신러닝 기반 탐지**: 비정상 패턴 자동 감지 및 차단

### Phase 4 (예정)

1. **API 사용량 대시보드**: 실시간 모니터링 UI
2. **자동 알림**: Rate Limit 초과 시 관리자 알림
3. **API 키 기반 할당량**: 파트너사별 독립적인 할당량 관리

## 관련 문서

- [인증 API 엔드포인트](../api/auth-endpoints.md)
- [보안 체크리스트](../review/checks/security-phase2-checklist.md)
- [환경 구성](../devops/environments.md)
- [Phase 2 구현 문서](../milestones/phase2.md)

## 버전 이력

| 버전 | 날짜 | 작성자 | 변경 내역 |
|------|------|--------|----------|
| 1.0 | 2025-10-01 | Laravel Expert | 초기 작성 - RateLimit 상수 클래스 구현 |

## 문의

Rate Limiting 정책 관련 문의나 개선 제안이 있는 경우 프로젝트 리드에게 연락하세요.

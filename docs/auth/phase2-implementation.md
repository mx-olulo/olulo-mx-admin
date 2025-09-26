# Phase2 인증 구현 계획

## 개요
Phase2는 Firebase Admin SDK와 Sanctum SPA 세션을 통합하여 안전한 인증 기반을 구축하는 단계입니다.

## 구현 목표
1. Firebase Admin SDK를 통한 서버 측 인증 토큰 검증
2. Sanctum SPA 세션 기반 상태 관리
3. 서브도메인 지원 CORS 및 CSRF 보호
4. 환경별 설정 템플릿 표준화

## 아키텍처 구성

### 1. Firebase Admin SDK 통합
```
app/Services/Auth/
├── FirebaseAuthService.php       # Firebase Admin SDK 래퍼
├── Contracts/
│   └── FirebaseAuthInterface.php # 인터페이스 정의
└── Exceptions/
    └── FirebaseAuthException.php # 커스텀 예외
```

### 2. 인증 컨트롤러
```
app/Http/Controllers/Auth/
├── FirebaseAuthController.php    # Firebase 인증 처리
└── SessionController.php         # 세션 관리
```

### 3. 미들웨어 구성
```
app/Http/Middleware/
├── ValidateFirebaseToken.php     # Firebase 토큰 검증
└── EnsureValidTenant.php         # 테넌트 검증
```

## 환경 설정

### Firebase 설정
```env
# Firebase Admin SDK
FIREBASE_PROJECT_ID=olulo-mx-admin
FIREBASE_CLIENT_EMAIL=firebase-adminsdk@olulo-mx-admin.iam.gserviceaccount.com
FIREBASE_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----\n...\n-----END PRIVATE KEY-----\n"

# Firebase Web SDK (프론트엔드용)
FIREBASE_WEB_API_KEY=
FIREBASE_AUTH_DOMAIN=
FIREBASE_STORAGE_BUCKET=
FIREBASE_MESSAGING_SENDER_ID=
FIREBASE_APP_ID=
```

### Sanctum/세션 설정
```env
# 개발 환경
SESSION_DRIVER=cookie
SESSION_DOMAIN=.localhost
SANCTUM_STATEFUL_DOMAINS=localhost:3000,localhost:8000,admin.localhost,menu.localhost

# 스테이징 환경
SESSION_DRIVER=redis
SESSION_DOMAIN=.demo.olulo.com.mx
SANCTUM_STATEFUL_DOMAINS=admin.demo.olulo.com.mx,menu.demo.olulo.com.mx,api.demo.olulo.com.mx

# 프로덕션 환경
SESSION_DRIVER=redis
SESSION_DOMAIN=.olulo.com.mx
SANCTUM_STATEFUL_DOMAINS=admin.olulo.com.mx,menu.olulo.com.mx,api.olulo.com.mx
```

### CORS 설정
```php
// config/cors.php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        env('APP_ENV') === 'local'
            ? 'http://localhost:3000'
            : 'https://*.olulo.com.mx'
    ],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
```

## API 엔드포인트

### 인증 엔드포인트
- `POST /api/auth/firebase/login` - Firebase ID 토큰으로 로그인
- `POST /api/auth/firebase/verify` - 토큰 유효성 검증
- `POST /api/auth/logout` - 로그아웃
- `GET /api/auth/user` - 현재 사용자 정보

### 요청/응답 형식
```json
// POST /api/auth/firebase/login
{
  "id_token": "eyJhbGciOiJSUzI1NiIsImtpZCI6..."
}

// Response (204 No Content)
// 세션 쿠키가 자동으로 설정됨

// GET /api/auth/user
{
  "id": 1,
  "firebase_uid": "abc123",
  "email": "user@example.com",
  "name": "John Doe",
  "roles": ["store_manager"],
  "permissions": ["manage_orders", "view_reports"]
}
```

## 보안 체크리스트
- [ ] HTTPS 전용 쿠키 설정
- [ ] SameSite=Lax 쿠키 정책
- [ ] Firebase ID 토큰 만료 시간 검증
- [ ] CSRF 토큰 검증
- [ ] Rate limiting 적용
- [ ] 환경별 도메인 화이트리스트

## 테스트 계획
1. 단위 테스트
   - FirebaseAuthService 토큰 검증
   - 세션 생성/파기
   - 미들웨어 동작

2. 통합 테스트
   - 전체 인증 플로우
   - 크로스 도메인 세션 공유
   - 권한별 접근 제어

3. E2E 테스트
   - 고객 앱 로그인/로그아웃
   - 관리자 대시보드 접근
   - 세션 만료 처리

## 구현 단계
1. **Phase 2.1** - Firebase Admin SDK 설정 (45분)
   - Composer 패키지 설치
   - 서비스 프로바이더 등록
   - 환경변수 설정

2. **Phase 2.2** - Sanctum 설정 (30분)
   - Stateful 도메인 구성
   - 세션 드라이버 설정
   - CSRF 보호 활성화

3. **Phase 2.3** - CORS/보안 설정 (30분)
   - 서브도메인 화이트리스트
   - Preflight 요청 처리
   - Rate limiting 규칙

## 관련 문서
- [인증 설계](../auth.md)
- [환경 설정](../devops/environments.md)
- [테넌시 미들웨어](../tenancy/host-middleware.md)
- [프로젝트 1 마일스톤](../milestones/project-1.md)
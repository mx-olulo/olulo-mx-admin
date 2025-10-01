# Firebase Emulator 기반 E2E 테스트 가이드

본 문서는 Firebase Emulator를 사용한 인증 플로우 E2E 테스트 실행 방법을 설명합니다.

## 목적

실제 브라우저 환경에서 Firebase 로그인 → Sanctum 세션 확립 → 보호된 라우트 접근 전체 플로우를 검증합니다.

## 테스트 파일

- `/tests/Browser/Auth/FirebaseLoginTest.php`

## 사전 준비

### 1. Firebase Emulator Suite 설치

```bash
npm install -g firebase-tools
```

### 2. Firebase Emulator 초기화

프로젝트 루트에서:

```bash
firebase init emulators
```

다음 옵션 선택:
- Authentication Emulator
- 포트: 9099 (기본값)

### 3. Firebase Emulator 실행

```bash
firebase emulators:start --only auth
```

또는 백그라운드 실행:

```bash
firebase emulators:start --only auth &
```

### 4. 환경변수 설정

`.env.testing` 파일에 다음 추가:

```env
FIREBASE_USE_EMULATOR=true
FIREBASE_PROJECT_ID=demo-project
FIREBASE_AUTH_EMULATOR_HOST=127.0.0.1:9099
```

### 5. Laravel Dusk 설치 확인

```bash
php artisan dusk:install
```

Chrome Driver 자동 설치:

```bash
php artisan dusk:chrome-driver
```

## 테스트 실행

### 전체 Dusk 테스트 제외하고 실행 (CI 환경)

```bash
vendor/bin/phpunit --exclude-group dusk
```

### Firebase E2E 테스트만 실행 (로컬 환경)

```bash
php artisan dusk --group=dusk
```

또는 특정 테스트 파일만:

```bash
php artisan dusk tests/Browser/Auth/FirebaseLoginTest.php
```

### 특정 테스트 메서드만 실행

```bash
php artisan dusk --filter=test_user_can_login_with_firebase_emulator
```

## 테스트 시나리오

### 1. test_user_can_login_with_firebase_emulator

기본 Firebase Emulator 로그인 플로우 검증

**단계:**
1. CSRF 토큰 획득 (`/sanctum/csrf-cookie`)
2. Firebase Emulator로 테스트 사용자 생성
3. Firebase 로그인하여 ID Token 획득
4. Laravel API에 ID Token 전송 (`/api/auth/firebase-login`)
5. 보호된 사용자 정보 엔드포인트 접근 (`/api/user`)

### 2. test_sanctum_session_established_after_firebase_login

Sanctum 세션 확립 검증

**검증 항목:**
- Laravel 세션 쿠키 존재 여부
- XSRF 토큰 쿠키 존재 여부
- 쿠키 도메인 설정 일치 여부

### 3. test_authenticated_user_can_access_protected_routes

인증된 사용자의 보호된 라우트 접근 검증

**검증 항목:**
- `/api/user` 엔드포인트 접근
- 사용자 정보 JSON 응답 확인
- 사용자 이메일 일치 확인

### 4. test_logout_invalidates_session

로그아웃 시 세션 무효화 검증

**단계:**
1. Firebase 로그인 및 세션 확립
2. 로그아웃 API 호출 (`/api/auth/logout`)
3. 보호된 라우트 접근 시 인증 실패 확인

### 5. test_invalid_firebase_token_is_rejected

잘못된 Firebase ID Token 처리 검증

**검증 항목:**
- 잘못된 토큰으로 로그인 시도
- 401 Unauthorized 또는 422 Unprocessable Entity 응답 확인

## 주요 특징

### 1. 목업 금지 원칙

실제 Firebase Emulator를 사용하여 프로덕션 환경과 동일한 인증 플로우를 검증합니다.

### 2. Firebase Auth Emulator REST API 직접 호출

Firebase Admin SDK를 사용하지 않고, REST API를 직접 호출하여:
- 사용자 생성: `POST /identitytoolkit.googleapis.com/v1/accounts:signUp`
- 로그인: `POST /identitytoolkit.googleapis.com/v1/accounts:signInWithPassword`

참고: [Firebase Auth REST API](https://firebase.google.com/docs/reference/rest/auth)

### 3. 브라우저 JavaScript 컨텍스트 활용

`$browser->script()` 메서드를 사용하여 실제 브라우저에서 `fetch()` API 호출:
- XSRF 토큰 쿠키 자동 추출
- `credentials: 'include'` 설정으로 쿠키 전송
- JSON 요청/응답 처리

### 4. 동일 루트 도메인 세션 정책 준수

`docs/auth.md`에 정의된 동일 루트 도메인 기반 Sanctum SPA 세션 정책을 따릅니다:
- `SESSION_DOMAIN=.example.com`
- `SANCTUM_STATEFUL_DOMAINS` 설정
- CSRF 보호 및 쿠키 기반 인증

## 문제 해결

### Firebase Emulator 연결 실패

```
Firebase Emulator가 실행되지 않았습니다. 먼저 에뮬레이터를 시작하세요.
```

**해결책:**
```bash
firebase emulators:start --only auth
```

### Chrome Driver 에러

```
Laravel\Dusk\Exceptions\DriverException: Chrome driver not found
```

**해결책:**
```bash
php artisan dusk:chrome-driver
```

### 세션 쿠키 누락

세션이 유지되지 않는 경우:

1. `SESSION_DOMAIN` 설정 확인
2. `SANCTUM_STATEFUL_DOMAINS` 구성 확인
3. CORS `credentials: true` 설정 확인
4. XSRF 토큰 헤더 포함 여부 확인

### JavaScript 실행 에러

브라우저 콘솔 로그 확인:

```bash
php artisan dusk --debug
```

## CI/CD 통합

### GitHub Actions 예시

```yaml
name: E2E Tests

on:
  pull_request:
    branches: [main, production]

jobs:
  dusk:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v3

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: mbstring, pdo_sqlite

      - name: Install Firebase Tools
        run: npm install -g firebase-tools

      - name: Start Firebase Emulator
        run: firebase emulators:start --only auth &

      - name: Install Dependencies
        run: composer install

      - name: Install Chrome Driver
        run: php artisan dusk:chrome-driver

      - name: Run Dusk Tests
        run: php artisan dusk --group=dusk
        env:
          FIREBASE_USE_EMULATOR: true
          FIREBASE_PROJECT_ID: demo-project
          FIREBASE_AUTH_EMULATOR_HOST: 127.0.0.1:9099
```

## 추가 참고 문서

- 인증 설계: `docs/auth.md`
- 프로젝트 1: `docs/milestones/project-1.md`
- QA 체크리스트: `docs/qa/checklist.md`
- 환경별 도메인/CORS: `docs/devops/environments.md`

## 후속 작업

- [ ] 추가 시나리오: 토큰 만료 처리
- [ ] 추가 시나리오: 다중 서브도메인 간 세션 공유
- [ ] 추가 시나리오: 권한별 라우트 접근 제어
- [ ] 성능 테스트: 동시 로그인 처리
- [ ] Visual Regression Testing 추가

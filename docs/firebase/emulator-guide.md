# Firebase Emulator 사용 가이드

## 개요

개발 환경에서 Firebase Authentication을 로컬에서 테스트하기 위해 Firebase Emulator를 사용합니다.

## 사전 요구사항

- Node.js 18+ 설치
- Firebase CLI 설치: `npm install -g firebase-tools`
- Java JDK 11+ 설치 (Emulator 실행에 필요)

## 환경 설정

### 1. .env 파일 설정

`.env` 파일에 다음 환경변수를 추가하세요:

```bash
# Firebase Emulator 사용 활성화
FIREBASE_USE_EMULATOR=true
FIREBASE_AUTH_EMULATOR_HOST=127.0.0.1:9099

# Firebase 프로젝트 설정 (Emulator용 더미 값)
FIREBASE_PROJECT_ID=demo-project
FIREBASE_CLIENT_EMAIL=firebase-adminsdk@demo-project.iam.gserviceaccount.com
FIREBASE_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----
demo-key-for-emulator
-----END PRIVATE KEY-----"

# Firebase Web SDK (프론트엔드)
FIREBASE_WEB_API_KEY=demo-api-key
FIREBASE_AUTH_DOMAIN=127.0.0.1
FIREBASE_STORAGE_BUCKET=demo-project.appspot.com
FIREBASE_MESSAGING_SENDER_ID=123456789
FIREBASE_APP_ID=1:123456789:web:demo

# Vite 환경변수 (프론트엔드 Emulator 연결)
VITE_FIREBASE_API_KEY="${FIREBASE_WEB_API_KEY}"
VITE_FIREBASE_PROJECT_ID="${FIREBASE_PROJECT_ID}"
VITE_FIREBASE_AUTH_DOMAIN="${FIREBASE_AUTH_DOMAIN}"
VITE_FIREBASE_STORAGE_BUCKET="${FIREBASE_STORAGE_BUCKET}"
VITE_FIREBASE_MESSAGING_SENDER_ID="${FIREBASE_MESSAGING_SENDER_ID}"
VITE_FIREBASE_APP_ID="${FIREBASE_APP_ID}"
VITE_FIREBASE_USE_EMULATOR=true
VITE_FIREBASE_AUTH_EMULATOR_HOST="127.0.0.1:9099"
```

### 2. firebase.json 확인

프로젝트 루트의 `firebase.json` 파일에 Emulator 설정이 있는지 확인:

```json
{
  "emulators": {
    "auth": {
      "port": 9099,
      "host": "127.0.0.1"
    },
    "ui": {
      "enabled": true,
      "port": 4000,
      "host": "127.0.0.1"
    }
  }
}
```

## Emulator 실행

### 터미널 1: Firebase Emulator

```bash
firebase emulators:start
```

성공 시 다음과 같은 메시지가 표시됩니다:

```
┌─────────────────────────────────────────────────────────────┐
│ ✔  All emulators ready! It is now safe to connect your app. │
│ i  View Emulator UI at http://127.0.0.1:4000                │
└─────────────────────────────────────────────────────────────┘

┌────────────┬────────────────┬─────────────────────────────────┐
│ Emulator   │ Host:Port      │ View in Emulator UI             │
├────────────┼────────────────┼─────────────────────────────────┤
│ Auth       │ 127.0.0.1:9099 │ http://127.0.0.1:4000/auth      │
└────────────┴────────────────┴─────────────────────────────────┘
```

### 터미널 2: Laravel 서버

```bash
php artisan serve
```

### 터미널 3: Vite 개발 서버

```bash
npm run dev
```

## Emulator 사용

### 1. Emulator UI 접속

브라우저에서 http://127.0.0.1:4000 접속

- **Authentication**: 사용자 목록, 수동 사용자 생성, 토큰 확인
- **Logs**: 인증 요청 로그 확인

### 2. 애플리케이션 테스트

1. http://localhost:8000 접속 (Laravel 서버)
2. 로그인 페이지로 이동
3. Google 또는 Email로 로그인 시도
4. Emulator UI에서 사용자 생성 확인

### 3. 테스트 사용자 생성 (수동)

Emulator UI (http://127.0.0.1:4000/auth)에서:

1. **Add user** 버튼 클릭
2. Email과 Password 입력
3. **Add** 클릭
4. 애플리케이션에서 해당 계정으로 로그인 테스트

## 디버깅

### Firebase 연결 확인

브라우저 콘솔에서 다음 메시지 확인:

```
🔧 Firebase Auth Emulator connected: 127.0.0.1:9099
```

### Emulator 로그 확인

Firebase Emulator 터미널에서 실시간 로그 확인:

```
i  auth: Beginning /identitytoolkit.googleapis.com/v1/accounts:signInWithPassword
i  auth: Finished /identitytoolkit.googleapis.com/v1/accounts:signInWithPassword
```

### 일반적인 문제

#### 1. Emulator 연결 실패

**증상**: "Failed to connect to emulator" 에러

**해결책**:
- Firebase Emulator가 실행 중인지 확인
- 포트 9099가 사용 가능한지 확인: `lsof -i :9099`
- `.env` 파일의 `VITE_FIREBASE_USE_EMULATOR=true` 확인
- Vite 개발 서버 재시작 (환경변수 변경 후)

#### 2. CORS 에러

**증상**: Cross-origin 에러 발생

**해결책**:
- Emulator는 자동으로 CORS를 허용하므로 문제가 없어야 함
- Laravel 서버와 Vite 서버가 모두 실행 중인지 확인

#### 3. ID Token 검증 실패

**증상**: Laravel에서 "Invalid token" 에러

**해결책**:
- Laravel `.env`의 `FIREBASE_USE_EMULATOR=true` 확인
- AuthController에서 Emulator 모드 확인
- Emulator가 127.0.0.1:9099에서 실행 중인지 확인

## Emulator 데이터 초기화

Emulator 데이터를 지우려면:

```bash
firebase emulators:start --clear
```

## 프로덕션 전환

프로덕션으로 전환 시:

1. `.env` 파일 수정:
   ```bash
   FIREBASE_USE_EMULATOR=false
   VITE_FIREBASE_USE_EMULATOR=false
   ```

2. 실제 Firebase 프로젝트 정보로 환경변수 설정:
   - `FIREBASE_PROJECT_ID`
   - `FIREBASE_PRIVATE_KEY`
   - `VITE_FIREBASE_API_KEY`
   - 등등...

3. Vite 빌드:
   ```bash
   npm run build
   ```

## 참고 자료

- [Firebase Emulator Suite 공식 문서](https://firebase.google.com/docs/emulator-suite)
- [Firebase Auth Emulator](https://firebase.google.com/docs/emulator-suite/connect_auth)
- [프로젝트 인증 문서](../auth.md)

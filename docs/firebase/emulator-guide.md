# Firebase 에뮬레이터 로컬 개발 가이드

로컬 개발 환경에서 Firebase 에뮬레이터를 사용하는 방법입니다.

## 개요

Firebase 에뮬레이터를 사용하면 실제 Firebase 프로젝트 없이 로컬에서 인증, Firestore 등을 테스트할 수 있습니다.

## 에뮬레이터 장점

- 실제 Firebase 프로젝트 사용 없이 로컬에서 인증 테스트
- 비용 없음
- 빠른 개발 사이클
- 오프라인 개발 가능
- 데이터 격리 (개발/테스트 데이터가 프로덕션에 영향 없음)

## 사전 요구사항

- Node.js 18+ 설치
- Firebase CLI 설치: `npm install -g firebase-tools`
- Java JDK 11+ 설치 (Emulator 실행에 필요)

## 설정 방법

### 1. 자동 설정 (권장)

```bash
cd /opt/GitHub/olulo-mx-admin
bash scripts/setup-firebase-emulator.sh
```

### 2. 수동 설정

`.env` 파일에 다음 설정을 추가하세요:

```bash
# 에뮬레이터 활성화
FIREBASE_USE_EMULATOR=true
FIREBASE_AUTH_EMULATOR_HOST=127.0.0.1:9099

# Firebase 프로젝트 설정 (에뮬레이터용 더미 값)
FIREBASE_PROJECT_ID=demo-project
FIREBASE_CLIENT_EMAIL=firebase-adminsdk@demo-project.iam.gserviceaccount.com
FIREBASE_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----
demo-key-for-emulator
-----END PRIVATE KEY-----"

# Firebase Web SDK (프론트엔드)
FIREBASE_WEB_API_KEY=demo-api-key
FIREBASE_AUTH_DOMAIN=localhost
FIREBASE_STORAGE_BUCKET=demo-project.appspot.com
FIREBASE_MESSAGING_SENDER_ID=000000000000
FIREBASE_APP_ID=1:000000000000:web:demo
FIREBASE_MEASUREMENT_ID=G-DEMO

# Vite 환경변수 (프론트엔드 에뮬레이터 연결)
VITE_FIREBASE_API_KEY="${FIREBASE_WEB_API_KEY}"
VITE_FIREBASE_PROJECT_ID="${FIREBASE_PROJECT_ID}"
VITE_FIREBASE_AUTH_DOMAIN="${FIREBASE_AUTH_DOMAIN}"
VITE_FIREBASE_STORAGE_BUCKET="${FIREBASE_STORAGE_BUCKET}"
VITE_FIREBASE_MESSAGING_SENDER_ID="${FIREBASE_MESSAGING_SENDER_ID}"
VITE_FIREBASE_APP_ID="${FIREBASE_APP_ID}"
VITE_FIREBASE_MEASUREMENT_ID="${FIREBASE_MEASUREMENT_ID}"
VITE_FIREBASE_AUTH_EMULATOR_HOST="${FIREBASE_AUTH_EMULATOR_HOST}"
```

### 3. firebase.json 확인

프로젝트 루트의 `firebase.json` 파일에 에뮬레이터 설정이 있는지 확인:

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

### 4. Firebase 에뮬레이터 실행

```bash
# Firebase CLI 설치 (없는 경우)
npm install -g firebase-tools

# 에뮬레이터 시작
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

### 5. Laravel 설정 재생성

```bash
php artisan config:clear
php artisan config:cache
```

### 6. 프론트엔드 개발 서버 실행

```bash
npm run dev
# 또는 프로덕션 빌드
npm run build
```

## 에뮬레이터 접속 정보

- **Emulator UI**: http://127.0.0.1:4000
- **인증 에뮬레이터**: http://127.0.0.1:9099
- **Firestore 에뮬레이터**: http://127.0.0.1:8080 (설정 시)

## 프론트엔드에서 에뮬레이터 연결

### 자동 연결 (현재 구현)

프로젝트는 `import.meta.env.DEV` 기반으로 개발 환경에서 자동으로 에뮬레이터에 연결됩니다.

`resources/js/lib/firebase.ts`:

```typescript
import { getAuth, connectAuthEmulator } from 'firebase/auth';

const auth = getAuth();

// 개발 환경에서 자동으로 에뮬레이터 연결
if (import.meta.env.DEV) {
  const emulatorHost = import.meta.env.VITE_FIREBASE_AUTH_EMULATOR_HOST || 'localhost:9099';

  try {
    connectAuthEmulator(auth, `http://${emulatorHost}`, { disableWarnings: true });
    console.log(`🔧 Firebase Auth Emulator connected: ${emulatorHost}`);
  } catch (error) {
    console.warn('Auth Emulator already connected');
  }
}
```

### 수동 연결 (참고용)

필요시 `.env`에서 `VITE_FIREBASE_USE_EMULATOR=true`를 확인하여 연결할 수도 있습니다:

```javascript
if (import.meta.env.VITE_FIREBASE_USE_EMULATOR === 'true') {
  const emulatorHost = import.meta.env.VITE_FIREBASE_AUTH_EMULATOR_HOST || '127.0.0.1:9099';
  connectAuthEmulator(auth, `http://${emulatorHost}`, { disableWarnings: true });
}
```

## Emulator UI 사용법

### 1. Emulator UI 접속

브라우저에서 http://127.0.0.1:4000 접속

### 2. 주요 기능

- **Authentication 탭**
  - 사용자 목록 확인
  - 수동 사용자 생성/삭제
  - 사용자 ID 토큰 확인
  - 사용자 속성(displayName, email, photoURL 등) 수정

- **Logs 탭**
  - 실시간 인증 요청 로그 확인
  - API 호출 내역 및 응답 확인

### 3. 수동 테스트 사용자 생성

Emulator UI (http://127.0.0.1:4000/auth)에서:

1. **Add user** 버튼 클릭
2. Email과 Password 입력
3. **Add** 클릭
4. 애플리케이션에서 해당 계정으로 로그인 테스트

### 4. 자동 사용자 생성 (권장)

에뮬레이터에서는 아무 이메일/비밀번호로 즉시 가입 가능:

```
test@example.com / password123
admin@example.com / admin123
user1@example.com / test1234
```

## 애플리케이션 테스트

### 멀티 터미널 실행 예시

**터미널 1: Firebase Emulator**
```bash
firebase emulators:start
```

**터미널 2: Laravel 서버**
```bash
php artisan serve
```

**터미널 3: Vite 개발 서버**
```bash
npm run dev
```

### 테스트 절차

1. http://localhost:8000 접속 (Laravel 서버)
2. 로그인 페이지로 이동
3. Google 또는 Email로 로그인 시도
4. Emulator UI (http://127.0.0.1:4000/auth)에서 사용자 생성 확인
5. Laravel 백엔드에서 ID Token 검증 확인

## 디버깅

### Firebase 연결 확인

브라우저 콘솔에서 다음 메시지 확인:

```
🔧 Firebase Auth Emulator connected: 127.0.0.1:9099
```

또는

```
Auth Emulator already connected
```

### Emulator 로그 확인

Firebase Emulator 터미널에서 실시간 로그 확인:

```
i  auth: Beginning /identitytoolkit.googleapis.com/v1/accounts:signInWithPassword
i  auth: Finished /identitytoolkit.googleapis.com/v1/accounts:signInWithPassword
```

### Laravel 백엔드 로그

```bash
tail -f storage/logs/laravel.log
```

## 문제 해결

### "Firebase: authDomain required" 에러

**원인**: Vite 환경변수 누락

**해결책**:
- `.env`에서 `VITE_FIREBASE_AUTH_DOMAIN=localhost` 확인
- `npm run dev` 또는 `npm run build` 재실행
- 브라우저 캐시 초기화

### 에뮬레이터 연결 실패

**증상**: "Failed to connect to emulator" 에러

**해결책**:
- `firebase emulators:start`가 실행 중인지 확인
- 포트 9099가 사용 가능한지 확인: `lsof -i :9099`
- 포트가 사용 중이면 프로세스 종료: `kill -9 <PID>`
- `.env` 파일의 `VITE_FIREBASE_AUTH_EMULATOR_HOST=127.0.0.1:9099` 확인
- Vite 개발 서버 재시작 (환경변수 변경 후 반드시 재시작)

### CORS 에러

**원인**: 일반적으로 에뮬레이터는 localhost 요청을 자동으로 허용하므로 CORS 문제가 발생하지 않아야 함

**해결책**:
- Laravel 서버와 Vite 서버가 모두 실행 중인지 확인
- `APP_URL`이 `http://localhost` 또는 `http://127.0.0.1`인지 확인
- 브라우저 콘솔에서 실제 CORS 에러 메시지 확인

### ID Token 검증 실패

**증상**: Laravel에서 "Invalid token" 에러 발생

**해결책**:
- Laravel `.env`의 `FIREBASE_USE_EMULATOR=true` 확인
- `AuthController`에서 에뮬레이터 모드가 활성화되었는지 확인
- 에뮬레이터가 `127.0.0.1:9099`에서 실행 중인지 확인
- Laravel 설정 캐시 재생성:
  ```bash
  php artisan config:clear
  php artisan config:cache
  ```

### Java JDK 누락 에러

**증상**: "Firebase Emulator requires Java JDK" 에러

**해결책**:
- Java JDK 11+ 설치 확인: `java -version`
- macOS: `brew install openjdk@11`
- Ubuntu: `sudo apt install openjdk-11-jdk`
- 환경변수 설정 후 터미널 재시작

## Emulator 데이터 관리

### 데이터 초기화

Emulator 데이터를 완전히 지우고 재시작:

```bash
firebase emulators:start --clear
```

### 데이터 보존

- 기본적으로 에뮬레이터 데이터는 메모리에 저장됨
- 에뮬레이터 재시작 시 데이터가 초기화됨
- 데이터 지속성이 필요한 경우 `firebase.json`에 `--import` / `--export-on-exit` 옵션 추가

## 실제 프로덕션 전환

개발 서버(dev.olulo.com.mx)나 프로덕션에서는:

### 1. .env 파일 수정

```bash
# 에뮬레이터 비활성화
FIREBASE_USE_EMULATOR=false

# 실제 Firebase 프로젝트 정보
FIREBASE_PROJECT_ID=mx-olulo
FIREBASE_AUTH_DOMAIN=mx-olulo.firebaseapp.com
FIREBASE_STORAGE_BUCKET=mx-olulo.appspot.com
FIREBASE_MESSAGING_SENDER_ID=실제값
FIREBASE_APP_ID=실제값
FIREBASE_MEASUREMENT_ID=실제값

# 실제 Private Key (서버 측)
FIREBASE_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----
실제 Private Key
-----END PRIVATE KEY-----"
```

### 2. Vite 환경변수 갱신

```bash
VITE_FIREBASE_API_KEY=실제API키
VITE_FIREBASE_PROJECT_ID=mx-olulo
VITE_FIREBASE_AUTH_DOMAIN=mx-olulo.firebaseapp.com
VITE_FIREBASE_STORAGE_BUCKET=mx-olulo.appspot.com
VITE_FIREBASE_MESSAGING_SENDER_ID=실제값
VITE_FIREBASE_APP_ID=실제값
```

### 3. 프론트엔드 프로덕션 빌드

```bash
npm run build
```

### 4. Laravel 설정 재생성

```bash
php artisan config:clear
php artisan config:cache
```

## 주의사항

- 에뮬레이터 데이터는 재시작 시 초기화됨 (별도 설정 없으면)
- 에뮬레이터는 localhost에서만 접근 가능
- 실제 Firebase 프로젝트와 데이터 공유 안 됨
- `authDomain`을 localhost로 설정해도 에뮬레이터가 처리함
- 프로덕션 배포 시 반드시 `.env` 파일의 `FIREBASE_USE_EMULATOR=false` 확인
- 개발 환경에서는 `import.meta.env.DEV` 기반 자동 연결 활용 (명시적 플래그 불필요)

## 참고 문서

### 내부 문서
- [인증 설계 문서](../auth.md)
- [환경 설정 가이드](../devops/environments.md)
- [프로젝트 1 마일스톤](../milestones/project-1.md)
- [환경변수 관리](../todo/environment-variables.md)

### 외부 문서
- [Firebase 에뮬레이터 공식 문서](https://firebase.google.com/docs/emulator-suite)
- [인증 에뮬레이터 가이드](https://firebase.google.com/docs/emulator-suite/connect_auth)
- [Firebase Admin SDK](https://firebase.google.com/docs/admin/setup)

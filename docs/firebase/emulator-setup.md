# Firebase 에뮬레이터 로컬 개발 가이드

로컬 개발 환경에서 Firebase 에뮬레이터를 사용하는 방법입니다.

## 에뮬레이터 장점

- 실제 Firebase 프로젝트 사용 없이 로컬에서 인증 테스트
- 비용 없음
- 빠른 개발 사이클
- 오프라인 개발 가능

## 설정 방법

### 1. 자동 설정 (권장)

```bash
cd /opt/GitHub/olulo-mx-admin
bash scripts/setup-firebase-emulator.sh
```

### 2. 수동 설정

.env 파일에 다음 설정:

```bash
# 에뮬레이터 활성화
FIREBASE_USE_EMULATOR=true
FIREBASE_AUTH_EMULATOR_HOST=127.0.0.1:9099

# 에뮬레이터용 더미 설정
FIREBASE_AUTH_DOMAIN=localhost
FIREBASE_STORAGE_BUCKET=demo-project.appspot.com
FIREBASE_MESSAGING_SENDER_ID=000000000000
FIREBASE_APP_ID=1:000000000000:web:demo
FIREBASE_MEASUREMENT_ID=G-DEMO

# Vite 환경변수
VITE_FIREBASE_USE_EMULATOR="${FIREBASE_USE_EMULATOR}"
VITE_FIREBASE_AUTH_EMULATOR_HOST="${FIREBASE_AUTH_EMULATOR_HOST}"
VITE_FIREBASE_AUTH_DOMAIN="${FIREBASE_AUTH_DOMAIN}"
VITE_FIREBASE_STORAGE_BUCKET="${FIREBASE_STORAGE_BUCKET}"
VITE_FIREBASE_MESSAGING_SENDER_ID="${FIREBASE_MESSAGING_SENDER_ID}"
VITE_FIREBASE_APP_ID="${FIREBASE_APP_ID}"
```

### 3. Firebase 에뮬레이터 실행

```bash
# Firebase CLI 설치 (없는 경우)
npm install -g firebase-tools

# 에뮬레이터 시작
firebase emulators:start
```

### 4. Laravel 설정 재생성

```bash
php artisan config:clear
php artisan config:cache
```

### 5. 프론트엔드 빌드

```bash
npm run build
# 또는 개발 서버
npm run dev
```

## 에뮬레이터 접속

- 에뮬레이터 UI: http://127.0.0.1:4000
- 인증 에뮬레이터: http://127.0.0.1:9099
- Firestore 에뮬레이터: http://127.0.0.1:8080

## 프론트엔드에서 에뮬레이터 연결

React/JavaScript 코드에서:

```javascript
import { getAuth, connectAuthEmulator } from 'firebase/auth';

const auth = getAuth();

// .env에서 VITE_FIREBASE_USE_EMULATOR 확인
if (import.meta.env.VITE_FIREBASE_USE_EMULATOR === 'true') {
  const emulatorHost = import.meta.env.VITE_FIREBASE_AUTH_EMULATOR_HOST || '127.0.0.1:9099';
  connectAuthEmulator(auth, `http://${emulatorHost}`, { disableWarnings: true });
}
```

## 테스트 사용자 생성

에뮬레이터에서는 아무 이메일/비밀번호로 가입 가능:

```
test@example.com / password123
admin@example.com / admin123
```

## 실제 프로덕션 전환

개발 서버(dev.olulo.com.mx)나 프로덕션에서는:

```bash
FIREBASE_USE_EMULATOR=false
FIREBASE_AUTH_DOMAIN=mx-olulo.firebaseapp.com
```

실제 Firebase 프로젝트 설정 사용.

## 주의사항

- 에뮬레이터 데이터는 재시작 시 초기화됨
- 에뮬레이터는 localhost에서만 접근 가능
- 실제 Firebase 프로젝트와 데이터 공유 안 됨
- authDomain을 localhost로 설정해도 에뮬레이터가 처리함

## 문제 해결

### "Firebase: authDomain required" 에러
- VITE_FIREBASE_AUTH_DOMAIN이 설정되어 있는지 확인
- npm run build 재실행

### 에뮬레이터 연결 실패
- firebase emulators:start가 실행 중인지 확인
- 포트 9099가 사용 가능한지 확인: `lsof -i :9099`

### CORS 에러
- 에뮬레이터는 localhost 요청을 자동으로 허용
- APP_URL이 localhost 또는 127.0.0.1인지 확인

## 참고 문서

### 내부 문서
- [인증 설계 문서](../auth.md)
- [환경 설정 가이드](../devops/environments.md)
- [프로젝트 1 마일스톤](../milestones/project-1.md)
- [환경변수 관리](../todo/environment-variables.md)

### 외부 문서
- [Firebase 에뮬레이터 공식 문서](https://firebase.google.com/docs/emulator-suite)
- [인증 에뮬레이터 가이드](https://firebase.google.com/docs/emulator-suite/connect_auth)

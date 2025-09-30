# Firebase Local Emulator Suite 설정 계획

## 📝 중요 메모리 기록
**테스트 환경에서는 Firebase Local Emulator Suite를 사용한다.**

## 🎯 목표
- 로컬 개발/테스트 환경에서 Firebase 서비스를 에뮬레이트
- 프로덕션 Firebase 프로젝트에 영향 없이 안전한 테스트 환경 구축
- 인증, Firestore, Functions 등 Firebase 서비스 로컬 실행

## 🛠️ Firebase Emulator Suite 설치 및 설정

### 1. Firebase CLI 설치
```bash
# Firebase CLI 설치 (Node.js 필요)
npm install -g firebase-tools

# Firebase 로그인 (프로덕션 설정용)
firebase login

# 프로젝트 초기화
cd /opt/GitHub/olulo-mx-admin
firebase init
```

### 2. Emulator 설정 파일 (firebase.json)
```json
{
  "emulators": {
    "auth": {
      "port": 9099
    },
    "firestore": {
      "port": 8080
    },
    "ui": {
      "enabled": true,
      "port": 4000
    },
    "singleProjectMode": true
  },
  "firestore": {
    "rules": "firestore.rules",
    "indexes": "firestore.indexes.json"
  }
}
```

### 3. 환경별 Firebase 설정

#### 개발/테스트 환경 (.env.testing)
```env
# Firebase Emulator 설정
FIREBASE_AUTH_EMULATOR_HOST=127.0.0.1:9099
FIREBASE_FIRESTORE_EMULATOR_HOST=127.0.0.1:8080
FIREBASE_PROJECT_ID=demo-olulo-mx
FIREBASE_USE_EMULATOR=true

# 테스트용 Firebase 설정
FIREBASE_WEB_API_KEY=demo-api-key
FIREBASE_CLIENT_EMAIL=test@demo-olulo-mx.iam.gserviceaccount.com
FIREBASE_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----\n[테스트용 더미 키]\n-----END PRIVATE KEY-----"
```

#### 프로덕션 환경 (.env)
```env
# 프로덕션 Firebase 설정 (현재 설정 유지)
FIREBASE_PROJECT_ID=mx-olulo
FIREBASE_CLIENT_EMAIL=firebase-adminsdk-fbsvc@mx-olulo.iam.gserviceaccount.com
FIREBASE_PRIVATE_KEY="[프로덕션 키]"
FIREBASE_WEB_API_KEY=AIzaSyCeKo15SRamxqP-xSO4Itjwoy945BMmd6w
FIREBASE_USE_EMULATOR=false
```

## 🔧 Laravel 통합 설정

### 1. Firebase 서비스 수정 (app/Services/Auth/FirebaseAuthService.php)
```php
public function __construct()
{
    $useEmulator = config('firebase.use_emulator', false);

    if ($useEmulator) {
        // Emulator용 설정
        $this->auth = Firebase::connect([
            'project_id' => config('firebase.project_id'),
            'use_emulator' => true,
            'emulator_host' => config('firebase.auth_emulator_host')
        ]);
    } else {
        // 프로덕션 설정 (현재 구현 유지)
        $this->auth = Firebase::connect([
            'project_id' => config('firebase.project_id'),
            'credentials' => [
                'type' => 'service_account',
                'client_email' => config('firebase.client_email'),
                'private_key' => config('firebase.private_key')
            ]
        ]);
    }
}
```

### 2. 환경변수 추가 (config/firebase.php)
```php
return [
    'project_id' => env('FIREBASE_PROJECT_ID'),
    'client_email' => env('FIREBASE_CLIENT_EMAIL'),
    'private_key' => env('FIREBASE_PRIVATE_KEY'),
    'web_api_key' => env('FIREBASE_WEB_API_KEY'),

    // Emulator 설정
    'use_emulator' => env('FIREBASE_USE_EMULATOR', false),
    'auth_emulator_host' => env('FIREBASE_AUTH_EMULATOR_HOST', '127.0.0.1:9099'),
    'firestore_emulator_host' => env('FIREBASE_FIRESTORE_EMULATOR_HOST', '127.0.0.1:8080'),
];
```

## 🧪 테스트 시나리오

### 1. Emulator 기동
```bash
# Emulator Suite 시작
firebase emulators:start

# 백그라운드 실행
firebase emulators:start --detach

# 특정 서비스만 실행
firebase emulators:start --only auth,firestore
```

### 2. 테스트 실행
```bash
# 테스트 환경 설정
cp .env.testing .env

# Laravel 테스트 실행
php artisan test --env=testing

# 특정 테스트 클래스 실행
php artisan test tests/Feature/Auth/FirebaseAuthTest.php
```

### 3. Emulator UI 접근
- **URL**: http://localhost:4000
- **Authentication**: http://localhost:4000/auth
- **Firestore**: http://localhost:4000/firestore

## 📋 개발 워크플로우

### 1. 로컬 개발 시작
```bash
# 1. PostgreSQL 시작
docker start olulo-postgres

# 2. Firebase Emulator 시작
firebase emulators:start --detach

# 3. Laravel 서버 시작
php artisan serve --port=8001

# 4. 프론트엔드 개발 서버 (추후)
npm run dev
```

### 2. 테스트 실행
```bash
# 1. Emulator가 실행 중인지 확인
firebase emulators:list

# 2. 테스트 환경 설정
export APP_ENV=testing

# 3. 테스트 실행
php artisan test --filter=Firebase
```

### 3. 환경 정리
```bash
# Emulator 중지
firebase emulators:stop

# PostgreSQL 중지 (필요 시)
docker stop olulo-postgres
```

## ⚠️ 주의사항

### 1. 환경 분리
- **절대 테스트 데이터를 프로덕션에 전송하지 않음**
- 환경변수 `FIREBASE_USE_EMULATOR`로 명확히 구분
- `.env.testing` 파일로 테스트 설정 분리

### 2. 포트 충돌 방지
- Auth Emulator: 9099
- Firestore Emulator: 8080
- UI: 4000
- Laravel: 8001
- PostgreSQL: 5432

### 3. 데이터 지속성
- Emulator 데이터는 재시작 시 초기화됨
- 필요 시 `--export-on-exit` 옵션으로 데이터 내보내기

## 🔄 Phase3 통합 계획

Phase3 인증 구현 시 다음 순서로 진행:

1. **Firebase Emulator Suite 설치 및 설정**
2. **테스트용 인증 플로우 구현**
3. **Emulator 환경에서 테스트**
4. **프로덕션 환경 검증**

## 📚 참고 문서

- [Firebase Emulator Suite 공식 문서](https://firebase.google.com/docs/emulator-suite)
- [Laravel Testing 가이드](https://laravel.com/docs/12.x/testing)
- [Firebase Admin SDK PHP](https://firebase-php.readthedocs.io/en/stable/)

---

**중요**: 이 문서의 내용은 Phase3 인증 구현 시점에 실제로 적용될 예정입니다.
**메모리 기록**: 테스트 환경에서는 Firebase Local Emulator Suite 사용
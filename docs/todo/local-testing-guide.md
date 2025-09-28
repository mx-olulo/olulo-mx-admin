# 로컬 개발 환경 테스트 가이드

## 🎯 현재 상태 (2025-09-27)

✅ **완료된 설정**
- Laravel 12.31.1 애플리케이션 기동
- Firebase Admin SDK 연결 성공
- 환경변수 설정 완료
- SQLite 로컬 데이터베이스 설정

## 🚀 로컬 서버 기동

### 1. 애플리케이션 서버 시작
```bash
cd /opt/GitHub/olulo-mx-admin
php artisan serve --port=8001
```

**서버 URL**: http://127.0.0.1:8001

### 2. 기본 동작 확인
```bash
# 메인 페이지 접근
curl http://127.0.0.1:8001/

# Laravel 버전 확인
php artisan --version
```

## 🔥 Firebase 테스트

### 1. Firebase 서비스 연결 확인
```bash
php artisan tinker --execute="
use App\Services\Auth\FirebaseAuthService;
try {
    \$service = app(FirebaseAuthService::class);
    echo 'Firebase 서비스 로드 성공: ' . get_class(\$service) . PHP_EOL;
    echo 'Firebase 프로젝트 ID: ' . config('firebase.project_id') . PHP_EOL;
} catch (Exception \$e) {
    echo 'Firebase 연결 오류: ' . \$e->getMessage() . PHP_EOL;
}
"
```

**예상 결과**:
```
Firebase 서비스 로드 성공: App\Services\Auth\FirebaseAuthService
Firebase 프로젝트 ID: mx-olulo
```

### 2. Firebase 설정 확인
```bash
# Firebase 환경변수 확인
php artisan tinker --execute="
echo 'FIREBASE_PROJECT_ID: ' . config('firebase.project_id') . PHP_EOL;
echo 'FIREBASE_CLIENT_EMAIL: ' . config('firebase.client_email') . PHP_EOL;
echo 'Private Key 설정됨: ' . (config('firebase.private_key') ? 'YES' : 'NO') . PHP_EOL;
"
```

## 🔐 Sanctum 인증 테스트

### 1. CSRF 쿠키 테스트
```bash
# CSRF 쿠키 요청
curl -X GET http://127.0.0.1:8001/sanctum/csrf-cookie -v
```

**주의**: 현재 라우팅 이슈로 인해 Laravel 환영 페이지가 반환됩니다.
이는 Phase3에서 인증 라우트 구현 시 해결될 예정입니다.

### 2. API 엔드포인트 테스트 (Phase3 완료 후)
```bash
# 1. CSRF 쿠키 획득
curl -X GET http://127.0.0.1:8001/sanctum/csrf-cookie -c cookies.txt

# 2. Firebase 로그인 (구현 예정)
curl -X POST http://127.0.0.1:8001/api/auth/firebase-login \
  -H "Content-Type: application/json" \
  -H "X-XSRF-TOKEN: {csrf-token}" \
  -b cookies.txt \
  -d '{"idToken": "firebase-id-token"}'

# 3. 보호된 엔드포인트 접근
curl -X GET http://127.0.0.1:8001/api/user \
  -H "Accept: application/json" \
  -b cookies.txt
```

## 💾 데이터베이스 테스트

### 1. 마이그레이션 상태 확인
```bash
php artisan migrate:status
```

### 2. 기본 테이블 확인
```bash
php artisan tinker --execute="
use Illuminate\Support\Facades\Schema;
\$tables = ['users', 'personal_access_tokens', 'sessions', 'cache'];
foreach (\$tables as \$table) {
    echo \$table . ': ' . (Schema::hasTable(\$table) ? 'EXISTS' : 'MISSING') . PHP_EOL;
}
"
```

### 3. 사용자 생성 테스트
```bash
php artisan tinker --execute="
\$user = App\Models\User::create([
    'name' => 'Test User',
    'email' => 'test@olulo.com.mx',
    'password' => bcrypt('password123'),
    'firebase_uid' => 'test-firebase-uid'
]);
echo '사용자 생성됨: ' . \$user->name . ' (' . \$user->email . ')' . PHP_EOL;
"
```

## 📊 시스템 상태 체크

### 1. 전체 시스템 상태
```bash
# 애플리케이션 상태
php artisan about

# 설정 캐시 상태
php artisan config:cache
php artisan route:cache
```

### 2. 환경변수 검증
```bash
# 중요 환경변수 확인
php artisan tinker --execute="
\$vars = ['APP_NAME', 'APP_ENV', 'DB_CONNECTION', 'FIREBASE_PROJECT_ID', 'SANCTUM_STATEFUL_DOMAINS'];
foreach (\$vars as \$var) {
    echo \$var . ': ' . (env(\$var) ?: 'NOT SET') . PHP_EOL;
}
"
```

## 🔧 개발 도구

### 1. Laravel Telescope (개발 모니터링)
```bash
# Telescope 대시보드 접근
# http://127.0.0.1:8001/telescope
```

### 2. 코드 품질 도구
```bash
# 코드 스타일 검사
vendor/bin/pint --test

# 정적 분석
vendor/bin/phpstan analyse

# 테스트 실행
php artisan test
```

## ⚠️ 알려진 이슈

### 1. Sanctum CSRF 라우팅 문제
- **증상**: `/sanctum/csrf-cookie` 요청 시 Laravel 환영 페이지 반환
- **원인**: Laravel 12에서 Sanctum 라우트 자동 로딩 이슈
- **해결 예정**: Phase3 인증 구현 시 전용 컨트롤러로 해결

### 2. CORS 설정 (프론트엔드 연동 시)
- **현재**: 기본 설정으로 로컬호스트만 허용
- **필요 시**: `config/cors.php`에서 추가 도메인 설정

## 🚀 다음 단계 (Phase3)

1. **Firebase 인증 컨트롤러 구현**
   - `/api/auth/firebase-login` 엔드포인트
   - `/api/auth/logout` 엔드포인트

2. **Sanctum 세션 관리**
   - CSRF 보호 구현
   - 세션 기반 인증 플로우

3. **사용자 모델 확장**
   - Firebase UID 매핑
   - 권한 관리 (Spatie Permission)

## 📞 문제 해결

### 일반적인 문제
1. **서버 포트 충돌**: 다른 포트 사용 (`--port=8002`)
2. **권한 문제**: `storage/` 디렉터리 권한 확인
3. **캐시 이슈**: `php artisan cache:clear` 실행

### 로그 확인
```bash
# Laravel 로그
tail -f storage/logs/laravel.log

# 웹서버 접근 로그 (serve 명령 시)
# 터미널에서 직접 확인 가능
```

---

**마지막 업데이트**: 2025-09-27
**다음 테스트**: Phase3 인증 구현 완료 후
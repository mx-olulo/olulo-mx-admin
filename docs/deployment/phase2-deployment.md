# Phase 2 배포 가이드 ✅ READ

작성일: 2025-10-01
작성자: Documentation Reviewer (Claude Agent)
상태: Phase 2 인증 시스템 배포

## 목적

Phase 2 인증 시스템을 개발(dev), 스테이징(staging), 프로덕션(production) 환경에 배포하기 위한 단계별 가이드를 제공합니다.

## 전제 조건

### 필수 소프트웨어

- PHP 8.3+
- Composer 2.7+
- PostgreSQL 15+
- Redis 7.0+
- Node.js 22+ (프론트엔드 빌드용)
- Git

### 인프라 요구사항

- HTTPS 지원 웹 서버 (Nginx 또는 Apache)
- SSL 인증서 (Let's Encrypt 또는 와일드카드 인증서)
- Redis 서버 (세션 및 캐시용)
- PostgreSQL 데이터베이스
- 충분한 메모리 (최소 2GB, 권장 4GB)

### 필수 계정 및 키

- Firebase 프로젝트 (환경별)
- Firebase 서비스 계정 키 (JSON)
- 도메인 네임서버 접근 권한
- SSL 인증서 발급 권한

---

## 1. 환경변수 설정 체크리스트

### 1.1 공통 환경변수

모든 환경에서 설정해야 하는 공통 환경변수:

```bash
# 애플리케이션 기본 설정
APP_NAME="Olulo MX Admin"
APP_KEY=base64:...  # php artisan key:generate로 생성
APP_TIMEZONE=America/Mexico_City
APP_LOCALE=es
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=es_MX

# 로그 설정
LOG_CHANNEL=stack
LOG_LEVEL=info  # 프로덕션에서는 info 또는 warning

# 세션 설정
SESSION_DRIVER=redis
SESSION_LIFETIME=120  # 분

# 캐시 설정
CACHE_STORE=redis
CACHE_PREFIX=olulo_mx

# 큐 설정
QUEUE_CONNECTION=redis

# Redis 설정
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null  # 프로덕션에서는 강력한 비밀번호 설정
REDIS_PORT=6379

# 멕시코 특화 설정
MEXICO_TAX_RATE=0.16
MEXICO_CURRENCY=MXN
TIMEZONE=America/Mexico_City

# 멀티테넌시 설정
MULTI_TENANT_ENABLED=true
```

### 1.2 개발(dev) 환경변수

```bash
APP_ENV=local
APP_DEBUG=true
APP_URL=https://admin.dev.olulo.com.mx

# 세션 도메인
SESSION_DOMAIN=.dev.olulo.com.mx

# Sanctum Stateful Domains
SANCTUM_STATEFUL_DOMAINS=localhost,admin.dev.olulo.com.mx,menu.dev.olulo.com.mx,mx-olulo.firebaseapp.com,mx-olulo.web.app

# PostgreSQL
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=olulo_dev
DB_USERNAME=olulo
DB_PASSWORD=dev_secret_password
DB_SSLMODE=prefer

# Firebase 개발 프로젝트
FIREBASE_PROJECT_ID=olulo-mx-admin-dev
FIREBASE_CLIENT_EMAIL=firebase-adminsdk@olulo-mx-admin-dev.iam.gserviceaccount.com
FIREBASE_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----\n...\n-----END PRIVATE KEY-----\n"
FIREBASE_WEB_API_KEY=AIza...
FIREBASE_AUTH_DOMAIN=olulo-mx-admin-dev.firebaseapp.com
FIREBASE_STORAGE_BUCKET=olulo-mx-admin-dev.appspot.com

# Firebase 보안 설정
FIREBASE_CHECK_REVOKED=true
FIREBASE_SESSION_LIFETIME=432000

# Firebase 에뮬레이터 (선택)
FIREBASE_USE_EMULATOR=false
FIREBASE_AUTH_EMULATOR_HOST=localhost:9099

# 기본 테넌트
DEFAULT_TENANT_CODE=demo
```

### 1.3 스테이징(staging) 환경변수

```bash
APP_ENV=staging
APP_DEBUG=false  # 스테이징에서는 false 권장
APP_URL=https://admin.demo.olulo.com.mx

# 세션 도메인
SESSION_DOMAIN=.demo.olulo.com.mx

# Sanctum Stateful Domains
SANCTUM_STATEFUL_DOMAINS=admin.demo.olulo.com.mx,menu.demo.olulo.com.mx,mx-olulo.firebaseapp.com,mx-olulo.web.app

# PostgreSQL (RDS 또는 클라우드 DB)
DB_CONNECTION=pgsql
DB_HOST=olulo-staging.xyz.rds.amazonaws.com
DB_PORT=5432
DB_DATABASE=olulo_staging
DB_USERNAME=olulo
DB_PASSWORD=staging_secret_password
DB_SSLMODE=require

# Firebase 스테이징 프로젝트
FIREBASE_PROJECT_ID=olulo-mx-admin-staging
FIREBASE_CLIENT_EMAIL=firebase-adminsdk@olulo-mx-admin-staging.iam.gserviceaccount.com
FIREBASE_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----\n...\n-----END PRIVATE KEY-----\n"
FIREBASE_WEB_API_KEY=AIza...
FIREBASE_AUTH_DOMAIN=olulo-mx-admin-staging.firebaseapp.com
FIREBASE_STORAGE_BUCKET=olulo-mx-admin-staging.appspot.com

# Firebase 보안 설정
FIREBASE_CHECK_REVOKED=true
FIREBASE_SESSION_LIFETIME=432000
FIREBASE_USE_EMULATOR=false

# 기본 테넌트
DEFAULT_TENANT_CODE=demo
```

### 1.4 프로덕션(production) 환경변수

```bash
APP_ENV=production
APP_DEBUG=false  # 반드시 false
APP_URL=https://admin.olulo.com.mx

# 세션 도메인
SESSION_DOMAIN=.olulo.com.mx

# Sanctum Stateful Domains
SANCTUM_STATEFUL_DOMAINS=admin.olulo.com.mx,menu.olulo.com.mx,mx-olulo.firebaseapp.com,mx-olulo.web.app

# PostgreSQL (프로덕션 RDS)
DB_CONNECTION=pgsql
DB_HOST=olulo-production.xyz.rds.amazonaws.com
DB_PORT=5432
DB_DATABASE=olulo
DB_USERNAME=olulo
DB_PASSWORD=strong_production_password
DB_SSLMODE=require

# Firebase 프로덕션 프로젝트
FIREBASE_PROJECT_ID=olulo-mx-admin
FIREBASE_CLIENT_EMAIL=firebase-adminsdk@olulo-mx-admin.iam.gserviceaccount.com
FIREBASE_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----\n...\n-----END PRIVATE KEY-----\n"
FIREBASE_WEB_API_KEY=AIza...
FIREBASE_AUTH_DOMAIN=olulo-mx-admin.firebaseapp.com
FIREBASE_STORAGE_BUCKET=olulo-mx-admin.appspot.com

# Firebase 보안 설정
FIREBASE_CHECK_REVOKED=true
FIREBASE_SESSION_LIFETIME=432000
FIREBASE_USE_EMULATOR=false

# 기본 테넌트
DEFAULT_TENANT_CODE=flagship

# 메일 설정 (프로덕션)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@olulo.com.mx"
MAIL_FROM_NAME="${APP_NAME}"
```

---

## 2. Firebase 서비스 계정 키 관리

### 2.1 Firebase 콘솔에서 서비스 계정 키 생성

1. **Firebase Console** 접속: [https://console.firebase.google.com](https://console.firebase.google.com)
2. 프로젝트 선택 (환경별)
3. **프로젝트 설정** → **서비스 계정** 탭 이동
4. **새 비공개 키 생성** 클릭
5. JSON 파일 다운로드 (안전한 위치에 저장)

### 2.2 JSON 키를 환경변수로 변환

다운로드한 JSON 파일에서 필요한 값 추출:

```json
{
  "type": "service_account",
  "project_id": "olulo-mx-admin",
  "private_key_id": "abc123...",
  "private_key": "-----BEGIN PRIVATE KEY-----\n...\n-----END PRIVATE KEY-----\n",
  "client_email": "firebase-adminsdk@olulo-mx-admin.iam.gserviceaccount.com",
  "client_id": "123456789",
  ...
}
```

환경변수로 변환:

```bash
FIREBASE_PROJECT_ID=olulo-mx-admin
FIREBASE_CLIENT_EMAIL=firebase-adminsdk@olulo-mx-admin.iam.gserviceaccount.com
FIREBASE_CLIENT_ID=123456789
FIREBASE_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----\n...\n-----END PRIVATE KEY-----\n"
FIREBASE_PRIVATE_KEY_ID=abc123...
```

### 2.3 보안 권장사항

- ⚠️ **절대 Git에 커밋하지 말 것**: `.gitignore`에 `.env` 및 `*-firebase-adminsdk-*.json` 포함
- 🔐 **암호화 저장소 사용**: AWS Secrets Manager, HashiCorp Vault 등
- 🔄 **정기적인 키 로테이션**: 90일마다 새 키 생성 및 구 키 폐기
- 👥 **최소 권한 원칙**: IAM 권한을 필요한 최소한으로 제한

---

## 3. CORS 도메인 설정 (환경별)

### 3.1 개발(dev)

`config/cors.php` (또는 환경변수):

```php
'allowed_origins' => [
    'http://localhost',
    'http://localhost:3000',
    'http://localhost:8000',
    'http://admin.localhost',
    'http://menu.localhost',
    'https://admin.dev.olulo.com.mx',
    'https://menu.dev.olulo.com.mx',
    'https://mx-olulo.firebaseapp.com',
    'https://mx-olulo.web.app',
],
```

### 3.2 스테이징(staging)

```php
'allowed_origins' => [
    'https://admin.demo.olulo.com.mx',
    'https://menu.demo.olulo.com.mx',
    'https://mx-olulo.firebaseapp.com',
    'https://mx-olulo.web.app',
],
```

### 3.3 프로덕션(production)

```php
'allowed_origins' => [
    'https://admin.olulo.com.mx',
    'https://menu.olulo.com.mx',
    'https://mx-olulo.firebaseapp.com',
    'https://mx-olulo.web.app',
],

'allowed_origins_patterns' => [
    '#^https://[\w\-]+\.olulo\.com\.mx$#',  // 서브도메인 와일드카드
],
```

### 3.4 CORS 설정 검증

```bash
# 개발 환경에서 테스트
curl -H "Origin: https://admin.dev.olulo.com.mx" \
  -H "Access-Control-Request-Method: POST" \
  -H "Access-Control-Request-Headers: X-XSRF-TOKEN" \
  -X OPTIONS \
  https://admin.dev.olulo.com.mx/api/auth/firebase-login -v

# 응답 헤더 확인
# Access-Control-Allow-Origin: https://admin.dev.olulo.com.mx
# Access-Control-Allow-Credentials: true
```

---

## 4. Sanctum Stateful Domains 설정

### 4.1 환경별 도메인 설정

#### 개발(dev)

```bash
SANCTUM_STATEFUL_DOMAINS=localhost,admin.dev.olulo.com.mx,menu.dev.olulo.com.mx,mx-olulo.firebaseapp.com,mx-olulo.web.app
```

#### 스테이징(staging)

```bash
SANCTUM_STATEFUL_DOMAINS=admin.demo.olulo.com.mx,menu.demo.olulo.com.mx,mx-olulo.firebaseapp.com,mx-olulo.web.app
```

#### 프로덕션(production)

```bash
SANCTUM_STATEFUL_DOMAINS=admin.olulo.com.mx,menu.olulo.com.mx,mx-olulo.firebaseapp.com,mx-olulo.web.app
```

### 4.2 주의사항

- 포트 번호 제거 (프로덕션)
- 쉼표로 구분 (공백 없이)
- 프로토콜(https://) 제외
- 모든 프론트엔드 도메인 포함

---

## 5. 세션 드라이버 설정 (Redis)

### 5.1 Redis 설치 (Ubuntu/Debian)

```bash
sudo apt update
sudo apt install redis-server -y
sudo systemctl enable redis-server
sudo systemctl start redis-server
```

### 5.2 Redis 보안 설정

`/etc/redis/redis.conf` 편집:

```conf
# 비밀번호 설정 (프로덕션 필수)
requirepass your_strong_redis_password

# 외부 접근 차단 (로컬 전용)
bind 127.0.0.1 ::1

# 백그라운드 저장 설정
save 900 1
save 300 10
save 60 10000

# 최대 메모리 설정
maxmemory 256mb
maxmemory-policy allkeys-lru
```

Redis 재시작:

```bash
sudo systemctl restart redis-server
```

### 5.3 Laravel 환경변수 설정

```bash
SESSION_DRIVER=redis
CACHE_STORE=redis
QUEUE_CONNECTION=redis

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=your_strong_redis_password
REDIS_PORT=6379
```

### 5.4 Redis 연결 테스트

```bash
php artisan tinker

# Redis 연결 테스트
>>> Redis::set('test', 'value');
>>> Redis::get('test');
=> "value"
```

---

## 6. 프로덕션 최적화

### 6.1 설정 캐싱

프로덕션 환경에서는 설정을 캐싱하여 성능을 향상시킵니다:

```bash
# 설정 캐시 생성
php artisan config:cache

# 라우트 캐시 생성
php artisan route:cache

# 뷰 캐시 생성 (Blade 템플릿)
php artisan view:cache

# 이벤트 캐시 생성
php artisan event:cache
```

### 6.2 캐시 초기화 (배포 시)

새로운 코드 배포 후 캐시를 초기화해야 합니다:

```bash
# 모든 캐시 초기화
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear

# Composer 오토로드 최적화
composer install --optimize-autoloader --no-dev

# 설정 재캐싱
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

### 6.3 OPcache 설정 (PHP)

`/etc/php/8.3/fpm/php.ini` 편집:

```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.validate_timestamps=0  # 프로덕션에서는 0
opcache.save_comments=1
opcache.fast_shutdown=1
```

PHP-FPM 재시작:

```bash
sudo systemctl restart php8.3-fpm
```

---

## 7. 배포 체크리스트

### 7.1 배포 전 체크리스트

- [ ] 모든 환경변수가 올바르게 설정되었는지 확인
- [ ] Firebase 서비스 계정 키가 안전하게 저장되었는지 확인
- [ ] CORS 도메인이 환경에 맞게 설정되었는지 확인
- [ ] Sanctum Stateful Domains가 올바르게 설정되었는지 확인
- [ ] Redis가 정상 동작하는지 확인
- [ ] PostgreSQL 연결이 정상인지 확인
- [ ] SSL 인증서가 유효한지 확인
- [ ] 데이터베이스 마이그레이션이 준비되었는지 확인
- [ ] `APP_DEBUG=false` 확인 (스테이징/프로덕션)
- [ ] `APP_ENV`가 올바른 환경으로 설정되었는지 확인

### 7.2 배포 중 체크리스트

- [ ] Git 저장소에서 최신 코드 풀
- [ ] Composer 의존성 설치: `composer install --no-dev --optimize-autoloader`
- [ ] NPM 의존성 설치 및 빌드: `npm ci && npm run build`
- [ ] 데이터베이스 마이그레이션 실행: `php artisan migrate --force`
- [ ] 캐시 초기화 및 재생성 (위 6.2 참조)
- [ ] 큐 워커 재시작 (필요 시): `php artisan queue:restart`
- [ ] 파일 권한 설정: `storage/`, `bootstrap/cache/` 쓰기 권한

### 7.3 배포 후 체크리스트

- [ ] 애플리케이션 상태 확인: `curl https://admin.olulo.com.mx/up`
- [ ] CSRF 쿠키 엔드포인트 테스트: `/sanctum/csrf-cookie`
- [ ] Firebase 로그인 테스트: `/api/auth/firebase-login`
- [ ] 로그아웃 테스트: `/api/auth/logout`
- [ ] 다중 서브도메인 세션 공유 테스트
- [ ] 에러 로그 확인: `tail -f storage/logs/laravel.log`
- [ ] 성능 모니터링 (응답 시간, 메모리 사용량)

---

## 8. 배포 자동화 스크립트

### 8.1 배포 스크립트 예시 (deploy.sh)

```bash
#!/bin/bash

# Phase 2 배포 스크립트
# 사용법: ./deploy.sh [dev|staging|production]

set -e  # 에러 발생 시 중단

ENV=$1

if [ -z "$ENV" ]; then
  echo "사용법: ./deploy.sh [dev|staging|production]"
  exit 1
fi

echo "🚀 Phase 2 배포 시작 - 환경: $ENV"

# 1. 최신 코드 가져오기
echo "📦 Git 저장소에서 최신 코드 가져오는 중..."
git pull origin main

# 2. Composer 의존성 설치
echo "📚 Composer 의존성 설치 중..."
composer install --no-dev --optimize-autoloader --no-interaction

# 3. NPM 의존성 설치 및 빌드
echo "🎨 프론트엔드 빌드 중..."
npm ci
npm run build

# 4. 캐시 초기화
echo "🧹 캐시 초기화 중..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear

# 5. 데이터베이스 마이그레이션
echo "🗄️ 데이터베이스 마이그레이션 실행 중..."
php artisan migrate --force

# 6. 설정 캐싱 (프로덕션)
if [ "$ENV" == "production" ] || [ "$ENV" == "staging" ]; then
  echo "⚡ 설정 캐싱 중..."
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
  php artisan event:cache
fi

# 7. 파일 권한 설정
echo "🔒 파일 권한 설정 중..."
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# 8. 큐 워커 재시작 (필요 시)
if command -v supervisorctl &> /dev/null; then
  echo "♻️ 큐 워커 재시작 중..."
  php artisan queue:restart
fi

# 9. 웹 서버 재시작
echo "🔄 웹 서버 재시작 중..."
sudo systemctl reload nginx
sudo systemctl restart php8.3-fpm

echo "✅ Phase 2 배포 완료!"
echo "🌐 애플리케이션 URL: https://admin.$ENV.olulo.com.mx"
```

### 8.2 스크립트 실행 권한 부여

```bash
chmod +x deploy.sh
```

### 8.3 배포 실행

```bash
# 개발 환경 배포
./deploy.sh dev

# 스테이징 환경 배포
./deploy.sh staging

# 프로덕션 환경 배포
./deploy.sh production
```

---

## 9. 트러블슈팅

### 9.1 세션이 유지되지 않는 경우

**증상**: 로그인 후 즉시 로그아웃되거나 인증 상태가 유지되지 않음

**해결 방법**:
1. `SESSION_DOMAIN` 확인: `.dev.olulo.com.mx` 형식으로 설정
2. `SANCTUM_STATEFUL_DOMAINS` 확인: 모든 프론트엔드 도메인 포함
3. CORS 설정 확인: `supports_credentials: true`
4. Redis 연결 확인: `php artisan tinker` → `Redis::ping()`

### 9.2 CORS 에러 발생

**증상**: 브라우저 콘솔에 CORS 에러 표시

**해결 방법**:
1. `config/cors.php`에서 `allowed_origins`에 프론트엔드 도메인 추가
2. `supports_credentials: true` 설정 확인
3. 프론트엔드에서 `withCredentials: true` 옵션 사용 확인
4. 웹 서버(Nginx) CORS 설정 중복 제거

### 9.3 Firebase 토큰 검증 실패

**증상**: "유효하지 않은 Firebase 토큰입니다." 에러

**해결 방법**:
1. 서버 시간 동기화 확인: `timedatectl`
2. Firebase 프로젝트 ID 확인: `FIREBASE_PROJECT_ID`
3. 서비스 계정 키 확인: JSON 형식이 올바른지
4. Firebase 콘솔에서 승인된 도메인 확인

### 9.4 Redis 연결 실패

**증상**: "Connection refused" 에러

**해결 방법**:
1. Redis 서버 상태 확인: `sudo systemctl status redis-server`
2. Redis 비밀번호 확인: `REDIS_PASSWORD`
3. 포트 확인: `REDIS_PORT=6379`
4. 방화벽 설정 확인

### 9.5 500 Internal Server Error

**증상**: 페이지 로드 시 500 에러 발생

**해결 방법**:
1. 로그 확인: `tail -f storage/logs/laravel.log`
2. `APP_DEBUG=true` 설정 후 상세 에러 메시지 확인 (개발 환경)
3. 파일 권한 확인: `storage/`, `bootstrap/cache/` 쓰기 권한
4. 캐시 초기화: `php artisan cache:clear`

---

## 10. 모니터링 및 유지보수

### 10.1 로그 모니터링

```bash
# 실시간 로그 확인
tail -f storage/logs/laravel.log

# 에러만 필터링
tail -f storage/logs/laravel.log | grep ERROR

# 최근 100줄 확인
tail -n 100 storage/logs/laravel.log
```

### 10.2 성능 모니터링

Laravel Telescope 설치 (개발/스테이징 환경):

```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

### 10.3 정기 유지보수 작업

#### 주간 작업

- [ ] 로그 파일 크기 확인 및 로테이션
- [ ] 세션 정리: `php artisan session:clear`
- [ ] 캐시 정리: `php artisan cache:clear`

#### 월간 작업

- [ ] 데이터베이스 백업 검증
- [ ] SSL 인증서 만료일 확인
- [ ] 의존성 보안 업데이트: `composer audit`
- [ ] Firebase 서비스 계정 키 로테이션 검토

#### 분기별 작업

- [ ] 전체 시스템 보안 감사
- [ ] 성능 벤치마크 및 최적화
- [ ] 사용하지 않는 리소스 정리
- [ ] 문서 업데이트

---

## 11. 롤백 절차

배포 후 문제가 발생한 경우 이전 버전으로 롤백:

### 11.1 Git 롤백

```bash
# 이전 커밋으로 롤백
git log --oneline  # 커밋 해시 확인
git checkout {commit_hash}

# 의존성 재설치
composer install --no-dev --optimize-autoloader

# 캐시 재생성
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 웹 서버 재시작
sudo systemctl reload nginx
```

### 11.2 데이터베이스 롤백

```bash
# 마지막 마이그레이션 롤백
php artisan migrate:rollback

# 특정 단계만큼 롤백
php artisan migrate:rollback --step=2

# 전체 롤백 (주의!)
php artisan migrate:reset
```

### 11.3 긴급 롤백 체크리스트

- [ ] 사용자에게 점검 공지
- [ ] 현재 상태 백업
- [ ] Git 롤백 실행
- [ ] 데이터베이스 롤백 (필요 시)
- [ ] 캐시 재생성
- [ ] 기능 테스트
- [ ] 사용자에게 복구 완료 공지

---

## 12. CI/CD 통합 (선택)

### 12.1 GitHub Actions 워크플로우 예시

`.github/workflows/deploy.yml`:

```yaml
name: Deploy Phase 2

on:
  push:
    branches:
      - main

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: pgsql, redis, gd

      - name: Install Composer dependencies
        run: composer install --no-dev --optimize-autoloader

      - name: Run tests
        run: php artisan test

      - name: Deploy to server
        uses: appleboy/ssh-action@master
        with:
          host: ${{ secrets.SERVER_HOST }}
          username: ${{ secrets.SERVER_USERNAME }}
          key: ${{ secrets.SSH_PRIVATE_KEY }}
          script: |
            cd /var/www/olulo-mx-admin
            ./deploy.sh production
```

### 12.2 필요한 GitHub Secrets

- `SERVER_HOST`: 서버 IP 또는 도메인
- `SERVER_USERNAME`: SSH 사용자명
- `SSH_PRIVATE_KEY`: SSH 개인 키

---

## 관련 문서

- [보안 체크리스트](../security/phase2-checklist.md)
- [API 엔드포인트 문서](../api/auth-endpoints.md)
- [환경 구성](../devops/environments.md)
- [인증 설계](../auth.md)
- [Phase 2 완료도 평가 보고서](../milestones/phase2-completion-report.md)

## 버전 이력

| 버전 | 날짜 | 작성자 | 변경 내역 |
|------|------|--------|----------|
| 1.0 | 2025-10-01 | Documentation Reviewer | 초기 작성 |

## 지원 및 문의

배포 중 문제가 발생하면 다음을 참조하세요:

1. 트러블슈팅 섹션 (9번)
2. [보안 체크리스트](../security/phase2-checklist.md)
3. 프로젝트 이슈 트래커

긴급한 기술 지원이 필요한 경우 프로젝트 리드에게 문의하세요.

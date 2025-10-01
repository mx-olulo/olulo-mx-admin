# Nginx 설정 파일

이 디렉토리는 프로젝트의 nginx 설정 파일을 관리합니다.

## 파일 구조

- `admin.stage.olulo.com.mx.conf` - 스테이징 환경 nginx 설정
- `admin.olulo.com.mx.conf` - 프로덕션 환경 nginx 설정 (예정)

## 배포 방법

### 스테이징 환경

```bash
# 1. 설정 파일 복사
sudo cp deployment/nginx/admin.stage.olulo.com.mx.conf /etc/nginx/sites-available/admin.stage.olulo.com.mx

# 2. 심볼릭 링크 생성 (처음 한 번만)
sudo ln -sf /etc/nginx/sites-available/admin.stage.olulo.com.mx /etc/nginx/sites-enabled/

# 3. 설정 검증
sudo nginx -t

# 4. nginx 재시작
sudo systemctl reload nginx
```

### 프로덕션 환경

```bash
# 1. 설정 파일 복사
sudo cp deployment/nginx/admin.olulo.com.mx.conf /etc/nginx/sites-available/admin.olulo.com.mx

# 2. 심볼릭 링크 생성 (처음 한 번만)
sudo ln -sf /etc/nginx/sites-available/admin.olulo.com.mx /etc/nginx/sites-enabled/

# 3. 설정 검증
sudo nginx -t

# 4. nginx 재시작
sudo systemctl reload nginx
```

## 주의사항

- `/etc/nginx/sites-available/`의 실제 설정 파일을 직접 수정하지 말 것
- 이 디렉토리의 파일을 수정한 후 배포 스크립트를 통해 적용할 것
- SSL 인증서 경로는 환경별로 다를 수 있으므로 배포 시 확인 필요

## 주요 설정

### Firebase Auth 프록시

`/__/auth/` 경로는 Laravel 라우트로 전달되어 Firebase 인증 핸들러를 프록시합니다.
이는 Laravel Cloud 환경에서도 작동하도록 설계되었습니다.

### PHP-FPM

- PHP 8.4 사용
- Unix 소켓: `/var/run/php/php8.4-fpm.sock`

### 정적 파일 캐싱

- 이미지, CSS, JS 파일: 1년 캐싱
- `/__/auth/` 경로는 정적 파일 캐싱에서 제외 (Laravel 라우트로 처리)

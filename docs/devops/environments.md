# 환경 구성 — 도메인/쿠키/CORS (dev/staging/prod) ✅ READ

본 문서는 동일 루트 도메인 전략 하에서 개발/스테이징/프로덕션 환경의 도메인 및 인증 관련 설정 예시를 제공합니다. (Firebase + Sanctum SPA 세션 기준)

## 공통 원칙
- HTTPS 필수, Secure 쿠키, SameSite=Lax
- `SESSION_DOMAIN`은 상위 도메인으로 설정(예: `.dev.olulo.com.mx`)
- `SANCTUM_STATEFUL_DOMAINS`에 고객/관리자 서브도메인 모두 포함
- CORS는 각 환경의 고객/관리자 도메인을 모두 허용(allow credentials: true)

## 개발(dev)
- 관리자: `admin.dev.olulo.com.mx`
- 고객: `menu.dev.olulo.com.mx`

.env 예시
```
APP_URL=https://admin.dev.olulo.com.mx
SESSION_DOMAIN=.dev.olulo.com.mx
SANCTUM_STATEFUL_DOMAINS=admin.dev.olulo.com.mx,menu.dev.olulo.com.mx

SESSION_DRIVER=redis

# PostgreSQL
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=olulo_dev
DB_USERNAME=olulo
DB_PASSWORD=secret
DB_SSLMODE=prefer

# Firebase (예시; 실제 값으로 교체)
FIREBASE_PROJECT_ID=...
FIREBASE_CLIENT_EMAIL=...
FIREBASE_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----\n...\n-----END PRIVATE KEY-----\n"
FIREBASE_WEB_API_KEY=...
```

CORS 예시(요지)
- Allowed origins: `https://admin.dev.olulo.com.mx`, `https://menu.dev.olulo.com.mx`
- Allow credentials: true
- Allowed headers: `X-Requested-With, Content-Type, X-XSRF-TOKEN, Authorization`

## 스테이징(staging)
- 관리자: `admin.demo.olulo.com.mx`
- 고객: `menu.demo.olulo.com.mx`

.env 예시
```
APP_URL=https://admin.demo.olulo.com.mx
SESSION_DOMAIN=.demo.olulo.com.mx
SANCTUM_STATEFUL_DOMAINS=admin.demo.olulo.com.mx,menu.demo.olulo.com.mx
SESSION_DRIVER=redis

# PostgreSQL
DB_CONNECTION=pgsql
DB_HOST=<staging-rds-host>
DB_PORT=5432
DB_DATABASE=olulo_demo
DB_USERNAME=olulo
DB_PASSWORD=<staging-secret>
DB_SSLMODE=require
```

CORS 예시(요지)
- Allowed origins: `https://admin.demo.olulo.com.mx`, `https://menu.demo.olulo.com.mx`
- Allow credentials: true

## 프로덕션(prod)
- 관리자: `admin.olulo.com.mx`
- 고객: `menu.olulo.com.mx`

.env 예시
```
APP_URL=https://admin.olulo.com.mx
SESSION_DOMAIN=.olulo.com.mx
SANCTUM_STATEFUL_DOMAINS=admin.olulo.com.mx,menu.olulo.com.mx
SESSION_DRIVER=redis

# PostgreSQL
DB_CONNECTION=pgsql
DB_HOST=<prod-rds-host>
DB_PORT=5432
DB_DATABASE=olulo
DB_USERNAME=olulo
DB_PASSWORD=<prod-secret>
DB_SSLMODE=require
```

CORS 예시(요지)
- Allowed origins: `https://admin.olulo.com.mx`, `https://menu.olulo.com.mx`
- Allow credentials: true

## 라우팅/호스트 해석 가이드(요지)
- 호스트 기반 테넌시 미들웨어: `request()->getHost()`에서 서브도메인 파싱 → `stores.code` 매핑 → `tenant(store_id)` 컨텍스트 주입
- 고객 QR 경로: `https://menu.<env>.olulo.com.mx/c?...` → 토큰 검증 후 `https://menu.<env>.olulo.com.mx/app?...`로 리다이렉트 유지(동일 호스트)
- 관리자(Filament/Nova): `https://admin.<env>.olulo.com.mx` 고정, `/sanctum/csrf-cookie` 및 `/api/auth/firebase-login` 동일 호스트에서 처리

## 체크리스트
- DNS/SSL: 위 도메인 발급/와일드카드 인증서 검토(`*.dev.olulo.com.mx`, `*.demo.olulo.com.mx`, `*.olulo.com.mx`)
- 쿠키 도메인: 환경별 상위 도메인 값 정확히 설정
- CORS: 각 환경의 두 서브도메인 모두 허용 + credentials: true
- React 빌드 origin이 CORS 허용 목록과 일치하는지 확인
 - PostgreSQL 커넥션: 포트(5432), SSL 모드(`prefer`/`require`) 및 시크릿 값 검증

## 관련 문서
- 화이트페이퍼: `../whitepaper.md`
- 인증 설계: `../auth.md`
- 프로젝트 1: `../milestones/project-1.md`

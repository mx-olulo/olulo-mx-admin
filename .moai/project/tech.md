---
id: TECH-001
version: 0.2.0
status: active
created: 2025-10-01
updated: 2025-10-19
author: @Goos
priority: high
category: feature
labels:
  - php
  - typescript
  - laravel
  - react
  - quality
---

# olulo-mx-admin Technology Stack

## HISTORY

### v0.2.0 (2025-10-19)
- **UPDATED**: 템플릿 기본값을 실제 기술 스택으로 전면 갱신
- **AUTHOR**: @Goos
- **SECTIONS**: Stack, Framework, Quality, Security, Deploy 모두 실제 내용 반영
- **REASON**: 레거시 프로젝트 도입으로 MoAI-ADK 초기화 작업, 듀얼 언어 스택 (PHP + TypeScript) 명시

### v0.1.1 (2025-10-17)
- **UPDATED**: 템플릿 버전 동기화 (v0.3.8)
- **AUTHOR**: @Alfred
- **SECTIONS**: 메타데이터 표준화 (author 필드 단수형, priority 추가)

### v0.1.0 (2025-10-01)
- **INITIAL**: 프로젝트 기술 스택 문서 작성
- **AUTHOR**: @tech-lead
- **SECTIONS**: Stack, Framework, Quality, Security, Deploy

---

## @DOC:STACK-001 언어 & 런타임

### 듀얼 언어 스택

이 프로젝트는 **PHP (Backend)** + **TypeScript (Frontend)**의 듀얼 언어 스택을 채택합니다.

#### PHP (Backend)

- **언어**: PHP
- **버전**: >=8.2 (권장: 8.4+)
- **선택 이유**:
  - Laravel 12 프레임워크 공식 지원
  - Filament 4 및 Nova 5 요구사항
  - 강력한 타입 시스템 (declare(strict_types=1) 적용)
  - Composer 패키지 생태계
- **패키지 매니저**: Composer 2.x
- **런타임**: PHP-FPM (프로덕션), php artisan serve (로컬)

#### TypeScript (Frontend)

- **언어**: TypeScript
- **버전**: 5.9.x
- **선택 이유**:
  - React 19.1 최적 타입 안전성
  - 컴파일 타임 에러 감지
  - ESLint + TypeScript ESLint 통합
  - Vite 네이티브 지원
- **패키지 매니저**: npm (또는 pnpm)
- **런타임**: Node.js 20+ (Vite 요구사항)

### 멀티 플랫폼 지원

| 플랫폼 | Backend (PHP) | Frontend (Node.js) | 검증 도구 | 주요 제약 |
|--------|---------------|-------------------|-----------|-----------|
| **macOS** | ✅ 완전 지원 | ✅ 완전 지원 | Laravel Valet, Homebrew | - |
| **Linux** | ✅ 완전 지원 | ✅ 완전 지원 | Docker, systemd | - |
| **Windows** | ⚠️ WSL2 권장 | ✅ 완전 지원 | WSL2 + Docker Desktop | 심볼릭 링크 이슈 (storage/) |

**프로덕션 환경**: Ubuntu 22.04+ (권장)

## @DOC:FRAMEWORK-001 핵심 프레임워크 & 라이브러리

### 1. Backend 주요 의존성 (PHP)

**Laravel 생태계**:
- laravel/framework: ^12.0 (Core Framework)
- laravel/sanctum: ^4.0 (SPA Authentication)
- laravel/nova: ^5.0 (Master Admin)
- laravel/scout: ^10.19 (Full-text Search)
- laravel/tinker: ^2.10.1 (REPL)
- tightenco/ziggy: ^2.6 (Laravel Routes → JavaScript)

**Filament 생태계**:
- filament/filament: ^4.0 (Admin Panel Framework)

**Firebase 통합**:
- kreait/firebase-php: ^7.22 (Firebase Admin SDK)

**Spatie 패키지**:
- spatie/laravel-permission: ^6.10 (Role & Permission)
- spatie/laravel-activitylog: ^4.10 (Activity Logging)
- spatie/laravel-medialibrary: ^11.10 (Media Management)

**Inertia.js**:
- inertiajs/inertia-laravel: ^2.0 (Laravel-React Bridge)

### 2. Frontend 주요 의존성 (TypeScript/React)

**React 생태계**:
- react: ^19.1.1 (UI Library)
- react-dom: ^19.1.1 (DOM Renderer)
- @inertiajs/react: ^2.2.4 (Inertia.js React Adapter)

**Firebase 클라이언트**:
- firebase: ^10.14.1 (Firebase SDK)
- firebaseui: ^6.1.0 (Firebase UI Components)

**UI/UX**:
- tailwindcss: ^4.0.0 (Utility-first CSS)
- @tailwindcss/vite: ^4.0.0 (Tailwind Vite Plugin)
- lucide-react: ^0.544.0 (Icon Library)

**빌드 도구**:
- vite: ^7.0.4 (Build Tool & Dev Server)
- @vitejs/plugin-react: ^5.0.4 (React Plugin for Vite)
- laravel-vite-plugin: ^2.0.0 (Laravel Integration)

**HTTP 클라이언트**:
- axios: ^1.11.0 (Promise-based HTTP Client)

### 3. 개발 도구 (DevDependencies)

**PHP 품질 도구**:
- pestphp/pest: ^3.8 (테스트 프레임워크)
- pestphp/pest-plugin-laravel: ^3.2 (Laravel 통합)
- phpunit/phpunit: ^11.5.3 (테스트 러너)
- laravel/pint: ^1.24 (코드 포매터)
- larastan/larastan: ^3.7 (정적 분석, PHPStan Level 8)
- rector/rector: ^2.2 (자동 리팩토링)
- driftingly/rector-laravel: ^2.0 (Laravel 전용 Rector 룰)

**TypeScript 품질 도구**:
- typescript: ^5.9.3 (TypeScript 컴파일러)
- eslint: ^9.36.0 (린터)
- eslint-plugin-react: ^7.37.5 (React 린트 룰)
- eslint-plugin-react-hooks: ^6.1.0 (React Hooks 린트 룰)
- @typescript-eslint/eslint-plugin: ^8.45.0 (TypeScript 린트 룰)
- @typescript-eslint/parser: ^8.45.0 (TypeScript 파서)

**타입 정의**:
- @types/react: ^19.1.17 (React 타입)
- @types/react-dom: ^19.1.11 (React DOM 타입)

**Laravel 디버깅**:
- laravel/telescope: ^5.2 (Debug Assistant)
- barryvdh/laravel-debugbar: ^3.13 (Debug Bar)
- laravel/pail: ^1.2.2 (Log Viewer)

**Laravel 개발**:
- laravel/sail: ^1.41 (Docker 기반 로컬 환경)
- laravel/dusk: ^8.3 (E2E 브라우저 테스트)
- laravel/nova-devtool: ^1.8 (Nova 개발 도구)
- laravel/boost: * (성능 최적화)

**Faker**:
- fakerphp/faker: ^1.23 (테스트 데이터 생성)

**Mockery**:
- mockery/mockery: ^1.6 (Mocking Library)

**Collision**:
- nunomaduro/collision: ^8.6 (에러 핸들링 개선)

**Concurrently**:
- concurrently: ^9.0.1 (병렬 프로세스 실행)

### 4. 빌드 시스템

**Backend 빌드 (PHP)**:
- **빌드 도구**: Composer (autoload 최적화)
- **타겟**: PHP 8.2+ 런타임
- **성능 목표**: composer install < 2분 (캐시 없음)

**Frontend 빌드 (TypeScript/React)**:
- **빌드 도구**: Vite 7
- **번들링**: Rollup (Vite 내장)
- **타겟**: ES2020, 모던 브라우저 (Chrome 90+, Firefox 88+, Safari 14+)
- **성능 목표**:
  - 개발 서버 시작: < 1초
  - HMR (Hot Module Replacement): < 100ms
  - 프로덕션 빌드: < 30초

## @DOC:QUALITY-001 품질 게이트 & 정책

### 테스트 커버리지

**PHP (Backend)**:
- **목표**: 85% 커버리지
- **측정 도구**: Pest (PHPUnit 기반)
- **실패 시 대응**:
  - 커버리지 < 85%: PR 블록
  - 신규 코드: 90% 커버리지 필수

**TypeScript (Frontend)**:
- **목표**: 80% 커버리지 (향후 85%)
- **측정 도구**: Vitest 또는 Jest (향후 도입)
- **실패 시 대응**: 현재 미설정, MoAI-ADK 도입 후 설정 예정

### 정적 분석

| 언어 | 도구 | 역할 | 설정 파일 | 실패 시 조치 |
|------|------|------|-----------|--------------|
| **PHP** | **Laravel Pint** | 코드 포매터 (PSR-12) | 없음 (기본 규칙) | `composer pint` 자동 수정 |
| **PHP** | **Larastan** | 정적 분석 (PHPStan Level 8) | `phpstan.neon` | 타입 에러 수정 필수 |
| **PHP** | **Rector** | 자동 리팩토링 | `rector.php` (예정) | 제안 검토 후 적용 |
| **TypeScript** | **ESLint** | 린터 (React + TypeScript) | `eslint.config.js` (예정) | `npm run lint:fix` 자동 수정 |
| **TypeScript** | **TypeScript Compiler** | 타입 체크 | `tsconfig.json` | 타입 에러 수정 필수 |

### 자동화 스크립트

**Backend (PHP)**:

composer test                    # Pest 테스트 실행 (Feature + Unit)
composer pest:parallel           # 병렬 테스트 실행
composer pest:coverage           # 커버리지 측정
composer pint                    # 코드 포매팅 (자동 수정)
composer pint:check              # 포매팅 검증 (Dry-run)
composer phpstan                 # 정적 분석 (Level 8)
composer rector                  # 자동 리팩토링 (적용)
composer rector:check            # 리팩토링 제안 (Dry-run)
composer quality                 # 전체 품질 검증 (rector + pint + phpstan)
composer quality:check           # 전체 품질 검증 (Dry-run, CI용)
composer quality:fix             # 전체 품질 자동 수정
composer dev                     # 로컬 개발 서버 (server + queue + logs + vite)

**Frontend (TypeScript)**:

npm run build                    # 프로덕션 빌드
npm run dev                      # Vite 개발 서버
npm run lint                     # ESLint 검증
npm run lint:fix                 # ESLint 자동 수정
npm run typecheck                # TypeScript 타입 체크

## @DOC:SECURITY-001 보안 정책 & 운영

### 비밀 관리

- **정책**: 환경 변수 (.env) 기반 비밀 관리, Git 추적 금지
- **도구**:
  - `.env.example` (템플릿 제공)
  - Laravel 내장 `config()` 헬퍼
  - Firebase Service Account JSON (FIREBASE_CREDENTIALS 환경 변수)
- **검증**: `.gitignore`에 `.env`, `firebase-credentials.json` 포함 확인

### 의존성 보안

**PHP (Composer)**:

audit_tool: composer audit
update_policy: 주간 점검, 보안 패치 즉시 적용
vulnerability_threshold: 높음 이상 취약점 즉시 수정

**TypeScript (npm)**:

audit_tool: npm audit
update_policy: 주간 점검, 보안 패치 즉시 적용
vulnerability_threshold: 높음 이상 취약점 즉시 수정

### 로깅 정책

- **로그 수준**:
  - **프로덕션**: WARNING 이상 (ERROR, CRITICAL)
  - **스테이징**: INFO 이상
  - **로컬**: DEBUG (모든 로그)
- **민감정보 마스킹**:
  - 비밀번호, API Key, 토큰: `***REDACTED***`
  - 이메일: 앞 3자리만 표시 (`abc***@example.com`)
  - 전화번호: 앞 3자리만 표시 (`+52 ***-***-1234`)
- **보존 정책**:
  - 로컬: 7일
  - 스테이징: 30일
  - 프로덕션: 90일 (이후 아카이빙)

### Firebase 인증 보안

- **ID Token 검증**: 모든 API 요청에서 Firebase ID Token 검증 (FirebaseAuthService)
- **프로덕션**: 엄격한 검증 (verifyIdToken)
- **로컬 개발**: 관대한 검증 (verifyIdTokenLenient, 에뮬레이터 토큰 허용)
- **세션 유지**: Laravel Sanctum SPA 세션 (CSRF 보호 포함)

## @DOC:DEPLOY-001 배포 채널 & 전략

### 1. 배포 채널

**주 채널**: Ubuntu 22.04+ 서버 (권장)

**배포 방법**:
1. **Docker** (권장): Laravel Sail 기반 컨테이너화
2. **Traditional**: PHP-FPM + Nginx + PostgreSQL + Redis

**릴리스 절차**:
1. 로컬 개발 (feature 브랜치)
2. PR 생성 → Review Checks 워크플로우 실행
3. 품질 게이트 통과 (composer quality:check, npm run lint)
4. main 브랜치 병합
5. 프로덕션 배포 (CI/CD 자동화 예정)

**버전 정책**: Semantic Versioning (v1.0.0, v1.1.0, v1.1.1)

**rollback 전략**:
- **Database 마이그레이션**: `php artisan migrate:rollback`
- **코드**: Git 이전 커밋으로 롤백 + 재배포
- **Firebase 설정**: Firebase Console에서 수동 롤백

### 2. 개발 설치

**사전 요구사항**:
- PHP 8.2+ (권장: 8.4)
- Composer 2.x
- Node.js 20+
- PostgreSQL 14+
- Redis 6+

**로컬 개발 환경 구축**:

# 1. 저장소 클론
git clone https://github.com/your-org/olulo-mx-admin.git
cd olulo-mx-admin

# 2. 환경 변수 설정
cp .env.example .env
# .env 파일 편집: DB_*, FIREBASE_* 설정

# 3. Composer 의존성 설치
composer install

# 4. NPM 의존성 설치
npm install

# 5. 애플리케이션 키 생성
php artisan key:generate

# 6. 데이터베이스 마이그레이션
php artisan migrate

# 7. Firebase Service Account 설정
# firebase-credentials.json 파일을 프로젝트 루트에 배치
# .env에 FIREBASE_CREDENTIALS 경로 설정

# 8. 개발 서버 시작 (병렬 실행)
composer dev
# 또는 개별 실행:
# php artisan serve          # Laravel 서버 (8000)
# php artisan queue:listen   # Queue Worker
# php artisan pail           # Log Viewer
# npm run dev                # Vite Dev Server (5173)

**Docker 기반 개발 (Laravel Sail)**:

# 1. Sail 설치 및 시작
./vendor/bin/sail up -d

# 2. 마이그레이션
./vendor/bin/sail artisan migrate

# 3. Vite 개발 서버
./vendor/bin/sail npm run dev

### 3. CI/CD 파이프라인 (예정)

| 단계 | 목적 | 사용 도구 | 성공 조건 |
|------|------|-----------|-----------|
| **Lint** | 코드 품질 검증 | Pint (PHP), ESLint (TS) | 오류 0개 |
| **Type Check** | 타입 안전성 검증 | Larastan (PHP), TSC (TS) | 오류 0개 |
| **Test** | 단위/통합 테스트 | Pest (PHP) | 모든 테스트 통과 |
| **Build** | 프로덕션 빌드 | Vite (Frontend) | 빌드 성공 |
| **Deploy** | 배포 | Docker + GitHub Actions | 배포 성공 |

## 환경별 설정

### 개발 환경 (local)

export APP_ENV=local
export APP_DEBUG=true
export LOG_LEVEL=debug

# Firebase Emulator 사용
export FIREBASE_AUTH_EMULATOR_HOST=localhost:9099

composer dev  # Laravel + Queue + Vite 병렬 실행

### 테스트 환경 (testing)

export APP_ENV=testing
export APP_DEBUG=false
export LOG_LEVEL=info
export DB_CONNECTION=pgsql_testing

composer test  # Pest 테스트 실행

### 프로덕션 환경 (production)

export APP_ENV=production
export APP_DEBUG=false
export LOG_LEVEL=warning

# 프로덕션 빌드
npm run build
composer install --optimize-autoloader --no-dev

# 캐시 최적화
php artisan config:cache
php artisan route:cache
php artisan view:cache

## @CODE:TECH-DEBT-001 기술 부채 관리

### 현재 기술 부채

1. **Firebase Emulator 로컬 환경 미완성** (우선순위: 높음)
   - verifyIdTokenLenient() 임시 대응 중
   - 로컬 개발 환경에서 서명 없는 토큰 허용 (보안 주의)
   - **해결 방법**: Firebase Emulator Suite 완전 통합, 환경 변수 분리

2. **테스트 커버리지 부족** (우선순위: 높음)
   - 현재 테스트 코드 부족
   - 목표: PHP 85%, TypeScript 80%
   - **해결 방법**: MoAI-ADK `/alfred:2-build` TDD 워크플로우 적용

3. **TypeScript 품질 도구 미설정** (우선순위: 중간)
   - ESLint 설정 파일 없음 (eslint.config.js 예정)
   - TypeScript 컴파일러 옵션 최적화 필요
   - **해결 방법**: ESLint + TypeScript ESLint 설정, tsconfig.json 강화

4. **Rector 설정 미완성** (우선순위: 낮음)
   - rector.php 파일 없음
   - Laravel 전용 Rector 룰 미적용
   - **해결 방법**: driftingly/rector-laravel 룰셋 적용

5. **CI/CD 파이프라인 미구축** (우선순위: 중간)
   - GitHub Actions 워크플로우 없음 (Review Checks만 존재)
   - 자동화된 배포 프로세스 없음
   - **해결 방법**: GitHub Actions 워크플로우 구축 (lint → test → build → deploy)

### 개선 계획

- **단기 (1개월)**:
  - Firebase Emulator 완전 통합
  - Pest 테스트 커버리지 50% 달성
  - ESLint 설정 완료

- **중기 (3개월)**:
  - Pest 테스트 커버리지 85% 달성
  - CI/CD 파이프라인 구축 (GitHub Actions)
  - Rector 자동 리팩토링 적용

- **장기 (6개월+)**:
  - E2E 테스트 (Laravel Dusk) 도입
  - TypeScript 테스트 프레임워크 (Vitest) 도입
  - 성능 모니터링 (Laravel Telescope + APM)

## EARS 기술 요구사항 작성법

### 기술 스택에서의 EARS 활용

기술적 의사결정과 품질 게이트 설정 시 EARS 구문을 활용하여 명확한 기술 요구사항을 정의하세요:

#### 기술 스택 EARS 예시

### Ubiquitous Requirements (기본 기술 요구사항)
- 시스템은 PHP 8.2 이상을 사용해야 한다
- 시스템은 TypeScript 5.9 타입 안전성을 보장해야 한다
- 시스템은 듀얼 언어 스택 (PHP + TypeScript)을 지원해야 한다

### Event-driven Requirements (이벤트 기반 기술)
- WHEN 코드가 커밋되면, 시스템은 자동으로 Pint + Larastan 품질 검증을 실행해야 한다
- WHEN 빌드가 실패하면, 시스템은 개발자에게 즉시 알림을 보내야 한다 (CI/CD)
- WHEN 의존성에 보안 취약점이 발견되면, 시스템은 PR을 차단해야 한다

### State-driven Requirements (상태 기반 기술)
- WHILE 개발 모드일 때, 시스템은 Vite HMR을 제공해야 한다 (< 100ms)
- WHILE 프로덕션 모드일 때, 시스템은 최적화된 빌드를 생성해야 한다 (< 30초)
- WHILE 로컬 환경일 때, 시스템은 Firebase Emulator 토큰을 허용해야 한다 (verifyIdTokenLenient)

### Optional Features (선택적 기술)
- WHERE Docker 환경이면, 시스템은 Laravel Sail 기반 개발 환경을 제공할 수 있다
- WHERE CI/CD가 구성되면, 시스템은 자동 배포를 수행할 수 있다
- WHERE E2E 테스트가 필요하면, 시스템은 Laravel Dusk를 활용할 수 있다

### Constraints (기술적 제약사항)
- IF 의존성에 보안 취약점 (높음 이상)이 발견되면, 시스템은 빌드를 중단해야 한다
- PHP 테스트 커버리지는 85% 이상을 유지해야 한다
- TypeScript 빌드 시간은 30초를 초과하지 않아야 한다
- 모든 Firebase 인증 요청은 HTTPS를 사용해야 한다

---

_이 기술 스택은 `/alfred:2-build` 실행 시 TDD 도구 선택과 품질 게이트 적용의 기준이 됩니다._

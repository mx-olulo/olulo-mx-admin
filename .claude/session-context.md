# Claude Code 세션 컨텍스트 - Olulo MX Admin

## 프로젝트 개요
- **프로젝트명**: Olulo MX Admin - 멕시코 음식 배달 플랫폼
- **아키텍처**: Laravel 12 + Filament 4 + Nova v5 + React 19.1
- **패턴**: DDD/CQRS + 멀티테넌시 (서브도메인 기반)
- **인증**: Firebase + Sanctum SPA 세션
- **현재 단계**: Phase1 완료, Phase2 준비

## Phase1 완료 상태 (2025-09-27)

### ✅ 완료된 작업
1. **에이전트 파일 재구성** (PR #15 머지완료)
   - 에이전트 파일명 정리 (.claude/agents/*.md)
   - 16개 전문 에이전트 활용 가능

2. **Laravel 12 프로젝트 초기화**
   - Laravel Framework 12.31.1 설치
   - Laravel Boost v1.2.1 보일러플레이트 적용
   - 멕시코 시장 특화 설정 (MXN, es_MX, Mexico City)

3. **기본 의존성 설치**
   - Sanctum v4.0, Filament v4.0.20, Firebase SDK v7.15
   - 개발도구: Pint, Larastan, Telescope, Debugbar
   - 품질검사: PSR-12, PHPStan Level 5 통과

4. **프로젝트 구조 표준화**
   - DDD/CQRS 아키텍처 구현
   - 멀티테넌시 기반 구조
   - 도메인별 구조: Restaurant, Order, User, Delivery, Payment

### 📊 현재 상태
- **브랜치**: `chore/boost-bootstrap`
- **PR**: #16 생성완료 (84 files changed, 4,067 insertions)
- **커밋**: 90fabaf - "feat: complete Phase1 Laravel 12 multitenancy foundation setup"
- **다음 작업**: PR 머지 후 Phase2 시작

## Phase2 작업 계획 (다음 세션)

### 🎯 목표: 인증 기반 설정 (예상 1시간 45분)
1. **Firebase Admin SDK 통합** (45분)
   - Firebase 서비스 계정 설정
   - 환경변수 설정 (.env 템플릿 업데이트)
   - Firebase 인증 헬퍼 클래스 구현

2. **Sanctum SPA 세션 설정** (30분)
   - SANCTUM_STATEFUL_DOMAINS 설정
   - 세션 쿠키 도메인 설정
   - CSRF 보호 설정

3. **CORS/CSRF 보안 설정** (30분)
   - cors.php 설정 (서브도메인 지원)
   - 프론트엔드 도메인 화이트리스트
   - preflight 요청 처리

### 🔄 Phase2 시작 명령어
```bash
# 1. PR 머지 확인 및 최신 동기화
git checkout main && git pull origin main

# 2. Phase2 브랜치 생성
git checkout -b feature/phase2-auth-foundation

# 3. Phase2 작업 시작
@agent-laravel-expert Firebase Admin SDK 통합을 시작해라
```

## 주요 파일 경로

### 설정 파일
- `/opt/GitHub/olulo-mx-admin/config/tenancy.php` - 멀티테넌시 설정
- `/opt/GitHub/olulo-mx-admin/config/olulo.php` - 플랫폼 비즈니스 설정
- `/opt/GitHub/olulo-mx-admin/config/sanctum.php` - SPA 인증 설정
- `/opt/GitHub/olulo-mx-admin/.env.example` - 환경변수 템플릿

### 아키텍처 파일
- `/opt/GitHub/olulo-mx-admin/src/Domain/` - 도메인 레이어
- `/opt/GitHub/olulo-mx-admin/src/Application/` - 애플리케이션 레이어
- `/opt/GitHub/olulo-mx-admin/src/Infrastructure/` - 인프라스트럭처 레이어
- `/opt/GitHub/olulo-mx-admin/app/Providers/` - 서비스 프로바이더

### 서비스 프로바이더
- `DomainServiceProvider.php` - DDD 의존성 관리
- `TenancyServiceProvider.php` - 멀티테넌시 기능
- `TelescopeServiceProvider.php` - 개발 도구

## 환경 정보
- **PHP**: 8.3.22
- **Composer**: 2.8.5
- **Node**: 설치필요 (React 프론트엔드용)
- **Database**: PostgreSQL (프로덕션), SQLite (개발)

## 품질 도구 명령어
```bash
# 코드 스타일 검사 및 수정
vendor/bin/pint

# 정적 분석
vendor/bin/phpstan analyse

# 전체 테스트
php artisan test

# 의존성 검증
composer validate
```

## 다음 세션 체크리스트

### Phase2 시작 전 확인사항
- [ ] PR #16이 머지되었는지 확인
- [ ] main 브랜치 최신 상태 동기화
- [ ] 개발 환경 정상 동작 확인 (`php artisan --version`)
- [ ] Firebase 프로젝트 준비 (서비스 계정 키)

### Phase2 작업 순서
1. Firebase Admin SDK 통합 - **laravel-expert** 에이전트 활용
2. Sanctum SPA 세션 설정 - **laravel-expert** 에이전트 활용
3. CORS/CSRF 보안 설정 - **laravel-expert** + **architect** 에이전트 활용
4. 품질 검사 및 테스트 - **code-reviewer** 에이전트 활용

## Phase3 작업 계획 (인증 구현)

### 🎯 목표: 인증 구현 (예상 2시간 30분)
7. **Firebase 토큰 검증 미들웨어** (1시간)
   - Firebase ID 토큰 검증 미들웨어 구현
   - 토큰 디코딩 및 사용자 정보 추출
   - 에러 처리 및 로깅

8. **AuthController 및 라우트 구현** (1시간)
   - `/api/auth/firebase-login` 엔드포인트
   - `/api/auth/logout` 엔드포인트
   - 세션 관리 로직
   - API 응답 표준화 (204/200/401/403)

9. **User 모델 Firebase 통합** (30분)
   - User 모델에 Firebase UID 필드 추가
   - 사용자 생성/업데이트 로직
   - Firebase 사용자 정보 동기화

### 담당 에이전트
- **code-author**: 미들웨어, 컨트롤러 구현
- **laravel-expert**: 라우팅, 세션 관리
- **database-expert**: User 모델 확장

## Phase4 작업 계획 (품질 보증)

### 🎯 목표: 품질 보증 (예상 2시간)
10. **테스트 케이스 작성** (1시간)
    - 인증 플로우 통합 테스트
    - Firebase 토큰 검증 단위 테스트
    - API 엔드포인트 기능 테스트
    - 에러 시나리오 테스트

11. **환경별 설정 파일 정비** (30분)
    - .env.example 완성
    - 환경별 Firebase 설정
    - 프로덕션 보안 설정

12. **품질 검사 및 문서화** (30분)
    - PHPStan/Pint 최종 검사
    - API 문서 작성
    - 인증 플로우 문서화
    - 배포 가이드 작성

### 담당 에이전트
- **code-author**: 테스트 작성
- **laravel-expert**: 환경 설정
- **docs-reviewer**: 문서화
- **pm**: 품질 검증 및 완료 확인

## 전체 마일스톤 타임라인

### 📅 예상 일정 (총 7-8시간)
```
✅ Phase1 (완료): 기반 구조 (3시간)
🔄 Phase2 (다음): 인증 기반 (1시간 45분)
📋 Phase3 (예정): 인증 구현 (2시간 30분)
🎯 Phase4 (예정): 품질 보증 (2시간)
```

### 🎯 마일스톤 체크포인트
- **M1**: Laravel 프로젝트 부팅 완료 ✅
- **M2**: 인증 기반 준비 완료 (Phase2 완료 시)
- **M3**: 인증 플로우 구현 완료 (Phase3 완료 시)
- **M4**: 품질 보증 완료 (Phase4 완료 시)

### 이슈 #1 최종 목표
```
Firebase 인증과 Laravel Sanctum SPA 세션 기반 인증 시스템 구현
- 인증 플로우: /sanctum/csrf-cookie → POST /api/auth/firebase-login → 세션 확립
- 로그아웃: POST /api/auth/logout 엔드포인트
- Firebase ID 토큰 검증 미들웨어
- 완료 기준: 204 응답으로 세션 확립, 보호 API 접근 제어 (200/401/403)
```

## 리스크 관리 계획

### 🚨 Phase별 주요 리스크
#### Phase2 리스크
- Firebase 서비스 계정 설정 복잡도
- Sanctum 도메인 설정 오류
- CORS 정책 충돌

#### Phase3 리스크
- Firebase 토큰 검증 실패
- 세션 관리 복잡도
- API 응답 표준화 이슈

#### Phase4 리스크
- 테스트 커버리지 부족
- 성능 병목 발생
- 문서화 누락

### 대응 방안
- 각 Phase별 품질 체크포인트 설정
- 에러 시나리오 대비 폴백 옵션
- 단계별 테스트 및 검증

## 전문 에이전트 활용 가이드

### 추천 에이전트 매핑
- **laravel-expert**: Laravel 12 기능, 인증, 설정
- **code-author**: 새로운 클래스/미들웨어 작성
- **architect**: 아키텍처 설계 및 검토
- **code-reviewer**: 코드 품질 검토
- **database-expert**: 모델 및 마이그레이션
- **coordinator**: 복잡한 작업 조정

### 세션 시작 명령 예시
```bash
# 새 세션에서 컨텍스트 로드
cat .claude/session-context.md

# Phase2 시작
@agent-coordinator Phase2 인증 기반 설정을 시작해라
```

---
**생성일시**: 2025-09-27 15:45 UTC
**다음 세션**: Phase2 인증 기반 설정
**예상 소요**: 1시간 45분
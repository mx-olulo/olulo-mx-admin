# Phase2 - Firebase 통합 인증 시스템 구축

## 개요

Phase2는 Firebase Admin SDK와 Sanctum SPA 세션을 통합하여 고객 앱과 관리자 대시보드를 위한 통합 인증 기반을 구축하는 단계입니다. 동일 루트 도메인 아래 서브도메인 간 세션 공유를 통해 원활한 사용자 경험을 제공합니다.

## 목표와 범위

### 핵심 목표
- Firebase Admin SDK를 통한 서버 측 인증 토큰 검증 체계 확립
- Sanctum SPA 세션 기반 상태 관리로 서브도메인 간 세션 공유
- 다국어 지원 (ko/en/es-MX) 인증 인터페이스 구현
- 환경별 CORS 및 세션 도메인 설정 표준화
- Firebase Emulator Suite를 활용한 로컬 테스트 환경 구축

### 구현 범위
- Firebase Admin SDK 통합 서비스 계층
- 인증 컨트롤러 및 미들웨어 구현
- 환경별 설정 템플릿 구성
- 다국어 인증 UI 컴포넌트
- 테스트 케이스 및 문서화

## Firebase 통합 인증 시스템 아키텍처

### 시스템 구성 요소

#### 1. 인증 서비스 계층
Firebase Admin SDK를 래핑하여 토큰 검증, 사용자 관리, 클레임 처리를 담당하는 서비스 계층입니다.

- **FirebaseAuthService**: Firebase Admin SDK 기능을 Laravel 서비스로 추상화
- **FirebaseAuthInterface**: 인터페이스를 통한 의존성 주입 및 테스트 용이성 확보
- **FirebaseAuthException**: 인증 관련 예외 처리 표준화

#### 2. 인증 컨트롤러
클라이언트의 인증 요청을 처리하고 세션을 관리하는 HTTP 계층입니다.

- **FirebaseAuthController**: Firebase ID 토큰 검증 및 세션 생성
- **SessionController**: 세션 상태 관리 및 로그아웃 처리

#### 3. 미들웨어 구성
요청의 인증 상태를 검증하고 테넌트 컨텍스트를 설정하는 미들웨어입니다.

- **ValidateFirebaseToken**: Firebase ID 토큰 유효성 검증
- **EnsureValidTenant**: 서브도메인 기반 테넌트 검증 및 컨텍스트 주입

### 인증 플로우

#### 고객 앱 인증 플로우
1. 고객이 QR 코드 스캔 또는 직접 URL 접근
2. React 앱에서 Firebase 초기화 및 세션 쿠키 요청
3. FirebaseUI를 통한 로그인 (Google, Email, 익명)
4. Firebase ID 토큰 획득 후 백엔드 전송
5. 서버에서 토큰 검증 및 Sanctum 세션 생성
6. 세션 쿠키 기반 API 접근

#### 관리자 대시보드 인증 플로우
1. 관리자가 Filament/Nova 대시보드 접근
2. Firebase 인증 상태 확인
3. 미인증 시 FirebaseUI 리다이렉트
4. 인증 완료 후 역할/권한 검증
5. 대시보드 접근 허용

## 구현 컴포넌트 상세

### Firebase Admin SDK 서비스

#### FirebaseAuthService 주요 메서드
- `verifyIdToken(string $token): array` - ID 토큰 검증 및 클레임 반환
- `getUser(string $uid): array` - Firebase 사용자 정보 조회
- `setCustomClaims(string $uid, array $claims): void` - 커스텀 클레임 설정
- `revokeRefreshTokens(string $uid): void` - 리프레시 토큰 무효화
- `createCustomToken(string $uid, array $claims = []): string` - 커스텀 토큰 생성

#### 사용자 동기화 로직
Firebase 사용자와 Laravel 사용자를 동기화하여 일관된 사용자 경험을 제공합니다.

- Firebase UID를 기준으로 로컬 사용자 조회
- 신규 사용자인 경우 자동 생성
- 프로필 정보 동기화 (이름, 이메일, 프로필 사진)
- 역할 및 권한 매핑

### 인증 엔드포인트

#### 공개 엔드포인트
- `GET /sanctum/csrf-cookie` - CSRF 토큰 획득
- `POST /api/auth/firebase/login` - Firebase ID 토큰으로 로그인
- `POST /api/auth/firebase/register` - 신규 사용자 등록
- `POST /api/auth/firebase/anonymous` - 익명 사용자 세션 생성

#### 보호된 엔드포인트
- `GET /api/auth/user` - 현재 사용자 정보
- `POST /api/auth/logout` - 로그아웃
- `POST /api/auth/refresh` - 세션 갱신
- `PUT /api/auth/profile` - 프로필 업데이트

### 미들웨어 상세

#### ValidateFirebaseToken
Firebase ID 토큰의 유효성을 검증하는 미들웨어입니다.

검증 항목:
- 토큰 서명 검증
- 발급자(iss) 확인
- 만료 시간(exp) 검증
- 대상(aud) 프로젝트 ID 일치 확인

#### EnsureValidTenant
서브도메인 기반 테넌트를 식별하고 컨텍스트를 설정합니다.

처리 과정:
1. 요청 호스트에서 서브도메인 추출
2. 서브도메인으로 매장 코드 조회
3. 테넌트 컨텍스트 바인딩
4. 후속 쿼리 스코프 적용

## 다국어 지원 설계

### 지원 언어
- **한국어 (ko)**: 기본 언어
- **영어 (en)**: 국제 사용자용
- **스페인어 (es-MX)**: 멕시코 현지 사용자용

### 언어 감지 및 전환
1. **자동 감지**: Accept-Language 헤더 기반 초기 언어 설정
2. **수동 전환**: 사용자 선택 시 세션/쿠키에 저장
3. **우선순위**: 사용자 선택 > 프로필 설정 > 브라우저 설정 > 기본값

### 인증 UI 다국어 처리
- FirebaseUI 로케일 설정
- 에러 메시지 번역
- 이메일 템플릿 다국어화
- 날짜/시간 형식 지역화

## CORS 및 세션 도메인 설정

### 지원 도메인 구성

#### 개발 환경
- **로컬호스트**: `localhost:3000`, `localhost:8000`
- **Firebase 호스팅**: `mx-olulo.firebaseapp.com`, `mx-olulo.web.app`
- **개발 도메인**: `admin.dev.olulo.com.mx`, `menu.dev.olulo.com.mx`

#### 프로덕션 환경
- **관리자**: `admin.olulo.com.mx`
- **고객**: `menu.olulo.com.mx`
- **API**: `api.olulo.com.mx`

### CORS 정책 설정

#### 허용 오리진
각 환경에 따라 명시적으로 허용된 오리진을 설정합니다.

- Credentials 포함 허용
- Preflight 요청 캐싱
- 커스텀 헤더 허용 (X-XSRF-TOKEN, X-Requested-With)

#### 세션 쿠키 설정
- **Domain**: 상위 도메인 설정 (`.olulo.com.mx`)
- **Secure**: HTTPS 전용
- **SameSite**: Lax (CSRF 보호)
- **HttpOnly**: XSS 방지

### Sanctum Stateful 도메인
각 환경별로 상태를 유지할 도메인을 명시적으로 지정합니다.

- 서브도메인 간 세션 공유
- CSRF 토큰 검증 도메인
- API 요청 인증 도메인

## Firebase Emulator Suite 테스트 전략

### 에뮬레이터 구성

#### Authentication Emulator
- 로컬 인증 테스트 환경
- 테스트 사용자 시딩
- 토큰 생성 및 검증

#### Firestore Emulator
- 사용자 프로필 저장
- 세션 메타데이터 관리
- 권한 규칙 테스트

#### Functions Emulator
- 인증 트리거 테스트
- 커스텀 클레임 설정
- 이메일 발송 시뮬레이션

### 테스트 시나리오

#### 단위 테스트
- 토큰 검증 로직
- 사용자 동기화
- 권한 확인
- 세션 생성/파기

#### 통합 테스트
- 전체 인증 플로우
- 크로스 도메인 세션
- 역할별 접근 제어
- 토큰 갱신 처리

#### E2E 테스트
- 사용자 등록부터 로그인까지
- 다국어 전환 동작
- 세션 만료 및 재인증
- 익명에서 등록 사용자로 전환

### 테스트 데이터 관리

#### 시드 데이터
- 테스트 사용자 계정
- 역할 및 권한 설정
- 매장 및 테넌트 데이터

#### 테스트 격리
- 테스트별 독립된 Firebase 프로젝트
- 트랜잭션 롤백
- 에뮬레이터 상태 초기화

## 구현 체크리스트

### Phase 2.1 - 기초 설정 (2시간)
- [ ] Firebase 프로젝트 생성 및 설정
- [ ] Firebase Admin SDK 서비스 계정 키 생성
- [ ] composer require kreait/firebase-php 설치
- [ ] 환경 변수 설정 (.env.example 업데이트)
- [ ] Firebase 서비스 프로바이더 등록

### Phase 2.2 - 서비스 계층 구현 (3시간)
- [ ] FirebaseAuthInterface 인터페이스 정의
- [ ] FirebaseAuthService 구현
- [ ] FirebaseAuthException 예외 클래스
- [ ] 서비스 컨테이너 바인딩
- [ ] 단위 테스트 작성

### Phase 2.3 - 인증 컨트롤러 (2시간)
- [ ] FirebaseAuthController 구현
- [ ] SessionController 구현
- [ ] API 라우트 정의
- [ ] 요청 유효성 검사 규칙
- [ ] 응답 포맷 표준화

### Phase 2.4 - 미들웨어 구현 (2시간)
- [ ] ValidateFirebaseToken 미들웨어
- [ ] EnsureValidTenant 미들웨어
- [ ] 미들웨어 등록 (bootstrap/app.php)
- [ ] 라우트 그룹 적용
- [ ] 미들웨어 테스트

### Phase 2.5 - Sanctum 통합 (2시간)
- [ ] Sanctum 설정 최적화
- [ ] Stateful 도메인 구성
- [ ] CORS 정책 설정
- [ ] 세션 드라이버 설정
- [ ] CSRF 보호 활성화

### Phase 2.6 - 다국어 지원 (2시간)
- [ ] 언어 파일 구조 설정
- [ ] 인증 메시지 번역
- [ ] FirebaseUI 로케일 설정
- [ ] 언어 전환 API
- [ ] 번역 캐싱 전략

### Phase 2.7 - Firebase Emulator 설정 (2시간)
- [ ] Firebase CLI 설치 및 초기화
- [ ] 에뮬레이터 설정 파일 구성
- [ ] 테스트 데이터 시딩 스크립트
- [ ] 로컬 환경 변수 설정
- [ ] 에뮬레이터 실행 스크립트

### Phase 2.8 - 테스트 구현 (3시간)
- [ ] 단위 테스트 케이스 작성
- [ ] 통합 테스트 시나리오
- [ ] E2E 테스트 설정
- [ ] 테스트 커버리지 확인
- [ ] CI/CD 파이프라인 통합

### Phase 2.9 - 문서화 (1시간)
- [ ] API 문서 생성
- [ ] 구현 가이드 작성
- [ ] 트러블슈팅 가이드
- [ ] 보안 체크리스트
- [ ] 배포 가이드

### Phase 2.10 - 보안 강화 (2시간)
- [ ] Rate limiting 적용
- [ ] 토큰 블랙리스트 구현
- [ ] 세션 하이재킹 방지
- [ ] XSS/CSRF 보호 검증
- [ ] 보안 헤더 설정

## 성공 기준

### 기능적 요구사항
- Firebase ID 토큰을 통한 성공적인 인증
- 서브도메인 간 세션 공유 동작
- 다국어 인증 UI 정상 작동
- 익명 사용자에서 등록 사용자로의 원활한 전환
- 역할 기반 접근 제어 정상 동작

### 비기능적 요구사항
- 인증 응답 시간 500ms 이하
- 동시 사용자 1,000명 처리 가능
- 99.9% 가용성 목표
- 테스트 커버리지 80% 이상
- 보안 취약점 0건

### 품질 지표
- 모든 PHPStan 레벨 8 검사 통과
- Laravel Pint 코드 스타일 준수
- 단위 테스트 100% 통과
- 통합 테스트 100% 통과
- 문서 완성도 100%

### 검증 방법
- 개발 환경에서 전체 인증 플로우 테스트
- 스테이징 환경에서 부하 테스트
- 보안 스캔 도구 실행
- 코드 리뷰 완료
- 사용자 수용 테스트 통과

## 위험 요소 및 완화 방안

### 기술적 위험
- **Firebase 서비스 중단**: Fallback 인증 메커니즘 준비
- **세션 동기화 실패**: Redis 클러스터 구성 및 모니터링
- **CORS 정책 충돌**: 환경별 명시적 설정 및 테스트

### 보안 위험
- **토큰 탈취**: HTTPS 강제, 토큰 수명 단축
- **세션 하이재킹**: IP 검증, User-Agent 확인
- **무차별 대입 공격**: Rate limiting, 계정 잠금

### 운영 위험
- **설정 오류**: 환경별 설정 검증 자동화
- **배포 실패**: 롤백 계획 수립
- **모니터링 부재**: 로그 수집 및 알림 설정

## 관련 문서

- [인증 설계 상세](../auth.md)
- [Phase2 구현 계획](../auth/phase2-implementation.md)
- [환경별 설정 가이드](../devops/environments.md)
- [테넌시 미들웨어 설계](../tenancy/host-middleware.md)
- [프로젝트 1 마일스톤](project-1.md)
- [화이트페이퍼](../whitepaper.md)
- [QA 체크리스트](../qa/checklist.md)

## 다음 단계

Phase2 완료 후 다음 단계로 진행할 항목:

1. **Phase 3**: 메뉴 관리 시스템 구현
2. **Phase 4**: 주문 및 결제 시스템 구축
3. **Phase 5**: 알림 시스템 통합 (WhatsApp, WebPush)
4. **Phase 6**: 리포팅 및 분석 도구 개발
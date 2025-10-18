<!--
Sync Impact Report
==================
Version: 2.1.0 → 2.2.0 (MINOR: Workflow expansion)
Ratification Date: 2025-10-18
Last Amendment: 2025-10-18

Changes:
- EXPANDED: Development Workflow section to include GitHub Issue and Draft PR steps
- Added GitHub tracking integration to SpecKit workflow
- Enhanced transparency and traceability in development process

Rationale for Changes:
- GitHub Issue creation ensures work is publicly tracked and visible
- Draft PR provides early visibility into planned changes
- Linking Issues to PRs creates clear traceability from specification to implementation
- Improves collaboration and project management transparency

Modified Sections:
- Development Workflow > SpecKit 기반 작업 흐름: Added steps 3.5 (Issue creation) and 4.5 (Draft PR creation)

Template Alignment:
✅ .specify/templates/plan-template.md - No updates needed
✅ .specify/templates/spec-template.md - No updates needed
✅ .specify/templates/tasks-template.md - No updates needed
✅ GitHub integration already available via gh CLI

Follow-up TODOs: None
-->

# Olulo MX Admin 프로젝트 헌장

## 핵심 원칙 (Core Principles)

### I. 문서 우선 개발 (Documentation-First Development)

모든 기능 개발과 아키텍처 변경은 문서화를 선행한다:

- 코드 작성 전 관련 문서(`docs/*`)를 먼저 갱신하고 검토받아야 한다
- 설계 문서(spec.md, plan.md)가 승인된 후에만 구현을 시작할 수 있다
- API 변경, 데이터 모델 수정, 인증/세션 정책 변경은 반드시 해당 문서 갱신을 포함해야 한다
- 문서 간 교차 참조 링크를 강화하여 맥락 파악을 용이하게 한다

**근거**: 멀티테넌시, 복잡한 인증 플로우(Firebase + Sanctum), 다국어/다중통화 요구사항을 가진
프로젝트에서 문서화 없는 변경은 일관성을 해치고 기술 부채를 누적시킨다.

### II. 한국어 의사소통 (Korean Language Communication)

모든 사고 과정, 응답, 문서 작성, 커밋 메시지(접두사 제외), PR 제목/본문, 이슈는
한국어(우리말)로 작성한다:

- 커밋 메시지: `feat: 사용자 인증 미들웨어 추가`
- PR 제목: `chore(auth): Firebase ID 토큰 검증 로직 구현`
- 이슈 제목 및 본문: 모두 한국어
- 코드 주석: 복잡한 로직에는 한국어 주석 우선

**예외**: 코드(변수명, 함수명, 클래스명)는 영어 사용, 공식 문서 링크/기술 용어는 원어 병기 가능

**근거**: 프로젝트 팀의 주 언어가 한국어이며, 명확한 의사소통으로 오해를 방지하고
협업 효율을 극대화한다.

### III. 코드 품질 & 정적 분석 (Code Quality & Static Analysis) — NON-NEGOTIABLE

모든 커밋은 자동화된 품질 검증 도구를 통과해야 한다:

**필수 도구 (Git Pre-commit Hook으로 자동 실행)**:
- **Rector**: PHP 코드 자동 리팩토링 및 현대화 (`vendor/bin/rector process --dry-run`)
- **Laravel Pint**: 코드 스타일 검사 (`vendor/bin/pint --test`)
- **Larastan/PHPStan**: 정적 타입 분석 (`vendor/bin/phpstan analyse`)

**추가 규칙**:
- **300라인 규칙**: 한 파일이 300라인을 초과하면 trait/interface/서비스 클래스로 분할
- **Artisan 우선**: 데이터베이스/모델/컨트롤러 생성 시 `php artisan make:*` 명령 사용 필수
- **변수/필드명 일관성**: 기존 명칭 확인 후 재사용, 신규 생성 최소화

**자동 검증 시점**:
- **로컬**: Git commit 시 pre-commit hook이 자동으로 Rector → Pint → PHPStan 순차 실행
- **CI**: GitHub Actions 파이프라인에서 동일한 검사 재실행
- **PR 머지 전**: 모든 품질 검사 통과 필수

**Git Hook 설치**: `composer install` 시 자동 설치되며, 수동 설치는 `composer install-hooks`

**근거**: Laravel 12, Filament 4, Nova v5, React 19.1을 사용하는 복잡한 스택에서
일관된 품질 기준 없이는 유지보수가 불가능하다. Git hook을 통한 자동화로
품질 저하를 사전에 차단한다.

### IV. 멀티테넌시 & 도메인 모델 아키텍처 (Multi-Tenancy & Domain Model Architecture)

서브도메인 기반 호스트 분리와 도메인 주도 설계를 준수한다:

- **테넌시 전략**: 서브도메인 기반 호스트 분리 (`docs/tenancy/host-middleware.md` 준수)
- **도메인 스코핑**: 모든 주요 엔티티는 `store_id` 또는 `store_group_id` 스코핑 필수
- **세션 정책**: Firebase + Sanctum SPA 세션 (동일 루트 도메인 공유)
- **CORS/쿠키**: `docs/devops/environments.md` 환경 정책 엄격 준수
- **정책 변경 금지**: 테넌시/도메인/세션 정책을 임의 변경하지 않음 (문서 기준 엄수)

**근거**: 프랜차이즈와 일반 매장을 분리 운영하며, 데이터 격리와 보안을 보장해야 한다.

### V. 보안 & 컴플라이언스 (Security & Compliance)

보안 최우선 원칙을 모든 단계에 적용한다:

- **비밀 관리**: `.env`, `credentials.json` 등 비밀 값은 절대 커밋하지 않음
- **인증 검증**: Firebase ID Token 검증 미들웨어 필수 (`docs/auth.md` 준수)
- **입력 검증**: Form Request 클래스를 통한 검증 (컨트롤러 인라인 검증 금지)
- **OWASP Top 10**: XSS, SQLi, CSRF 방지 메커니즘 적용
- **멕시코 개인정보법**: PII 최소화, 결제 정보 토큰화
- **감사 로그**: 주요 작업(주문/결제/권한 변경)은 로그 기록 필수

**근거**: 결제 정보와 개인정보를 다루는 프로덕션 시스템으로서 보안 사고는
사업 연속성을 위협한다.

### VI. 원자적 변경 & PR 규율 (Atomic Changes & PR Discipline)

모든 변경은 작고 독립적인 단위로 수행한다:

- **1 PR = 1 목적**: 단일 기능, 버그 수정, 또는 문서 갱신
- **300라인 제한**: PR이 300라인을 초과하면 분할 (예외 시 근거 명시)
- **브랜치 전략**: `feature/*`, `chore/*`, `fix/*` 네이밍 준수
- **보호 브랜치**: `main`, `production`에 직접 푸시 금지, PR 필수
- **리뷰 필수**: 최소 1인 승인, 모든 대화 해결 후 머지
- **커밋 메시지**: `type(scope): 한국어 설명` 형식

**근거**: 작은 변경 단위는 리뷰 품질을 높이고, 롤백을 용이하게 하며,
병합 충돌을 최소화한다.

## 기술 스택 표준 (Technology Stack Standards)

본 프로젝트는 다음 기술 스택과 버전을 사용하며, 이탈 시 헌장 수정이 필요하다:

**Backend**
- PHP 8.4.13
- Laravel Framework v12
- Filament v4 (매장 관리자)
- Laravel Nova v5 (마스터 관리자)
- PostgreSQL 15+ (RDBMS)
- Redis (세션/캐시/큐)

**Frontend**
- React 19.1
- Vite (빌드 도구)
- TailwindCSS v4 + daisyUI
- react-i18next (다국어)

**Authentication & API**
- Firebase Authentication (FirebaseUI)
- Laravel Sanctum v4 (SPA 세션)
- Inertia.js v2 (선택적 사용)

**Quality & Testing**
- Rector v2 (코드 리팩토링 및 현대화)
- Laravel Pint v1 (코드 스타일)
- Larastan v3 / PHPStan (정적 분석)
- Pest v3 (테스트 프레임워크)

**Development Tools**
- SpecKit (기능 명세 및 작업 관리)
- GitHub CLI (gh) - Issue 및 PR 관리

**External Integrations**
- WhatsApp Business API (알림)
- operacionesenlinea.com (멕시코 결제)
- OpenExchangeRates 또는 Fixer (환율 API)

**버전 업그레이드**: `docs/repo/rules.md`의 "버전 상향 정책" 준수

## 개발 워크플로우 (Development Workflow)

### SpecKit 기반 작업 흐름

1. **문서 작성/갱신**: `docs/*` 관련 문서 우선 수정

2. **브랜치 생성**: `feature/<scope>-<title>` 또는 `chore/<title>`

3. **SpecKit 명세 작성**: `/speckit.specify`, `/speckit.plan`, `/speckit.tasks` 활용
   - spec.md, plan.md, tasks.md 생성
   - 사용자 스토리, 기술 요구사항, 구현 계획 명세화

4. **GitHub Issue 생성** (명세 작성 완료 후):
   ```bash
   gh issue create --title "[Feature] 기능명" \
     --body "$(cat specs/###-feature/spec.md)" \
     --label "feature" \
     --assignee @me
   ```
   - SpecKit 명세 내용을 Issue 본문으로 등록
   - 적절한 레이블 및 마일스톤 할당
   - Issue 번호 기록 (예: #123)

5. **Draft PR 생성** (Issue 생성 후, 코드 작성 전):
   ```bash
   gh pr create --draft \
     --title "feat: 기능명 구현" \
     --body "Closes #123\n\n$(cat specs/###-feature/plan.md)" \
     --base main
   ```
   - Draft 상태로 PR 생성하여 작업 가시성 확보
   - PR 본문에 관련 Issue 링크 (`Closes #123`)
   - 구현 계획(plan.md) 포함
   - 팀원들이 GitHub에서 작업 내용 사전 확인 가능

6. **코드 작성**: 구현 진행
   - tasks.md를 기준으로 단계별 구현
   - Draft PR에 진행 상황 주기적 업데이트 (선택)

7. **커밋 시도**: Git pre-commit hook이 자동으로 품질 검증 실행
   - 🔍 Rector 리팩토링 검사
   - ✨ Pint 스타일 검사
   - 🔬 PHPStan 정적 분석
   - 실패 시 수정 후 재시도

8. **커밋 완료**: 한국어 메시지 (예: `feat: Firebase 토큰 검증 미들웨어 추가`)

9. **Draft PR을 Ready for Review로 전환**:
   ```bash
   gh pr ready
   ```
   - 코드 작성 완료 후 Draft 해제
   - 리뷰 요청 상태로 전환

10. **CI 통과**: GitHub Actions 워크플로우 (rector, pint, phpstan, review-checks)

11. **리뷰 & 머지**: 1인 이상 승인, 모든 대화 해결 후 머지
    - 머지 시 연결된 Issue 자동 종료 (`Closes #123`)

### 품질 검증 도구 사용법

**자동 실행 (권장)**:
- `git commit`: Pre-commit hook이 자동으로 모든 검사 실행

**수동 실행**:
- `composer quality:check`: 모든 도구 검사만 수행 (수정 없음)
- `composer quality:fix`: 모든 도구 실행 및 자동 수정
- `composer rector`: Rector 리팩토링 적용
- `composer rector:check`: Rector 검사만 수행
- `composer pint`: Pint 스타일 수정 적용
- `composer pint:check`: Pint 검사만 수행
- `composer phpstan`: PHPStan 정적 분석

### GitHub CLI 사용 예시

**Issue 생성**:
```bash
# 기본 템플릿 사용
gh issue create

# 명세 파일을 본문으로 사용
gh issue create \
  --title "[Feature] 사용자 인증 미들웨어" \
  --body-file specs/001-auth/spec.md \
  --label "feature,priority/P1" \
  --milestone "Project 1" \
  --assignee @me
```

**Draft PR 생성**:
```bash
# Draft PR 생성 (관련 Issue 링크 포함)
gh pr create --draft \
  --title "feat: 사용자 인증 미들웨어 구현" \
  --body "Closes #123

## 구현 계획
$(cat specs/001-auth/plan.md)

## 작업 목록
- [ ] Firebase ID Token 검증 미들웨어
- [ ] Sanctum 세션 통합
- [ ] 테스트 작성" \
  --base main

# Draft 해제 (리뷰 준비 완료 시)
gh pr ready
```

### CI/CD 파이프라인

**현재 활성 워크플로우**:
- `.github/workflows/review-checks.yml`: `docs/**` 변경 시 검수 파일 자동 갱신

**로컬 품질 자동화**:
- Git pre-commit hook (`.git/hooks/pre-commit`): 모든 커밋 전 Rector, Pint, PHPStan 자동 실행
- 설치: `composer install` 시 자동 또는 `composer install-hooks`

**계획된 강화** (프로젝트 1 진행 중):
- 빌드/테스트 워크플로우: PHP 런타임 체크, `composer validate`, `rector --dry-run`,
  `pint --test`, `phpstan analyse`
- 프런트엔드 검증: `npm/pnpm ci`, `vite build` (프런트 포함 시)
- Required status checks: 프로덕션 브랜치 머지 조건으로 설정

### 금지 사항

- 보호 규칙 우회 (강제 푸시, 직접 병합)
- 비밀 값 커밋 (`.env`, 키 파일 등)
- 문서 없는 아키텍처 변경
- 300라인 초과 파일/PR (근거 없이)
- 테넌시/세션 정책 임의 변경
- Git pre-commit hook 우회 (`--no-verify` 사용 금지, 예외 시 근거 필수)
- Issue 없이 PR 생성 (문서 갱신 등 단순 작업 제외)

## 거버넌스 (Governance)

### 헌장 우선순위

본 헌장은 모든 개발 관행과 가이드라인보다 우선한다. 충돌 시 헌장을 기준으로 한다.

### 수정 절차

1. **제안**: 원칙 추가/변경/삭제 제안서 작성 (근거 포함)
2. **문서화**: `.specify/memory/constitution.md` 수정 PR 생성
3. **영향도 분석**: 관련 템플릿/문서 갱신 필요 여부 확인
4. **승인**: 프로젝트 리더(CODEOWNERS) 승인 필수
5. **전파**: 종속 문서 일괄 갱신 (plan-template, spec-template, tasks-template 등)
6. **버전 증가**: 시맨틱 버저닝 적용
   - MAJOR: 기존 원칙 제거 또는 호환 불가 재정의
   - MINOR: 새 원칙/섹션 추가 또는 실질적 확장
   - PATCH: 명확화, 오타 수정, 비의미적 개선

### 준수 검증

- **모든 PR**: 헌장 원칙 준수 여부 검토 (특히 III, VI)
- **코드 리뷰**: Git hook 및 CI를 통한 품질 도구 자동 검증 확인
- **문서 리뷰**: 문서 우선 원칙 준수, 교차 참조 확인
- **정기 감사**: 분기별 헌장 준수 현황 점검 (선택)

### 복잡도 정당화

헌장 원칙을 위반해야 하는 경우 (예: 300라인 초과 파일, 새 기술 도입, git hook 우회):

1. PR 본문에 "Complexity Justification" 섹션 추가
2. 위반 사항, 필요 이유, 더 단순한 대안 검토 결과 명시
3. 리뷰어 승인 필수

### 런타임 개발 가이던스

일상 개발 시 참조할 실행 가이드:

- **CLAUDE.md**: Claude Code 개발 가이드 (프롬프트 가드레일 포함)
- **CLAUDE.local.md**: 로컬 개발 체크리스트 및 규칙
- **docs/repo/rules.md**: 저장소 운영 규칙 (브랜치/PR/커밋)

### CODEOWNERS

- 기본 담당: `@bluelucifer`
- 헌장 수정 시 자동 리뷰 요청

**Version**: 2.2.0 | **Ratified**: 2025-10-18 | **Last Amended**: 2025-10-18

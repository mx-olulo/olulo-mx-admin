# 저장소 운영 규칙(Repository Rules)

본 문서는 olulo-mx-admin 저장소의 브랜치 전략, 보호 규칙, 라벨/마일스톤, PR/리뷰, 템플릿, CODEOWNERS, 커밋 컨벤션, 워크플로우 정책을 정의합니다. 프로젝트 1 진행 중에는 본 문서를 기준으로 운영합니다.

## 브랜치 전략
- 기본 브랜치: `main` (보호됨, 직접 푸시 금지)
- 장기 브랜치: `develop`, `staging`, `production`
- 기능/작업 브랜치: `feature/<scope>-<short-title>`, `chore/<title>`, `fix/<title>`
- 머지 흐름(권장):
  - `feature/*` → PR → `develop`
  - 검증 후 `develop` → `staging`
  - 최종 승인 후 `staging` → `production` (배포)
  - `main`은 문서/운영 기준 브랜치로 유지(정책/문서 병합은 PR로)

## 브랜치 보호 규칙(설정 요지)
- `main`: 보호, 리뷰 1회 필수, 강제 푸시/삭제 금지, 대화 해결 필수
- `production`: 보호, 리뷰 1회 필수, 강제 푸시/삭제 금지, 대화 해결 필수, "필수 상태 체크(Required status checks)" 적용(현재 "Update Review Checks" 지정, 추후 빌드/테스트로 교체)
- 기타 브랜치: 필요 시 보호 규칙 추가

## 라벨 체계
- 영역(area/*): `area/auth`, `area/admin`, `area/frontend`, `area/tenancy`, `area/devops`, `area/quality`, `area/ci`, `area/qa`, `area/docs`
- 우선순위(priority/*): `priority/P1` (긴급/핵심)
- 필요 시 확장: `type/bug`, `type/feature`, `type/docs` 등

## 마일스톤
- 예: `Project 1 — 인증/기초 화면/환경`
- 각 프로젝트/스프린트 단위로 생성하며, 이슈는 해당 마일스톤에 귀속

## 이슈/PR 템플릿
- 이슈 템플릿 경로: `.github/ISSUE_TEMPLATE/`
  - `bug_report.md`, `feature_request.md`, `config.yml`
- PR 템플릿: `.github/PULL_REQUEST_TEMPLATE.md`
- 기본 담당자: `@bluelucifer`

## CODEOWNERS
- 경로: `.github/CODEOWNERS`
- 기본 소유자: `@bluelucifer`
- 주요 디렉터리(문서/워크플로우/백엔드/프런트)에 동일 지정

## 커밋 컨벤션(권장)
- 접두사: `feat:`, `fix:`, `chore:`, `docs:`, `refactor:`, `test:`, `ci:`
- 예: `chore: CODEOWNERS & unify issue/PR templates`

## 리뷰 규칙
- 최소 1인 승인(보호 규칙에 따름)
- 대화(Conversation) 미해결 시 머지 금지
- CODEOWNERS 요청 시 지정 검토자 우선

## 워크플로우 정책
- 문서 변경 시: `.github/workflows/review-checks.yml` 동작 → `docs/review/checks/*.md` 자동 갱신
- 프로덕션 브랜치: 현재 "Update Review Checks" 성공을 필수 체크로 설정됨(프로젝트 1 내 강화 예정)
- 프로젝트 1 진행 중 빌드/테스트 워크플로우를 추가하고, 필수 상태 체크를 해당 워크플로우로 교체 예정

## 문서 간 참조 원칙
- 핵심 문서 교차 링크 보장:
  - 화이트페이퍼: `docs/whitepaper.md`
  - 인증 설계: `docs/auth.md`
  - 환경 구성: `docs/devops/environments.md`
  - 프로젝트 1: `docs/milestones/project-1.md`
  - QA 체크: `docs/qa/checklist.md`
  - CLAUDE 가이드: `CLAUDE.md`, `CLAUDE.local.md`

## 프로젝트 1 — 정의된 산출물(DoD)
- Firebase+Sanctum 인증 플로우 문서화 및 스켈레톤 라우트 명세
- Filament/Nova 접근 보호 확인
- 고객앱 부트스트랩 플로우(문서 기준) 정리
- 환경별 도메인/쿠키/CORS 구성 문서화
- QA 체크리스트 수립 및 워크플로우 동작 확인

## 버전 상향 정책(Version Upgrade Policy)
- 목적: 프레임워크/라이브러리(예: Laravel, Filament, Nova, React)의 메이저/마이너 버전 상향 시 안정적으로 반영
- 원칙
  - 문서 우선: `README.md`, `CLAUDE.md`, 관련 스펙 문서의 버전을 먼저 갱신하고 PR로 제안
  - 최소 변경: 코드 반영은 보일러플레이트/설정부터 단계적으로 진행
  - 호환성 검증: changelog/업그레이드 가이드 참고(호환성 이슈 리스트업)
  - 린트/정적분석: `pint --test`, `larastan` 통과 후 커밋
  - 브랜치 전략: `chore/version-upgrade-<stack>` 브랜치에서 작업, PR 경유
- 절차
  1) 문서 갱신 PR: `README.md` 소개/기술 스택, `CLAUDE.md` 버전 기준, 관련 문서 링크 동기화
  2) 보일러플레이트/설정 반영: 예) `composer.json` 제안, `laravel/boost` 지침 준수
  3) 호환성 수정: Deprecated/Breaking 변경 대응(컨트롤러/미들웨어/빌드 설정 등)
  4) 검증: 로컬/스테이징 빌드, 주요 화면 스모크 테스트, QA 체크리스트 일부 수행
  5) 머지: 리뷰 승인 + 필수 체크 통과 후 병합, 릴리즈 노트에 변경 요약
- 참고 문서
  - `CLAUDE.md`(추가 레퍼런스), `docs/milestones/project-1.md`(보일러플레이트/워크플로우)

## 책임/담당(초기)
- 기본 담당: `@bluelucifer`
- 모든 Project 1 이슈: `@bluelucifer` 할당(필요시 재할당)

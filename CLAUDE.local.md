# CLAUDE.local — 로컬 개발 가이드(Claude Code 전용)

본 문서는 로컬 환경에서 Claude Code가 안전하고 일관되게 개발을 수행하기 위한 체크리스트/가드레일을 제공합니다. 모든 사고/응답은 한국어로 합니다.

## 로컬 기본 원칙
- 브랜치 전략 준수: `feature/*`, `chore/*`, `fix/*`
- main/production 직접 푸시 금지, PR 경유
- 작은 단위 커밋/변경(원자적 PR)
- 보안 비밀(.env 등) 커밋 금지

## 파일 편집 규칙
- 문서 우선: `docs/*`를 먼저 갱신 → 그다음 코드 변경
- 변경 시 항상 경로 명시: 예) `docs/auth.md`, `.github/workflows/...`
- 대규모 변경(>300줄)은 분할하여 여러 PR로 제출
- 교차 참조 링크 강화: 관련 문서 서로 연결

## 커밋/PR 규칙(요약)
- 커밋 메시지 접두사: `feat:`, `fix:`, `chore:`, `docs:`, `refactor:`, `test:`, `ci:`
- PR 제목: `type(scope): summary` 또는 `chore: ...`
- PR 본문: 목적/변경점/체크리스트/참고 링크 포함
- CODEOWNERS 자동 리뷰 라우팅 존중

## 로컬 점검(코드 없이도 가능한 최소 점검)
- PHP/Composer 버전 확인: `php -v`, `composer -V`
- (프런트 추가 시) Node 확인: `node -v`
- 문서 워크플로우 동작: `docs/**` 변경 → PR 생성 시 `Update Review Checks` 실행 확인

## 도메인/세션/테넌시 정책(주의)
- 동일 루트(서브도메인) Sanctum SPA 세션 정책을 임의로 변경하지 말 것
- 환경변수/쿠키 도메인/CORS는 `docs/devops/environments.md` 준수
- 호스트 기반 테넌시 설계는 `docs/tenancy/host-middleware.md` 준수

## 작업 흐름 예시
1) 문서 변경: `docs/milestones/project-1.md` → 워크플로우 계획 보강
2) 기능 브랜치 생성: `git checkout -b chore/p1-workflow-docs`
3) 커밋/푸시 → PR 생성 → 리뷰/머지
4) 필요 시 후속 PR: 워크플로우 파일 추가 → 보호 규칙 Required status checks 전환

## 금지 사항
- 보호 규칙 우회(강제 푸시/직접 병합)
- 대규모 일괄 변경 PR 1건
- 문서 없이 코드만 변경

## 참조 문서
- 저장소 규칙: `docs/repo/rules.md`
- 프로젝트 1: `docs/milestones/project-1.md`
- 인증/세션: `docs/auth.md`
- 환경/도메인: `docs/devops/environments.md`
- QA: `docs/qa/checklist.md`
- 모든 생성 파일에 함수 또는 클래스 생성 전에 주석을 먼저 생성할 것.
# .claude — 프로젝트 전용 에이전트 구성

본 디렉터리는 프로젝트 레벨의 Claude Code 서브 에이전트를 정의합니다. 사용자 레벨(`~/.claude/agents/`)보다 우선 적용됩니다.

## 구조
- `agents/` — 에이전트 정의(Markdown + YAML frontmatter)
- `pipelines/` — 멀티에이전트 파이프라인 정의(YAML)

## 원칙
- 에이전트 프롬프트는 짧고 명확하게(토큰 최적화)
- 필요한 툴/권한만 부여(최소 권한)
- 파일은 접두 숫자로 정렬(예: `01_`, `02_`)

## 참조
- CLAUDE 가이드: `../CLAUDE.md`
- 서브 에이전트 규격서: `../docs/claude/subagents.md`

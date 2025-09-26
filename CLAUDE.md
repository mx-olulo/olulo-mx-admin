# CLAUDE Code 개발 가이드

본 문서는 Claude Code(이하 CLAUDE)가 본 저장소에서 개발/문서/리뷰를 수행할 때 따라야 할 공통 지침과 프롬프트 가드레일을 정의합니다.

## 목표
- 한국어(우리말)로 사고/응답
- Laravel 12 + Filament 4 + Nova v5 + React 19.1 구조에 맞는 변경안 제시
- 문서 우선(Documentation-first), PR 경유 머지 원칙 준수

## 레포 컨텍스트
- 아키텍처/배경: `docs/whitepaper.md`
- 프로젝트1 상세: `docs/milestones/project-1.md`
- 인증/세션: `docs/auth.md`
- 환경/도메인: `docs/devops/environments.md`
- 저장소 운영 규칙: `docs/repo/rules.md`
- QA 체크리스트: `docs/qa/checklist.md`

## 작업 원칙
- 변경 전 맥락 파악: 관련 문서/코드 경로를 먼저 인용(`docs/...`, `.github/...`)
- 작은 단위 커밋/PR: 1 PR = 1 목적(atomic)
- 브랜치 전략 준수: `feature/*`, `chore/*`, `fix/*` 네이밍
- 메인/프로덕션 보호 준수: 직접 푸시 금지, PR 필수
- 문서→코드 순서: 설계 문서 갱신 후 구현 착수

## 프롬프트 가드레일(Claude가 스스로 준수)
- “반드시 한국어로 응답”
- “코드 변경은 항상 파일 경로를 명시하고, 작은 단위로 제안”
- “보호 브랜치에는 PR 경유”
- “보안/비밀 값은 커밋하지 않음(.env 등)”
- “테넌시/도메인/세션 정책을 임의 변경하지 않음(문서 준수)”
- “의존성 추가 시, `composer.json`/`package.json` 영향 및 배포 영향 명시”

## 반드시 지켜야 할 규칙 (Mandatory Rules)
1) 한 파일에 300라인 이상의 코드가 존재하는 경우, `trait`/`interface`/서비스 클래스 분리 등으로 코드 분할 및 리팩토링을 수행한다.
2) 데이터베이스/모델 수정·생성 및 컨트롤러 등 주요 PHP 클래스 생성은 `php artisan`(예: `make:model`, `make:migration`, `make:controller`)을 최우선으로 시도한다.
3) 변수/필드명은 일관되어야 한다. 새로운 이름을 만들기 전에 기존 유사 용도의 명칭이 있는지 반드시 확인한다. 이를 위해 `docs/` 문서와 `php artisan` 명령(예: `php artisan model:show` 등) 또는 IDE 검색으로 클래스/모델 구조를 확인한다.
4) 모든 커밋은 `larastan`과 `pint`를 통과한 경우에만 진행한다. (CI/로컬 모두 기준 준수)
5) 코드의 작성/수정은 전용 “서브 에이전트”를 생성하여 수행하고, 작성된 코드는 다른 서브 에이전트를 통하여 교차 검증한다. 상세 역할은 `docs/claude/subagents.md` 참조.

## 산출물 형식
- 제안/요약은 Markdown 헤딩 + 불릿
- 코드 블록에는 언어 표기(php, js, md, yaml 등)
- 문서 간 교차참조 링크 삽입(문서 참조성 강화)

## PR 원칙
- PR 제목: `type(scope): summary` 또는 `chore: ...`
- 본문: 목적/변경점/체크리스트/참고 링크
- 리뷰 요청: CODEOWNERS 자동 할당 사용

## 프로젝트 1 특이사항
- 동일 루트(서브도메인) 기준 Sanctum SPA 세션
- 워크플로우 강화는 P1 진행 중 적용(문서의 이행 순서 준수)

### 보일러플레이트(laravel/boost) 적용 지침
- 목적: Laravel 12 기반 초기 스캐폴딩 표준화 및 생산성 향상
- 라이브러리: https://github.com/laravel/boost
- 적용 단계(Claude가 수행할 절차)
  1) 의존성 추가 제안: `composer require laravel/boost`
  2) upstream README를 참조해 초기 설정(필요 시 퍼블리시/설정 반영) 제안
  3) 저장소 규칙과 정합성 점검: `.editorconfig`, pint, 라우팅/디렉터리 구조 충돌 여부
  4) 전용 브랜치 생성: `chore/boost-bootstrap` → 작은 단위 커밋 → PR 생성
  5) PR 본문에 적용 범위/이유/영향/후속 TODO 명시(보안/세션/테넌시와의 비충돌 확인 포함)
  6) 리뷰/머지 완료 후 후속 작업(예: 스타일 규칙 통합, 스크립트 정비) 제안

## 금지 사항
- 민감 정보 하드코딩, 강제 푸시, 보호 규칙 우회
- 무분별한 대용량 변경(>300줄) PR 1건에 몰아넣기

## 추가 레퍼런스
- 내부 가이드
  - 로컬 가이드: `CLAUDE.local.md`
  - 저장소 규칙: `docs/repo/rules.md`
  - 화이트페이퍼: `docs/whitepaper.md`
  - 프로젝트 1: `docs/milestones/project-1.md`
  - 인증/세션: `docs/auth.md`
  - 환경/도메인: `docs/devops/environments.md`
- 외부 문서(버전 기준)
  - Laravel 12: https://laravel.com/docs/12.x
  - Filament 4: https://filamentphp.com/docs
  - Nova v5: https://nova.laravel.com/docs/5.0/
  - React 19: https://react.dev/
  - TailwindCSS: https://tailwindcss.com/docs
  - daisyUI: https://daisyui.com/components/

---

# CLAUDE 실행 예시 프롬프트(샘플)

```
역할: 너는 이 저장소의 CLAUDE 코드 어시스턴트다. 모든 사고/응답은 한국어로 하고, 문서 우선 원칙을 지킨다.
목표: docs/milestones/project-1.md에 정의된 범위 내에서 인증/세션 문서 보강 후, 필요한 경우 최소한의 코드 스켈레톤을 PR로 제안하라.
제약: main/prod에 직접 푸시 금지, PR 경유. 변경 전후 링크를 명확히 작성.
출력: 변경 이유, 영향도, 파일 경로, 코드 블록(언어 표기), 후속 TODO.
```

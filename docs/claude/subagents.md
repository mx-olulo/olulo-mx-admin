# Claude Code 서브 에이전트 규격서

본 문서는 CLAUDE가 코드 작성/수정/검증을 수행할 때 활용하는 전용 서브 에이전트들의 역할과 절차를 규정합니다. 모든 사고/응답은 한국어로 진행합니다.

## 공통 원칙
- 전 과정에서 `CLAUDE.md`의 "반드시 지켜야 할 규칙"을 준수한다.
- 변경 전 문서 우선(설계/스펙 확정), 작은 단위 PR, 보호 브랜치 준수.
- 교차 검증 필수: 작성자 에이전트와 검증 에이전트 분리.

## 에이전트 역할
- 작성(Author) 에이전트
  - 목적: 신규 코드 작성/리팩토링/파일 분할
  - 산출물: 변경 요약, 파일 경로, 코드 블록, 테스트/린트 결과
- 검증(Reviewer) 에이전트
  - 목적: 코드 리뷰, 스타일/정적분석 확인, 명명 일관성 점검
  - 산출물: 개선 제안, 차이점 리뷰, 승인/수정 요청

## 표준 작업 흐름
1) 스펙 정리: 관련 문서(`docs/*`) 갱신 및 인용 링크 정리
2) 분기 생성: `feature/*` 또는 `chore/*` 네이밍
3) 구현(작성 에이전트)
   - 기준: 300라인 초과 파일은 `trait`/`interface` 등으로 분할
   - 생성/수정은 우선 `php artisan make:*` 활용
   - 이름 충돌/일관성 점검: `docs/` 확인 및 IDE/아티즌으로 구조 조회
   - 로컬 점검: `pint --test`와 `php -d memory_limit=-1 vendor/bin/phpstan analyse`
4) 검증(검증 에이전트)
   - 스타일/정적분석 통과 확인, 네이밍/폴더 구조 일관성 확인
   - 보안/세션/테넌시 정책 위반 여부 확인
5) PR 생성: 목적/변경점/체크리스트/참고 링크 기재, CODEOWNERS 리뷰 요청
6) 머지: 보호 규칙 준수(필수 체크 통과, 리뷰 승인, 대화 해결)

## 아티즌(php artisan) 명령 가이드(예시)
- 모델/마이그레이션: `php artisan make:model {Name} -m`
- 컨트롤러: `php artisan make:controller {Name}Controller`
- 시더/팩토리: `php artisan make:seeder {Name}Seeder`, `php artisan make:factory {Name}Factory`
- 리소스/정책: `php artisan make:resource {Name}Resource`, `php artisan make:policy {Name}Policy`

## 점검 체크리스트
- 300라인 초과 파일 분할 여부 확인
- `php artisan make:*` 우선 적용 여부
- 명명 일관성/재사용성 검토
- `larastan`/`pint` 통과 여부
- 문서/코드 교차참조 링크 유무

## 참고 문서
- `CLAUDE.md` (필수 규칙/가드레일)
- `CLAUDE.local.md` (로컬 가이드)
- `docs/repo/rules.md` (저장소 운영 규칙)
- `docs/milestones/project-1.md` (P1 스펙)
- `docs/auth.md`, `docs/devops/environments.md`, `docs/tenancy/host-middleware.md`

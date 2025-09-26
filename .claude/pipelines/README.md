# Claude 파이프라인 가이드

본 디렉토리는 프로젝트 전용 Claude Code 서브에이전트 파이프라인을 관리합니다.

## 파이프라인 종류

### 1. lightweight.yaml (경량화)
**적용 상황:**
- 파일 5개 이하 변경
- 100줄 이하 수정
- 핫픽스, 타이포, 마이너 변경
- 문서만 수정

**특징:**
- 실행 시간: 2분 이내
- 동적 전문가 선택 (파일 타입별)
- 위험도 기반 조건부 실행
- 빠른 승인/배포

### 2. default.yaml (기본)
**적용 상황:**
- 표준 개발 워크플로우
- 파일 10개 이하 변경
- 300줄 이하 수정
- 단일 스택 변경

**특징:**
- author → reviewer → github-expert 순차 실행
- 문서 우선 원칙 적용
- pint, larastan 필수 실행
- 브랜치: `chore/{short}`

### 3. optimized.yaml (최적화)
**적용 상황:**
- 멀티스택 변경 (Laravel + React 등)
- 파일 25개 이하 변경
- 기능 개발, 개선사항
- 프런트엔드+백엔드 동시 변경

**특징:**
- 조건부 실행 (변경된 파일 기준)
- 병렬 처리 (최대 3개 동시)
- 컨텍스트 인식 에이전트 선택
- 실행 시간: 8-12분
- 브랜치: `feature/{scope}-{short}`

### 4. extended.yaml (확장)
**적용 상황:**
- 대규모 아키텍처 변경
- 파일 25개 초과
- 마일스톤, 메이저 기능
- 마이그레이션 포함 변경

**특징:**
- 모든 15개 에이전트 순차 실행
- 종합적 품질 검토
- PM, UX, 아키텍처 리뷰 포함
- 실행 시간: 15-20분
- 브랜치: `feature/{short}`

## 서브에이전트 매핑

| 에이전트 ID | 파일명 | 전문 분야 |
|------------|--------|-----------|
| coordinator | 14_coordinator.md | 작업 조정, 전략 수립 |
| architect | 03_architect.md | 아키텍처 설계, 위험 분석 |
| code-author | 01_code-author.md | 코드 작성, 구현 |
| code-reviewer | 02_code-reviewer.md | 코드 검토, 품질 관리 |
| laravel-expert | 04_laravel-expert.md | Laravel 프레임워크 |
| filament-expert | 05_filament-expert.md | Filament 관리자 패널 |
| nova-expert | 06_nova-expert.md | Nova 관리자 패널 |
| react-expert | 07_react-expert.md | React 19.1 프런트엔드 |
| database-expert | 08_database-expert.md | 데이터베이스, 마이그레이션 |
| docs-reviewer | 09_docs-reviewer.md | 문서 검토, 기술 문서 |
| tailwind-expert | 10_tailwind-expert.md | TailwindCSS 스타일링 |
| livewire-expert | 11_livewire-expert.md | Livewire 컴포넌트 |
| ux-expert | 12_ux-expert.md | 사용자 경험, UI/UX |
| pm | 13_pm.md | 프로젝트 관리, 요구사항 |
| github-expert | 15_github-expert.md | Git, GitHub 작업 |

## 자동 파이프라인 선택

`meta.yaml`이 변경 특성을 분석하여 자동으로 적절한 파이프라인을 선택합니다:

1. **파일 수**, **변경 라인 수**, **스코프 키워드** 기준
2. **기술 스택 조합** (프런트엔드+백엔드 동시 여부)
3. **아키텍처 영향도** (마이그레이션, 설정 변경 등)
4. **도메인 영향 범위** (멀티 도메인 변경 여부)

## 성능 최적화 기능

### 조건부 실행
변경된 파일/내용에 따라 관련 에이전트만 실행:
- PHP 파일 → Laravel, Database 전문가
- React 파일 → React, Tailwind, UX 전문가
- 문서 파일 → Docs 리뷰어만
- 마이그레이션 → Database, Architecture 전문가

### 병렬 처리
독립적인 리뷰 단계를 동시 실행:
- 백엔드 리뷰 (Laravel + Database)
- 관리자 리뷰 (Filament + Nova)
- 프런트엔드 리뷰 (React + Tailwind + Livewire)

### 캐싱 및 최적화
- 콘텐츠 해시 기반 캐싱
- 조기 종료 (실패 시)
- 타임아웃 관리 (단계별)

## 품질 관리

### 필수 규칙 (모든 파이프라인)
- `require_docs_first: true` - 문서 우선 원칙
- `require_pint: true` - 코드 스타일 검사
- `require_larastan: true` - 정적 분석 (lightweight 제외)
- `enforce_naming_consistency: true` - 네이밍 일관성

### 품질 임계값
- 최소 점수: 6.0/10
- 경고 점수: 7.0/10
- 우수 점수: 9.0/10

## 사용 예시

```bash
# 자동 선택 (권장)
claude --pipeline auto "사용자 모델에 프로필 이미지 필드 추가"

# 수동 선택
claude --pipeline lightweight "README 타이포 수정"
claude --pipeline default "Laravel 컨트롤러 리팩토링"
claude --pipeline optimized "매장 관리 기능 추가 (Filament + React)"
claude --pipeline extended "멀티테넌시 아키텍처 구현"
```

## 모니터링 및 보고

파이프라인 실행 성과는 다음 지표로 추적됩니다:
- **실행 시간** (파이프라인별, 에이전트별)
- **에이전트 활용률** (조건부 실행 효과)
- **성공률** (승인/거부 비율)
- **품질 점수** 분포

## 문제 해결

### 자주 발생하는 문제
1. **에이전트 파일 없음**: `.claude/agents/` 경로 확인
2. **조건부 실행 실패**: 파일 경로 패턴 점검
3. **병렬 처리 오류**: 의존성 충돌 확인
4. **타임아웃**: 변경 범위 축소 또는 extended 파이프라인 사용

### 디버깅
```bash
# 파이프라인 검증
claude --validate-pipeline optimized.yaml

# 에이전트 매핑 확인
claude --check-agents

# 성능 보고서
claude --pipeline-report --last-week
```

## 업데이트 가이드

파이프라인 수정 시 다음 순서를 따르세요:

1. **테스트 환경**에서 파이프라인 검증
2. **작은 변경**으로 동작 확인
3. **문서 업데이트** (`README.md`, `meta.yaml`)
4. **PR 생성** 및 코드 리뷰
5. **단계적 배포** (lightweight → default → optimized → extended)

---

*이 가이드는 프로젝트의 서브에이전트 시스템이 업데이트될 때마다 함께 갱신됩니다.*
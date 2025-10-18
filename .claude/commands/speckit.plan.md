---
description: 계획 템플릿을 사용하여 구현 계획 워크플로를 실행하여 설계 아티팩트를 생성합니다.
---

## 사용자 입력

```text
$ARGUMENTS
```

입력이 비어있지 않으면 계속 진행하기 전에 **반드시** 사용자 입력을 고려해야 합니다.

## 개요

1. **설정**: 저장소 루트에서 `.specify/scripts/bash/setup-plan.sh --json`을 실행하고 FEATURE_SPEC, IMPL_PLAN, SPECS_DIR, BRANCH에 대한 JSON을 구문 분석합니다. "I'm Groot"와 같은 인수의 작은따옴표의 경우 이스케이프 구문을 사용: 예) 'I'\''m Groot' (또는 가능하면 큰따옴표: "I'm Groot").

2. **컨텍스트 로드**: FEATURE_SPEC 및 `.specify/memory/constitution.md`를 읽습니다. IMPL_PLAN 템플릿을 로드합니다 (이미 복사됨).

3. **계획 워크플로 실행**: IMPL_PLAN 템플릿의 구조를 따라:
   - 기술 컨텍스트 채우기 (알 수 없는 것은 "NEEDS CLARIFICATION"으로 표시)
   - 헌장에서 헌장 확인 섹션 채우기
   - 게이트 평가 (정당화되지 않은 위반인 경우 ERROR)
   - 0단계: research.md 생성 (모든 NEEDS CLARIFICATION 해결)
   - 1단계: data-model.md, contracts/, quickstart.md 생성
   - 1단계: 에이전트 스크립트를 실행하여 에이전트 컨텍스트 업데이트
   - 설계 후 헌장 확인 재평가

4. **중지 및 보고**: 2단계 계획 후 명령 종료. 브랜치, IMPL_PLAN 경로 및 생성된 아티팩트 보고.

## 단계

### 0단계: 개요 및 연구

1. **위의 기술 컨텍스트에서 알 수 없는 것 추출**:
   - 각 NEEDS CLARIFICATION → 연구 작업
   - 각 종속성 → 모범 사례 작업
   - 각 통합 → 패턴 작업

2. **연구 에이전트 생성 및 발송**:
   ```
   기술 컨텍스트의 각 알 수 없는 것에 대해:
     작업: "{기능 컨텍스트}에 대한 {알 수 없는 것} 연구"
   각 기술 선택에 대해:
     작업: "{도메인}에서 {기술}에 대한 모범 사례 찾기"
   ```

3. **`research.md`에 결과 통합** 형식 사용:
   - 결정: [선택된 것]
   - 근거: [선택된 이유]
   - 고려된 대안: [평가된 다른 것]

**출력**: 모든 NEEDS CLARIFICATION이 해결된 research.md

### 1단계: 설계 및 계약

**전제 조건:** `research.md` 완료

1. **기능 명세에서 엔티티 추출** → `data-model.md`:
   - 엔티티 이름, 필드, 관계
   - 요구사항에서 검증 규칙
   - 해당되는 경우 상태 전환

2. **기능 요구사항에서 API 계약 생성**:
   - 각 사용자 작업 → 엔드포인트
   - 표준 REST/GraphQL 패턴 사용
   - `/contracts/`에 OpenAPI/GraphQL 스키마 출력

3. **에이전트 컨텍스트 업데이트**:
   - `.specify/scripts/bash/update-agent-context.sh claude` 실행
   - 이 스크립트는 사용중인 AI 에이전트를 감지합니다
   - 적절한 에이전트별 컨텍스트 파일을 업데이트합니다
   - 현재 계획의 새로운 기술만 추가합니다
   - 마커 사이의 수동 추가를 유지합니다

**출력**: data-model.md, /contracts/*, quickstart.md, 에이전트별 파일

## 주요 규칙

- 절대 경로 사용
- 게이트 실패 또는 미해결 명확화에 대한 ERROR

## 사고 및 응답
반드시 모든 흐름의 출력과 응답, 사고는 우리말(한글)로 진행하시오.

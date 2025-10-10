# RBAC (Teams + Scopes) 체크리스트

- 목적: Spatie Permission v6 Teams 활성 + 다형 스코프(scopes) 도입 작업의 완료 여부를 신뢰성 있게 판단
- 범위: 설정, 데이터베이스, 애플리케이션(리졸버/미들웨어), 시드, 테스트, 문서

## 1) 설정(Config)
- [x] `config/permission.php`에서 `teams = true` 적용됨
- [x] `team_resolver`는 기본값(`DefaultTeamResolver`) 사용 (Spatie 공식 방식)
- [x] `ScopeContextService`에서 `setPermissionsTeamId()` 호출로 통합
- [ ] 구성/캐시 초기화 절차 문서화(`config:clear`, `cache:clear` 실행 순서 포함)

## 2) 데이터베이스(Database)
- [x] `scopes` 테이블 생성됨 (PK, Unique(scope_type, scope_ref_id), 인덱스 포함)
- [x] `Scope` 모델 구현 (`findOrCreateScope()`, 다형 관계)
- [x] `roles`에 `team_id` 존재, (`team_id`, `name`, `guard_name`) UNIQUE 보장
- [x] `model_has_roles` 복합 PK에 `team_id` 포함 및 인덱스 존재
- [x] `model_has_permissions` 복합 PK에 `team_id` 포함 및 인덱스 존재
- [x] `role_has_permissions` 정상 생성(팀 비의존)
- [x] `migrate:fresh`가 로컬에서 무오류 수행됨
- [ ] `migrate:fresh` + 시드가 CI에서 무오류 수행됨

## 3) 애플리케이션(Application)
- [x] `ScopeContextService` 구현 (세션 기반 스코프 관리)
- [x] `setScope()` 호출 시 자동으로 `setPermissionsTeamId()` 실행
- [x] `clearScope()` 호출 시 자동으로 `setPermissionsTeamId(null)` 실행
- [x] 헬퍼 함수 구현 (`scopeContext()`, `currentScopeTeamId()`)
- [ ] `SetScopeContext` 미들웨어가 등록되어 컨텍스트 주입/검증 흐름을 보장
- [ ] 유효하지 않은 스코프 시 403 또는 명시적 예외 처리 정책이 적용됨
- [ ] 컨텍스트 미설정 시의 기본 동작 정의(예: 글로벌 team=null 허용 여부) 문서화

## 4) 시딩(Seeding)
- [ ] 글로벌 롤/권한이 필요한 경우(team=null)로 생성 정책 정의 및 반영
- [ ] 스코프 전용 롤(예: org_admin, brand_manager, store_staff) 시드 정책 정의 및 샘플 데이터 포함
- [ ] 샘플 스코프(Org/Brand/Store) 레코드와 사용자-롤 매핑 시드가 준비됨

## 5) 권한/정책(Authorization)
- [ ] 초기 릴리스는 **스코프 내 명시적 권한만** 허용(계승 비활성)
- [ ] 후속 릴리스에서 계승 규칙(Org→Brand/Store, Brand→Store) 도입 계획 문서화
- [ ] 정책/게이트에서 컨텍스트 기준의 체크 흐름 합의됨

## 6) UI/UX & DX
- [ ] 컨텍스트 스위처(상단) UX 초안 및 라우팅 규칙 합의
- [ ] 403 응답 시 컨텍스트 전환 CTA 제공 지침 문서화
- [ ] 개발자 가이드에 컨텍스트 세팅/전파/검증 예시 흐름 추가(코드 없이 개념/절차만)

## 7) 테스트(Testing)
- [ ] 컨텍스트 A에서 부여된 롤이 컨텍스트 B에서는 효력을 갖지 않음을 확인
- [ ] 컨텍스트별 `hasRole`/`can`이 기대대로 동작함을 수동/자동 테스트로 검증
- [ ] 컨텍스트 미설정/오설정 시 403 또는 의도된 실패 경로 확인
- [ ] 롤/권한 변경 시 캐시 플러시/무효화로 즉시 반영되는지 점검

## 8) 문서(Docs)
- [ ] `docs/rbac-scope-teams-migration-plan.md` 최신 상태 반영
- [ ] `docs/rbac-scope-teams-todo.md` 진행 상황 주기적 업데이트
- [ ] `docs/roles-and-permissions.md`에 Teams + Scopes 전환 내용 추가됨

## 9) 최초 로그인 온보딩(무소속 사용자)
- [ ] 무소속 사용자 감지 흐름 문서화(미들웨어/가드 개념)
- [ ] 온보딩 선택 화면(스토어/조직) UX 개념 문서화
- [ ] 스토어/조직 생성 위자드 단계/필드 정의 문서화
- [ ] 위자드 완료 시 컨텍스트(`scopes.id`) 바인딩 정책 문서화
- [ ] 온보딩 중 보호 라우트 접근 제한/리다이렉트 규칙 문서화

## 10) 브랜치/PR 운영
- [ ] 메인 기능 브랜치 `feature/rbac-teams-scopes` 생성
- [ ] 서브 브랜치 전략 수립 및 문서화(`config`, `migrations`, `resolver-middleware`, `seeding`, `onboarding`, `tests`, `docs`)
- [ ] 서브 PR 단위 리뷰/수정 후 메인 기능 브랜치로 머지하는 프로세스 준수

## 수락 기준(Acceptance Criteria)
- [ ] 동일 사용자에 대해 서로 다른 스코프에서 상이한 롤을 부여/검증 가능
- [ ] 컨텍스트 전환 시 메뉴/리소스 스코핑이 즉시 반영(권한 상승/하향 없음)
- [ ] CI에서 `migrate:fresh --seed`가 통과하고, 기본 시나리오 테스트가 그린
- [ ] 운영 코드에 PII/비밀키/하드코딩 값 없이 환경설정 기반으로 동작

# RBAC (Teams + Scopes) 체크리스트 (Role = Tenant)

## 1) 설정(Config)
- [x] `config/permission.php` → `'role' => App\\Models\\Role::class`
- [x] Filament Panel → `->tenant(\App\Models\Role::class)`
- [x] Filament Panel → `->tenantMiddleware([SetSpatieTeamId::class], isPersistent: true)`
- [ ] 다중 Panel 아키텍처 전환 (Organization/Brand/Store 별도 Panel)
- [ ] 구성/캐시 초기화 절차 문서화(`config:clear`, `cache:clear` 실행 순서 포함)

## 2) 데이터베이스(Database)
- [x] `roles` 테이블에 `scope_type`, `scope_ref_id` 추가 마이그레이션 적용
- [x] `team_id`는 Spatie 표준 bigint 유지
- [x] `roles`에 (`team_id`, `name`, `guard_name`) UNIQUE 보장
- [x] `model_has_roles` 복합 PK에 `team_id` 포함 및 인덱스 존재
- [x] `model_has_permissions` 복합 PK에 `team_id` 포함 및 인덱스 존재
- [x] `migrate:fresh`가 로컬에서 무오류 수행됨
- [ ] `migrate:fresh` + 시드가 CI에서 무오류 수행됨

## 3) 애플리케이션(Application)
- [x] `User`가 `HasTenants` 구현 (`getTenants()`: team_id 있는 roles 반환, `canAccessTenant()` 구현)
- [x] `SetSpatieTeamId` 미들웨어에서 `setPermissionsTeamId($tenant->team_id)` 적용
- [x] `SetSpatieTeamId` 미들웨어가 Filament Panel에 등록됨
- [x] 글로벌 헬퍼(선택): `currentTenant()`, `currentTeamId()` 주석/제거 기준 포함
- [ ] Filament 테넌트 미선택 시 기본 동작 정의 (예: 첫 번째 Role 자동 선택 또는 선택 화면)
- [ ] 유효하지 않은 테넌트 접근 시 403 또는 명시적 예외 처리 정책 적용

## 4) 시딩(Seeding)
- [ ] 글로벌 롤/권한이 필요한 경우(team_id=null)로 생성 정책 정의 및 반영
- [ ] 스코프 전용 롤(예: org_admin, brand_manager, store_staff) 시드 정책 정의
- [ ] 샘플 스코프(Org/Brand/Store) 레코드와 사용자-롤 매핑 시드 준비
- [ ] Role 생성 시 `team_id`, `scope_type`, `scope_ref_id` 일관성 보장

## 5) 권한/정책(Authorization)
- [x] `hasRole()` / `can()`은 Filament가 설정한 현재 테넌트(Role)의 `team_id` 컨텍스트에서 동작
- [ ] 초기 릴리스는 **스코프 내 명시적 권한만** 허용(계승 비활성)
- [ ] 후속 릴리스에서 계승 규칙(Org→Brand/Store, Brand→Store) 도입 계획 문서화
- [ ] 정책/게이트에서 테넌트 컨텍스트 기준의 체크 흐름 합의됨

## 6) UI/UX & DX
- [ ] Filament 테넌트 스위처 UI 동작 확인 (자동 제공)
- [ ] 테넌트 선택 시 URL 변경 확인 (`/admin/{tenant}/...`)
- [ ] 403 응답 시 테넌트 전환 CTA 제공 지침 문서화
- [ ] 개발자 가이드에 테넌트 설정/전파/검증 예시 흐름 추가
- [ ] `Role::getTenantName()` 실제 엔터티명 표시 구현 (Organization/Brand/Store 이름 조회)

## 7) 테스트(Testing)
- [ ] 테넌트 A에서 부여된 롤이 테넌트 B에서는 효력을 갖지 않음을 확인
- [ ] 테넌트별 `hasRole`/`can`이 기대대로 동작함을 수동/자동 테스트로 검증
- [ ] 테넌트 미설정/오설정 시 403 또는 의도된 실패 경로 확인
- [ ] 롤/권한 변경 시 캐시 플러시/무효화로 즉시 반영되는지 점검
- [ ] Filament 테넌트 전환 시 메뉴/리소스 스코핑 즉시 반영 확인

## 8) 문서(Docs)
- [x] `docs/rbac-filament-tenancy-integration.md` 최신화
- [x] 레거시 문서 Deprecated 처리 완료
- [x] `docs/rbac-scope-teams-migration-plan.md` 최신 상태 반영
- [x] `docs/rbac-scope-teams-checklist.md` 현재 아키텍처 반영
- [x] `docs/rbac-multi-panel-architecture.md` 다중 Panel 아키텍처 문서 작성
- [ ] `docs/roles-and-permissions.md`에 Teams + Scopes 전환 내용 추가

## 9) 최초 로그인 온보딩(무소속 사용자)
- [ ] 무소속 사용자 감지 흐름 문서화 (Role이 없거나 team_id가 없는 경우)
- [ ] 온보딩 선택 화면(스토어/조직) UX 개념 문서화
- [ ] 스토어/조직 생성 위자드 단계/필드 정의 문서화
- [ ] 위자드 완료 시 Role 생성 및 사용자 할당 정책 문서화
- [ ] 온보딩 중 보호 라우트 접근 제한/리다이렉트 규칙 문서화

## 10) 브랜치/PR 운영
- [x] 메인 기능 브랜치 `feature/rbac-teams-scopes-migrations` 생성
- [ ] 서브 브랜치 전략 수립 및 문서화 (`seeding`, `onboarding`, `tests`, `docs`)
- [ ] 서브 PR 단위 리뷰/수정 후 메인 기능 브랜치로 머지하는 프로세스 준수

## 수락 기준(Acceptance Criteria)
- [ ] 동일 사용자에 대해 서로 다른 테넌트(Role)에서 상이한 권한을 부여/검증 가능
- [ ] 테넌트 전환 시 메뉴/리소스 스코핑이 즉시 반영(권한 상승/하향 없음)
- [ ] CI에서 `migrate:fresh --seed`가 통과하고, 기본 시나리오 테스트가 그린
- [ ] 운영 코드에 PII/비밀키/하드코딩 값 없이 환경설정 기반으로 동작
- [ ] Filament 테넌트 스위처가 정상 동작하며 사용자가 접근 가능한 Role 목록 표시

# Spatie Permission Teams 기반 스코프형 RBAC 전환 실행 계획 (Role = Tenant)

## 핵심 결정

- Spatie `teams = true` 유지, `team_id`는 bigint 그대로 사용
- `roles` 테이블에 `scope_type`, `scope_ref_id` 필드 추가 (인덱스 포함)
- Role 모델을 Filament Tenant로 직접 사용 (`->tenant(\App\Models\Role::class)`)
- 미들웨어 `SetSpatieTeamId`에서 `setPermissionsTeamId($tenant->team_id)` 호출
- User는 `HasTenants` 구현: `getTenants()`는 `roles` 중 `team_id` 보유 Role 반환, `canAccessTenant()`는 Role id 확인

## 수행 순서

1. **마이그레이션 적용**
   - `database/migrations/*_add_scope_fields_to_roles_table.php` 실행
   - `scope_type`, `scope_ref_id` 필드 추가
   - 인덱스 `idx_role_scope` 생성

2. **설정 변경**
   - `config/permission.php`의 `models.role` → `App\\Models\\Role::class`

3. **Filament Panel 설정**
   - `app/Providers/Filament/AdminPanelProvider.php`
   - `->tenant(\App\Models\Role::class)` 추가
   - `->tenantMiddleware([SetSpatieTeamId::class], isPersistent: true)` 추가

4. **User 모델 구현**
   - `app/Models/User.php`에 `HasTenants` 인터페이스 구현
   - `getTenants()`: team_id가 있는 roles 반환
   - `canAccessTenant()`: Role id 기준 검증

5. **미들웨어 구현**
   - `app/Http/Middleware/SetSpatieTeamId.php` 생성
   - Filament 테넌트에서 `team_id` 추출하여 Spatie에 설정

6. **헬퍼 함수 (선택)**
   - `app/Support/helpers.php`에 `currentTenant()`, `currentTeamId()` 추가
   - 미사용 시 머지 전 제거

7. **시딩**
   - team별 Role 생성 (`team_id`, `scope_type`, `scope_ref_id`)
   - 사용자에 Role 할당

8. **검증**
   - Filament에서 테넌트 선택
   - `hasRole/can`이 team 컨텍스트에서 동작하는지 테스트
   - 테넌트 전환 시 권한 변경 확인

## 데이터 이전 (해당 시)

- 기존 `scopes` 테이블을 사용하지 않음
- 과거에 있었다면 `roles.team_id`에 대응하는 `scope_type/scope_ref_id`를 채우고 `scopes` 제거

## 체크포인트

- [ ] 마이그레이션 무오류 실행
- [ ] Filament 테넌트 스위처 UI 정상 동작
- [ ] 테넌트별 권한 분리 확인
- [ ] URL 라우팅 `/admin/{tenant}/...` 정상 동작
- [ ] 문서 업데이트 완료

## 다중 Panel 전환 (Phase 2)

현재는 단일 Admin Panel을 사용하지만, 향후 스코프 타입별로 독립적인 Panel로 전환할 계획입니다.

### 전환 목표

- Organization Panel: `/organization/{org_id}/dashboard`
- Brand Panel: `/brand/{brand_id}/dashboard`
- Store Panel: `/store/{store_id}/dashboard`

### 전환 이유

- URL에서 스코프 타입 명확히 구분
- 각 Panel별 독립적인 리소스/메뉴/위젯 관리
- 권한 분리 명확화
- UX 개선 (각 관리 영역에 맞는 UI)

### 상세 계획

`docs/rbac-multi-panel-architecture.md` 문서를 참고하세요.

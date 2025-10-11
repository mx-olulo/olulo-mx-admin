# RBAC (Teams + Scopes) 체스터리스트 (Role = Tenant)

- **[Config]**
  - [x] `config/permission.php` → `'role' => App\\Models\\Role::class`
  - [x] Filament Panel → `->tenant(\App\Models\Role::class)`
  - [x] Filament Panel → `->tenantMiddleware([SetSpatieTeamId::class], isPersistent: true)`
- **[DB]**
  - [x] `roles` 테이블에 `scope_type`, `scope_ref_id` 추가 마이그레이션 적용
  - [x] `team_id`는 Spatie 표준 bigint 유지
- **[App]**
  - [x] `User`가 `HasTenants` 구현 (`getTenants()`: team_id 있는 roles 반환, `canAccessTenant()` 구현)
  - [x] `SetSpatieTeamId` 미들웨어에서 `setPermissionsTeamId($tenant->team_id)` 적용
  - [x] 글로벌 헬퍼(선택): `currentTenant()`, `currentTeamId()` 주석/제거 기준 포함
  - [x] `ScopeContextService` 제거, 세션 기반 스코프 관리 제거
- **[AuthZ]**
  - [x] `hasRole()` / `can()`은 Filament가 설정한 현재 테넌트(Role)의 `team_id` 컨텍스트에서 동작
- **[Docs]**
  - [x] `docs/rbac-filament-tenancy-integration.md` 최신화
  - [x] 레거시 문서 Deprecated 처리 완료

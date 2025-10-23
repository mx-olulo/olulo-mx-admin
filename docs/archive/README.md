# 아카이브 문서

본 디렉토리는 폐기되었거나 대체된 레거시 문서를 보관합니다.

---

## 아카이브 목록

### rbac-filament-tenancy-integration.md.deprecated

**폐기일**: 2025-10-23
**이유**: Spatie Permission 제거로 인한 설계 변경

**내용**: Spatie Permission + Filament Tenancy 통합 방식 문서
- Role 모델을 Filament Tenant로 사용
- `SetSpatieTeamId` 미들웨어 구현
- `team_id` 기반 접근 제어

**대체 문서**: [docs/rbac-system.md](../rbac-system.md)

**주요 변경사항**:
- ❌ Spatie Permission 제거
- ✅ TenantUser 피벗 모델 기반 자체 RBAC 구현
- ✅ Polymorphic M:N 관계 (Organization/Brand/Store)
- ✅ Enum 기반 타입 안전성
- ✅ Fluent API 메서드 체이닝

### rbac-multi-panel-architecture.md.deprecated

**폐기일**: 2025-10-23
**이유**: Role = Tenant 아키텍처 폐기, TenantUser 기반으로 전환

**내용**: Role 모델을 Filament Tenant로 직접 사용하는 다중 Panel 아키텍처
- Role 모델이 테넌트 역할 (team_id, scope_type, scope_ref_id)
- 5개 Panel (Platform/System/Organization/Brand/Store)
- 스코프 검증 미들웨어 (EnsurePlatformScope 등)

**대체 방식**:
- ✅ TenantUser 피벗 모델로 User ↔ Tenant (Organization/Brand/Store) M:N 관계
- ✅ UserType Enum으로 Admin/User/Customer 구분
- ✅ User::canAccessPanel() 메서드로 패널 접근 제어
- ✅ 상세 구현: [docs/rbac-system.md](../rbac-system.md)

**참고**:
- 현재는 5개 독립 Panel (org/brand/store/platform/system) 구현됨
- 각 Panel은 User::canAccessPanel() + User::getTenants()로 접근 제어

---

## 아카이브 정책

**보관 기준**:
- 폐기된 설계 문서
- 대체된 구현 가이드
- 역사적 참고 가치가 있는 문서

**삭제 금지**:
- 레거시 문서는 삭제하지 않고 아카이브로 이동
- `.deprecated` 확장자 추가
- README에 폐기 이유 명시

**복구 절차**:
1. 아카이브 문서 검토
2. 현재 설계와 호환성 확인
3. 필요 시 새 문서로 재작성 (복사 금지)

---

**최종 업데이트**: 2025-10-23
**관리자**: @Alfred

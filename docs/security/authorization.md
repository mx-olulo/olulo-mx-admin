# Authorization (권한 관리)

> **상태**: ⚠️ 이론적 설계 문서 (현재 구현과 부분적으로 다름)
>
> **현재 구현**: TenantUser 기반 RBAC → [rbac-system.md](../rbac-system.md)

**최종 업데이트**: 2025-10-23

---

## 현재 구현 (TenantUser 기반)

**2-Layer 권한 체계**:

```
┌─────────────────────────────────────────────┐
│  Layer 1: User::canAccessPanel()            │
│  (UserType + global_role / tenant_users)    │
└─────────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────┐
│  Layer 2: Policy + TenantUser 권한          │
│  (User->tenant($model)->canManage())        │
└─────────────────────────────────────────────┘
```

### Layer 1: User::canAccessPanel()

**목적**: Filament 패널 접근 권한 확인

**구현**:
- **Customer**: 모든 패널 차단
- **User**: Platform/System 패널 (`global_role` 기반)
- **Admin**: Organization/Brand/Store 패널 (`tenant_users` 기반)

**예시**:
```php
// User 모델
public function canAccessPanel(Panel $panel): bool
{
    if ($this->user_type === UserType::CUSTOMER) {
        return false;
    }

    $scopeType = ScopeType::fromPanelId($panel->getId());

    // Platform/System: global_role 확인
    if ($scopeType === ScopeType::PLATFORM) {
        return $this->hasGlobalRole('platform_admin');
    }

    // Organization/Brand/Store: tenant_users 확인
    return $this->canAccessTenantPanel($panel->getId());
}
```

### Layer 2: Policy + TenantUser 권한

**목적**: 리소스별 세밀한 권한 제어

**구현**:
```php
// OrganizationPolicy
public function update(User $user, Organization $organization): bool
{
    return $user->tenant($organization)->canManage();
}

public function delete(User $user, Organization $organization): bool
{
    return $user->tenant($organization)->isOwner();
}
```

**권한 메서드**:
- `canManage()`: Owner 또는 Manager (생성, 수정)
- `canView()`: 모든 역할 (조회)
- `isOwner()`: Owner만 (삭제)

---

## 관련 문서

- **[rbac-system.md](../rbac-system.md)**: TenantUser 기반 RBAC 상세 구현
- **[roles-and-permissions.md](../roles-and-permissions.md)**: 역할 매핑 테이블

---

## 레거시: 3-Layer 설계 (미구현)

<details>
<summary>초기 설계 문서 (참고용)</summary>

### Layer 1: Gate::before (글로벌 권한)

```php
Gate::before(function (User $user, string $ability) {
    if ($user->hasGlobalScopeRole()) {
        return true;
    }
    return null;
});
```

### Layer 2: Spatie Permission ❌ 제거됨

**이유**: TenantUser 기반으로 대체
- Polymorphic M:N 관계 미지원
- 복잡도 증가

### Layer 3: Policy

현재 Layer 2로 통합됨

</details>

---

**작성자**: @Alfred

<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\ScopeType;
use App\Models\Organization;
use App\Models\User;
use Filament\Facades\Filament;

/**
 * Organization Policy
 *
 * Spatie Permission과 Filament 테넌트를 함께 사용하여 리소스 기반 권한을 체크합니다.
 *
 * 1. Spatie Permission: 세밀한 권한 체크 (view-organizations, create-organizations 등)
 * 2. Filament Tenant: 리소스 소유권 체크 (이 Organization에 접근 가능한가?)
 * 3. Gate::before: PLATFORM/SYSTEM 스코프는 모든 Organization 접근 가능
 */
class OrganizationPolicy
{
    /**
     * Organization 목록을 조회할 수 있는지 확인
     *
     * @param  User  $user  인증된 사용자
     * @return bool view-organizations 권한이 있으면 true
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view-organizations');
    }

    /**
     * 특정 Organization을 조회할 수 있는지 확인
     *
     * Spatie Permission + Filament Tenant로 권한 + 소유권 체크
     *
     * @param  User  $user  인증된 사용자
     * @param  Organization  $organization  조회하려는 Organization
     * @return bool 권한이 있고 소유권이 확인되면 true
     */
    public function view(User $user, Organization $organization): bool
    {
        // 1. Spatie Permission: view-organizations 권한 체크
        if (! $user->can('view-organizations')) {
            return false;
        }

        // 2. Filament Tenant: 리소스 소유권 체크
        return $this->canAccessOrganization($user, $organization);
    }

    /**
     * Organization을 생성할 수 있는지 확인
     *
     * @param  User  $user  인증된 사용자
     * @return bool create-organizations 권한이 있으면 true
     */
    public function create(User $user): bool
    {
        return $user->can('create-organizations');
    }

    /**
     * 특정 Organization을 수정할 수 있는지 확인
     *
     * @param  User  $user  인증된 사용자
     * @param  Organization  $organization  수정하려는 Organization
     * @return bool 권한이 있고 소유권이 확인되면 true
     */
    public function update(User $user, Organization $organization): bool
    {
        if (! $user->can('update-organizations')) {
            return false;
        }

        return $this->canAccessOrganization($user, $organization);
    }

    /**
     * 특정 Organization을 삭제할 수 있는지 확인
     *
     * @param  User  $user  인증된 사용자
     * @param  Organization  $organization  삭제하려는 Organization
     * @return bool delete-organizations 권한이 있고 소유권이 확인되면 true
     */
    public function delete(User $user, Organization $organization): bool
    {
        if (! $user->can('delete-organizations')) {
            return false;
        }

        return $this->canAccessOrganization($user, $organization);
    }

    /**
     * 특정 Organization을 복원할 수 있는지 확인
     *
     * @param  User  $user  인증된 사용자
     * @param  Organization  $organization  복원하려는 Organization
     * @return bool restore-organizations 권한이 있고 소유권이 확인되면 true
     */
    public function restore(User $user, Organization $organization): bool
    {
        if (! $user->can('restore-organizations')) {
            return false;
        }

        return $this->canAccessOrganization($user, $organization);
    }

    /**
     * 특정 Organization을 영구 삭제할 수 있는지 확인
     *
     * @param  User  $user  인증된 사용자
     * @param  Organization  $organization  영구 삭제하려는 Organization
     * @return bool force-delete-organizations 권한이 있고 소유권이 확인되면 true
     */
    public function forceDelete(User $user, Organization $organization): bool
    {
        if (! $user->can('force-delete-organizations')) {
            return false;
        }

        return $this->canAccessOrganization($user, $organization);
    }

    /**
     * 특정 Organization의 Activity Log를 조회할 수 있는지 확인
     *
     * @param  User  $user  인증된 사용자
     * @param  Organization  $organization  Activity Log를 조회하려는 Organization
     * @return bool view-activities 권한이 있고 소유권이 확인되면 true
     */
    public function viewActivities(User $user, Organization $organization): bool
    {
        // 1. Spatie Permission: view-activities 권한 체크
        if (! $user->can('view-activities')) {
            return false;
        }

        // 2. Filament Tenant: 리소스 소유권 체크
        return $this->canAccessOrganization($user, $organization);
    }

    /**
     * 사용자가 특정 Organization에 접근할 수 있는지 확인
     *
     * Filament Tenant (Role)의 scope_type과 scope_ref_id로 소유권 체크
     *
     * @param  User  $user  인증된 사용자
     * @param  Organization  $organization  접근하려는 Organization
     * @return bool 접근 가능하면 true
     */
    protected function canAccessOrganization(User $user, Organization $organization): bool
    {
        // Filament 테넌트(Role) 가져오기
        $tenant = Filament::getTenant();

        // 테넌트가 Role 인스턴스가 아니면 접근 불가
        if (! $tenant instanceof \App\Models\Role) {
            return false;
        }

        // PLATFORM/SYSTEM 스코프는 모든 Organization 접근 가능
        // (Gate::before에서도 처리되지만, 명시적으로 체크)
        if (in_array($tenant->scope_type, [
            ScopeType::PLATFORM->value,
            ScopeType::SYSTEM->value,
        ])) {
            return true;
        }

        // ORGANIZATION 스코프는 자신의 Organization만 접근 가능
        if ($tenant->scope_type === ScopeType::ORGANIZATION->value) {
            return $tenant->scope_ref_id === $organization->id;
        }

        // 그 외 스코프(BRAND, STORE 등)는 접근 불가
        return false;
    }
}

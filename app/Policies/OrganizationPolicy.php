<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\ScopeType;
use App\Models\Organization;
use App\Models\User;

/**
 * Organization Policy
 *
 * 멀티테넌시 환경에서 Organization 접근 권한을 검증합니다.
 * PLATFORM, SYSTEM 스코프 사용자는 모든 Organization에 접근 가능하며,
 * ORGANIZATION 스코프 사용자는 자신의 Organization만 접근 가능합니다.
 */
class OrganizationPolicy
{
    /**
     * 사용자가 Organization 목록을 조회할 수 있는지 확인
     *
     * @param  User  $user  인증된 사용자
     * @return bool PLATFORM/SYSTEM 스코프 또는 ORGANIZATION 역할이 있으면 true
     */
    public function viewAny(User $user): bool
    {
        // PLATFORM, SYSTEM 스코프는 모든 조직 조회 가능
        if ($this->hasGlobalScope($user)) {
            return true;
        }

        // ORGANIZATION 스코프 역할이 있으면 자신의 조직 조회 가능
        // Spatie Permission team_id context와 무관하게 직접 DB 쿼리
        return \DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('model_has_roles.model_type', User::class)
            ->where('model_has_roles.model_id', $user->id)
            ->where('roles.scope_type', ScopeType::ORGANIZATION->value)
            ->exists();
    }

    /**
     * 사용자가 특정 Organization을 조회할 수 있는지 확인
     *
     * @param  User  $user  인증된 사용자
     * @param  Organization  $organization  조회하려는 Organization
     * @return bool 접근 권한이 있으면 true
     */
    public function view(User $user, Organization $organization): bool
    {
        return $this->canAccessOrganization($user, $organization);
    }

    /**
     * 사용자가 Organization을 생성할 수 있는지 확인
     *
     * @param  User  $user  인증된 사용자
     * @return bool PLATFORM/SYSTEM 스코프만 생성 가능
     */
    public function create(User $user): bool
    {
        return $this->hasGlobalScope($user);
    }

    /**
     * 사용자가 특정 Organization을 수정할 수 있는지 확인
     *
     * @param  User  $user  인증된 사용자
     * @param  Organization  $organization  수정하려는 Organization
     * @return bool 접근 권한이 있으면 true
     */
    public function update(User $user, Organization $organization): bool
    {
        return $this->canAccessOrganization($user, $organization);
    }

    /**
     * 사용자가 특정 Organization을 삭제할 수 있는지 확인
     *
     * @param  User  $user  인증된 사용자
     * @param  Organization  $organization  삭제하려는 Organization
     * @return bool PLATFORM/SYSTEM 스코프만 삭제 가능
     */
    public function delete(User $user, Organization $organization): bool
    {
        return $this->hasGlobalScope($user);
    }

    /**
     * 사용자가 특정 Organization을 복원할 수 있는지 확인
     *
     * @param  User  $user  인증된 사용자
     * @param  Organization  $organization  복원하려는 Organization
     * @return bool PLATFORM/SYSTEM 스코프만 복원 가능
     */
    public function restore(User $user, Organization $organization): bool
    {
        return $this->hasGlobalScope($user);
    }

    /**
     * 사용자가 특정 Organization을 영구 삭제할 수 있는지 확인
     *
     * @param  User  $user  인증된 사용자
     * @param  Organization  $organization  영구 삭제하려는 Organization
     * @return bool PLATFORM/SYSTEM 스코프만 영구 삭제 가능
     */
    public function forceDelete(User $user, Organization $organization): bool
    {
        return $this->hasGlobalScope($user);
    }

    /**
     * 사용자가 특정 Organization의 Activity Log를 조회할 수 있는지 확인
     *
     * @param  User  $user  인증된 사용자
     * @param  Organization  $organization  Activity Log를 조회하려는 Organization
     * @return bool 접근 권한이 있으면 true
     */
    public function viewActivities(User $user, Organization $organization): bool
    {
        return $this->canAccessOrganization($user, $organization);
    }

    /**
     * 사용자가 PLATFORM 또는 SYSTEM 스코프를 가지고 있는지 확인
     *
     * Spatie Permission의 team_id context와 무관하게
     * 사용자가 가진 모든 Role을 확인합니다.
     *
     * @param  User  $user  인증된 사용자
     * @return bool PLATFORM/SYSTEM 스코프가 있으면 true
     */
    protected function hasGlobalScope(User $user): bool
    {
        // Spatie Permission team_id context와 무관하게 직접 DB 쿼리
        return \DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('model_has_roles.model_type', User::class)
            ->where('model_has_roles.model_id', $user->id)
            ->whereIn('roles.scope_type', [
                ScopeType::PLATFORM->value,
                ScopeType::SYSTEM->value,
            ])
            ->exists();
    }

    /**
     * 사용자가 특정 Organization에 접근할 수 있는지 확인
     *
     * Spatie Permission의 team_id context와 무관하게
     * 사용자가 가진 모든 Role을 확인합니다.
     *
     * @param  User  $user  인증된 사용자
     * @param  Organization  $organization  접근하려는 Organization
     * @return bool 접근 권한이 있으면 true
     */
    protected function canAccessOrganization(User $user, Organization $organization): bool
    {
        // PLATFORM, SYSTEM 스코프는 모든 조직 접근 가능
        if ($this->hasGlobalScope($user)) {
            return true;
        }

        // ORGANIZATION 스코프: 해당 조직에 대한 역할이 있는지 확인
        // Spatie Permission team_id context와 무관하게 직접 DB 쿼리
        return \DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('model_has_roles.model_type', User::class)
            ->where('model_has_roles.model_id', $user->id)
            ->where('roles.scope_type', ScopeType::ORGANIZATION->value)
            ->where('roles.scope_ref_id', $organization->id)
            ->exists();
    }
}

<?php

declare(strict_types=1);

/**
 * @CODE:AUTH-REDIRECT-001:DOMAIN | SPEC: .moai/specs/SPEC-AUTH-REDIRECT-001/spec.md
 *
 * 인증 후 지능형 테넌트 리다이렉트 서비스
 *
 * TDD History:
 * - REFACTOR (2025-10-19): AuthController에서 리다이렉트 로직 분리 (LOC 제약 준수)
 *
 * 책임:
 * - 사용자 테넌트 수 확인
 * - 테넌트 수에 따른 적절한 리다이렉트 경로 결정
 * - 단일 테넌트 자동 리다이렉트 처리
 */

namespace App\Services;

use App\Enums\ScopeType;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class AuthRedirectService
{
    /**
     * 로그인 후 사용자 테넌트 수에 따라 적절한 리다이렉트 처리
     *
     * @param  User  $user  인증된 사용자
     * @return RedirectResponse 리다이렉트 응답
     */
    public function redirectAfterLogin(User $user): RedirectResponse
    {
        $user->loadMissing('roles');
        $tenantCount = $this->countUserTenants($user);

        // 테넌트 0개 → 온보딩 (Filament tenantRegistration 경로)
        if ($tenantCount === 0) {
            /** @var RedirectResponse */
            return redirect('/org/new');
        }

        // 테넌트 1개 → 자동 리다이렉트
        if ($tenantCount === 1) {
            return $this->redirectToSingleTenant($user);
        }

        // 테넌트 2개+ → 계류페이지
        /** @var RedirectResponse */
        return redirect()->route('tenant.selector');
    }

    /**
     * 사용자가 소속된 테넌트 총 개수 확인
     *
     * Role의 scopeable 관계를 통해 실제 테넌트 수 계산
     * Spatie Permission의 team_id 필터를 우회하여 직접 DB 조회
     *
     * @param  User  $user  사용자
     * @return int 고유 테넌트 수 (Organization + Store + Brand)
     */
    private function countUserTenants(User $user): int
    {
        // 사용자의 모든 역할 조회 (Spatie Permission team_id 필터 우회)
        $roleIds = \DB::table('model_has_roles')
            ->where('model_id', $user->getKey())
            ->where('model_type', User::class)
            ->pluck('role_id');

        if ($roleIds->isEmpty()) {
            return 0;
        }

        // Organization, Store, Brand 스코프 타입 역할의 unique scope_ref_id 개수
        // SQLite 호환을 위해 Collection으로 변환하여 처리
        $roles = \DB::table('roles')
            ->whereIn('id', $roleIds)
            ->whereIn('scope_type', [
                ScopeType::ORGANIZATION->value,
                ScopeType::STORE->value,
                ScopeType::BRAND->value,
            ])
            ->get(['scope_type', 'scope_ref_id']);

        // scope_type + scope_ref_id 조합의 고유한 개수 계산
        return $roles->map(fn ($role): string => $role->scope_type . ':' . $role->scope_ref_id)->unique()->count();
    }

    /**
     * 단일 테넌트 소속 시 자동 리다이렉트 처리
     *
     * @param  User  $user  사용자
     * @return RedirectResponse 테넌트 패널 리다이렉트
     *
     * @throws \LogicException 테넌트를 찾을 수 없는 경우
     */
    private function redirectToSingleTenant(User $user): RedirectResponse
    {
        $user->loadMissing('roles');

        $roleIds = \DB::table('model_has_roles')
            ->where('model_type', User::class)
            ->where('model_id', $user->id)
            ->pluck('role_id');

        // Organization 확인
        /** @var object{scope_ref_id: int}|null $orgRole */
        $orgRole = \DB::table('roles')
            ->whereIn('id', $roleIds)
            ->where('scope_type', ScopeType::ORGANIZATION->value)
            ->first();

        if ($orgRole) {
            return redirect("/org/{$orgRole->scope_ref_id}");
        }

        // Store 확인
        /** @var object{scope_ref_id: int}|null $storeRole */
        $storeRole = \DB::table('roles')
            ->whereIn('id', $roleIds)
            ->where('scope_type', ScopeType::STORE->value)
            ->first();

        if ($storeRole) {
            return redirect("/store/{$storeRole->scope_ref_id}");
        }

        // Brand 확인
        /** @var object{scope_ref_id: int}|null $brandRole */
        $brandRole = \DB::table('roles')
            ->whereIn('id', $roleIds)
            ->where('scope_type', ScopeType::BRAND->value)
            ->first();

        if ($brandRole) {
            return redirect("/brand/{$brandRole->scope_ref_id}");
        }

        throw new \LogicException('No tenant found despite count being 1');
    }
}
